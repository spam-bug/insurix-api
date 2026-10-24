<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyType;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class AddSuggestedProduct
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Puts a typed-in product name into the catalog and links every policy that used it. A
     * product with that name (ignoring case) is reused; otherwise one is created with the type
     * and average commission rate of those policies. The policies keep their own figures.
     *
     * @return array{product: Product, linked: int}
     */
    public function handle(Workspace $workspace, string $name): array
    {
        $key = mb_strtolower(trim($name));

        $result = DB::transaction(function () use ($workspace, $name, $key) {
            $policies = $workspace->policies()->whereNull('product_id')->whereRaw('lower(trim(product_name)) = ?', [$key]);
            $figures = (clone $policies)->toBase()->selectRaw('max(type) as type, avg(commission_rate) as commission_rate')->first();

            $product = $workspace->products()->whereRaw('lower(name) = ?', [$key])->first()
                ?? $workspace->products()->create([
                    'name' => trim($name),
                    'type' => $figures->type ?? PolicyType::Other->value,
                    'commission_rate' => round((float) ($figures->commission_rate ?? 0), 2),
                ]);

            return ['product' => $product, 'linked' => $policies->update(['product_id' => $product->id])];
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Products, ChangeAction::Created, $result['product']->id, $result['product']->name));

        return $result;
    }
}
