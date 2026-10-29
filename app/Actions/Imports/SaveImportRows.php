<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Policies\PreparePolicyAttributes;
use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Clients\ClientData;
use App\DataTransferObject\Leads\LeadData;
use App\DataTransferObject\Policies\PolicyData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\ImportKind;
use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\PolicyEventType;
use App\Enums\PolicyStatus;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SaveImportRows
{
    /** Rows are saved in batches of this size, each in its own transaction. */
    private const BATCH = 200;

    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private PreparePolicyAttributes $preparePolicyAttributes,
        private RecordLeadActivity $recordLeadActivity,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Saves the checked rows: valid ones become new clients (with their policy) or leads; a later
     * row for the same person adds its policy to that client. Duplicates are skipped, or, when
     * the agent chose to, fill in what the existing record is missing (and add the row's policy).
     * Progress is saved after each batch, and the workspace hears about it once at the end.
     */
    public function handle(Import $import): void
    {
        $products = $import->workspace->products()->where('is_active', true)->get(['id', 'name', 'type', 'commission_rate'])
            ->keyBy(fn (Product $product) => mb_strtolower($product->name));
        $clientsByRow = [];

        $import->rows()
            ->whereIn('status', [ImportRowStatus::Valid, ImportRowStatus::Duplicate])
            ->chunkById(self::BATCH, function (Collection $rows) use ($import, $products, &$clientsByRow) {
                DB::transaction(function () use ($import, $rows, $products, &$clientsByRow) {
                    foreach ($rows as $row) {
                        $this->save($import, $row, $products, $clientsByRow);
                    }

                    $import->update(['imported_rows' => $import->rows()->whereIn('status', [ImportRowStatus::Imported, ImportRowStatus::Updated])->count()]);
                });
            }, 'row_number');

        $import->update(['status' => ImportStatus::Completed]);
        Storage::disk(Import::DISK)->delete($import->path);

        $topic = $import->kind === ImportKind::Clients ? ChangeTopic::Clients : ChangeTopic::Leads;
        $this->announceWorkspaceChange->handle($import->workspace_id, new WorkspaceChangeData($topic, ChangeAction::Created, count: $import->imported_rows));
    }

    /**
     * @param  Collection<string, Product>  $products
     * @param  array<int, string>  $clientsByRow  row number => the client that row created or updated
     */
    private function save(Import $import, ImportRow $row, Collection $products, array &$clientsByRow): void
    {
        $data = $row->data;
        $policy = $data['policy'] ?? null;
        unset($data['policy']);

        if ($row->status === ImportRowStatus::Duplicate && ($row->duplicate_id === null || ! $import->update_duplicates)) {
            $row->update(['status' => ImportRowStatus::Skipped]);

            return;
        }

        if ($import->kind === ImportKind::Leads) {
            // A lead row can match an existing client; that one is left alone.
            $lead = $row->duplicate_id ? $this->fillIn(Lead::find($row->duplicate_id), $data) : $this->createLead($import, $data);

            if ($lead === null) {
                $row->update(['status' => ImportRowStatus::Skipped]);

                return;
            }
        } else {
            $client = match (true) {
                $row->duplicate_id !== null => $this->fillIn(Client::find($row->duplicate_id), $data),
                $row->same_person_as !== null => Client::find($clientsByRow[$row->same_person_as] ?? null),
                default => $import->workspace->clients()->create(ClientData::fromArray($data)->toArray()),
            };

            if ($client === null) {
                $row->update(['status' => ImportRowStatus::Skipped]);

                return;
            }

            $clientsByRow[$row->row_number] = $client->id;

            if ($policy !== null) {
                $this->createPolicy($import, $client, $policy, $products);
            }
        }

        $row->update(['status' => $row->duplicate_id ? ImportRowStatus::Updated : ImportRowStatus::Imported]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createLead(Import $import, array $data): Lead
    {
        $lead = $import->workspace->leads()->create([...LeadData::fromArray([...$data, 'source' => LeadSource::Import->value])->toArray()]);
        $this->recordLeadActivity->handle($lead, LeadActivityType::Created, $import->user, properties: ['source' => LeadSource::Import->value]);

        return $lead;
    }

    /**
     * Fills in only what the existing record doesn't have yet; nothing the agent already typed is overwritten.
     *
     * @template T of Client|Lead
     *
     * @param  T|null  $person
     * @param  array<string, mixed>  $data
     * @return T|null
     */
    private function fillIn(Client|Lead|null $person, array $data): Client|Lead|null
    {
        if ($person === null) {
            return null;
        }

        foreach ($data as $field => $value) {
            if ($person->isFillable($field) && blank($person->getAttribute($field))) {
                $person->setAttribute($field, $value);
            }
        }
        $person->save();

        return $person;
    }

    /**
     * A catalog product with the same name sets the type and commission rate. A renewal date
     * makes it a custom term; otherwise an effective date means it renews every year.
     *
     * @param  array<string, mixed>  $policy
     * @param  Collection<string, Product>  $products
     */
    private function createPolicy(Import $import, Client $client, array $policy, Collection $products): void
    {
        $product = $products->get(mb_strtolower($policy['product_name']));

        $data = PolicyData::fromArray([
            'status' => $policy['policy_status'] ?? PolicyStatus::InForce->value,
            'premium' => $policy['premium'],
            'payment_frequency' => $policy['payment_frequency'],
            'commission_rate' => $policy['commission_rate'] ?? (string) ($product->commission_rate ?? '0'),
            'product_id' => $product?->id,
            'product_name' => $policy['product_name'],
            'policy_number' => $policy['policy_number'] ?? null,
            'type' => $policy['policy_type'] ?? null,
            'coverage_amount' => $policy['coverage_amount'] ?? null,
            'effective_date' => $policy['effective_date'] ?? null,
            'renewal_term' => match (true) {
                isset($policy['renewal_date']) => RenewalTerm::Custom->value,
                isset($policy['effective_date']) => RenewalTerm::Annual->value,
                default => null,
            },
            'renewal_date' => $policy['renewal_date'] ?? null,
        ]);

        $created = $client->policies()->create([
            ...$this->preparePolicyAttributes->handle($import->workspace, $data),
            'workspace_id' => $client->workspace_id,
        ]);
        $this->recordPolicyEvent->handle($created, PolicyEventType::Recorded, $import->user, ['status' => $created->status->value]);
    }
}
