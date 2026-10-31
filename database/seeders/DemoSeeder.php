<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Forms\CreateForm;
use App\Actions\Forms\PublishForm;
use App\Actions\Leads\ScoreLead;
use App\DataTransferObject\Forms\FormData;
use App\Enums\ApplicationStage;
use App\Enums\DocumentCategory;
use App\Enums\FollowUpType;
use App\Enums\FormTemplate;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Policy;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * A believable workspace for demos and landing page screenshots: a Filipino agent with leads in
 * every stage, clients with policies (some renewing or pending soon), today's follow-ups, forms,
 * documents and notes. Run by hand: `php artisan db:seed --class=DemoSeeder`.
 * Signs in as demo@insurix.test / password.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'demo@insurix.test',
            'phone' => '0917 555 0123',
        ]);
        $workspace = $agent->workspaces()->sole();
        $workspace->update(['name' => 'Santos Insurance Advisory']);

        $products = $this->products($workspace);
        $this->leads($workspace);
        $clients = $this->clients($workspace, $agent, $products);
        $this->followUps($workspace, $clients);
        $this->forms($workspace);

        Cache::flush();
    }

    /**
     * @return array<string, Product>
     */
    private function products(Workspace $workspace): array
    {
        $products = [
            'life' => ['Family Protect Life', PolicyType::Life, 25, PaymentFrequency::Annual],
            'vul' => ['Wealth Builder VUL', PolicyType::Life, 35, PaymentFrequency::Monthly],
            'health' => ['HealthGuard Plus', PolicyType::Health, 20, PaymentFrequency::Annual],
            'education' => ['Bright Future Education Plan', PolicyType::Life, 30, PaymentFrequency::Quarterly],
            'auto' => ['DriveSafe Comprehensive', PolicyType::Auto, 15, PaymentFrequency::Annual],
        ];

        return array_map(fn (array $product) => Product::factory()->for($workspace)->create([
            'name' => $product[0],
            'type' => $product[1],
            'commission_rate' => $product[2],
            'payment_frequency' => $product[3],
            'description' => null,
        ]), $products);
    }

    private function leads(Workspace $workspace): void
    {
        $leads = [
            ['Liza Garcia', LeadStatus::MeetingScheduled, LeadSource::Referral, LeadInterest::Life, 1],
            ['Ramon Villanueva', LeadStatus::Contacted, LeadSource::ShareableForm, LeadInterest::Health, 2],
            ['Ana Reyes', LeadStatus::Qualified, LeadSource::SocialMedia, LeadInterest::Health, 3],
            ['Paolo Aquino', LeadStatus::Prospect, LeadSource::ShareableForm, LeadInterest::Life, 0],
            ['Kristine Navarro', LeadStatus::Prospect, LeadSource::SocialMedia, LeadInterest::Health, 1],
            ['Mark Dizon', LeadStatus::Prospect, LeadSource::Referral, LeadInterest::Auto, 4],
            ['Jenny Lim', LeadStatus::Prospect, LeadSource::ShareableForm, LeadInterest::Life, 2],
            ['Arnel Bautista', LeadStatus::Prospect, LeadSource::Manual, LeadInterest::Property, 6],
            ['Grace Soriano', LeadStatus::Prospect, LeadSource::SocialMedia, LeadInterest::Health, 8],
            ['Rico Mercado', LeadStatus::Prospect, LeadSource::ShareableForm, LeadInterest::Life, 3],
            ['Joy Castillo', LeadStatus::Prospect, LeadSource::Referral, LeadInterest::Life, 5],
            ['Noel Ramos', LeadStatus::Contacted, LeadSource::SocialMedia, LeadInterest::Auto, 4],
            ['Camille Torres', LeadStatus::Contacted, LeadSource::ShareableForm, LeadInterest::Health, 6],
            ['Dennis Flores', LeadStatus::Contacted, LeadSource::Referral, LeadInterest::Life, 9],
            ['Bea Manalo', LeadStatus::Contacted, LeadSource::Manual, LeadInterest::Life, 12],
            ['Jerome Pascual', LeadStatus::MeetingScheduled, LeadSource::Referral, LeadInterest::Health, 7],
            ['Sheila Domingo', LeadStatus::MeetingScheduled, LeadSource::ShareableForm, LeadInterest::Life, 5],
            ['Victor Salazar', LeadStatus::MeetingScheduled, LeadSource::SocialMedia, LeadInterest::Property, 10],
            ['Lovely Cruz', LeadStatus::Qualified, LeadSource::Referral, LeadInterest::Life, 8],
            ['Edwin Tan', LeadStatus::Qualified, LeadSource::ShareableForm, LeadInterest::Auto, 11],
            ['Rhea Valdez', LeadStatus::Lost, LeadSource::SocialMedia, LeadInterest::Health, 30],
            ['Allan Ocampo', LeadStatus::Lost, LeadSource::Manual, LeadInterest::Life, 45],
        ];

        foreach ($leads as [$name, $status, $source, $interest, $daysAgo]) {
            $lead = Lead::factory()->for($workspace)->create([
                'name' => $name,
                'email' => $this->email($name),
                'phone' => $this->phone(),
                'address' => fake()->randomElement(['Quezon City', 'Makati City', 'Pasig City', 'Cebu City', 'Davao City', 'Antipolo, Rizal']),
                'source' => $source,
                'interest' => $interest,
                'status' => $status,
                'notes' => null,
                'meeting_at' => $status === LeadStatus::MeetingScheduled ? now()->addDays(fake()->numberBetween(1, 5))->setTime(14, 0) : null,
            ]);
            $lead->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

            app(ScoreLead::class)->handle($lead);
        }
    }

    /**
     * @param  array<string, Product>  $products
     * @return array<string, Client>
     */
    private function clients(Workspace $workspace, User $agent, array $products): array
    {
        $clients = [
            'carlo' => ['Carlo Mendoza', [['health', 24_000, PolicyStatus::InForce, 14]]],
            'liza' => ['Liza Fernandez', [['life', 36_000, PolicyStatus::InForce, 120], ['health', 18_000, PolicyStatus::InForce, 25]]],
            'jose' => ['Jose Bautista', [['education', 4_500, PolicyStatus::InForce, 200]]],
            'maricel' => ['Maricel Gonzales', [['vul', 3_000, PolicyStatus::InForce, 310]]],
            'roberto' => ['Roberto Santiago', [['auto', 28_500, PolicyStatus::InForce, 9]]],
            'teresa' => ['Teresa Uy', [['life', 42_000, PolicyStatus::InForce, 260]]],
            'miguel' => ['Miguel de Leon', [['vul', 5_000, PolicyStatus::Pending, null]]],
            'angela' => ['Angela Reyes', [['health', 21_000, PolicyStatus::Pending, null]]],
            'benjie' => ['Benjie Cortez', [['life', 30_000, PolicyStatus::Lapsed, -20]]],
            'patricia' => ['Patricia Lopez', [['education', 6_000, PolicyStatus::InForce, 150]]],
        ];

        $created = [];

        foreach ($clients as $key => [$name, $policies]) {
            $lead = in_array($key, ['carlo', 'liza', 'jose', 'maricel', 'roberto', 'teresa', 'patricia'], true)
                ? Lead::factory()->for($workspace)->create(['name' => $name, 'email' => $this->email($name), 'status' => LeadStatus::Converted, 'source' => $key === 'maricel' ? LeadSource::ShareableForm : LeadSource::Referral, 'notes' => null])
                : null;

            $client = Client::factory()->for($workspace)->create([
                'lead_id' => $lead?->id,
                'name' => $name,
                'email' => $this->email($name),
                'phone' => $this->phone(),
                'address' => fake()->randomElement(['Quezon City', 'Makati City', 'Taguig City', 'Mandaluyong City', 'Cebu City']),
                'occupation' => fake()->randomElement(['Teacher', 'Nurse', 'Small business owner', 'Engineer', 'Call center manager', 'OFW']),
                'notes' => null,
            ]);

            foreach ($policies as [$product, $premium, $status, $renewsInDays]) {
                $this->policy($client, $products[$product], $premium, $status, $renewsInDays);
            }

            $created[$key] = $client;
        }

        $documents = [
            ['carlo', 'Valid ID', DocumentCategory::ValidId, 'image/jpeg', 'jpg'],
            ['carlo', 'Medical exam results', DocumentCategory::MedicalRecord, 'application/pdf', 'pdf'],
            ['liza', 'Policy contract', DocumentCategory::PolicyContract, 'application/pdf', 'pdf'],
            ['liza', 'Official receipt October', DocumentCategory::Receipt, 'image/jpeg', 'jpg'],
            ['jose', 'Beneficiary form', DocumentCategory::ApplicationForm, 'application/pdf', 'pdf'],
            ['miguel', 'Application form', DocumentCategory::ApplicationForm, 'application/pdf', 'pdf'],
            ['miguel', 'Valid ID', DocumentCategory::ValidId, 'image/jpeg', 'jpg'],
            ['angela', 'Medical questionnaire', DocumentCategory::Requirement, 'application/pdf', 'pdf'],
        ];

        foreach ($documents as [$client, $name, $category, $mime, $extension]) {
            Document::factory()->for($created[$client])->create([
                'workspace_id' => $workspace->id,
                'category' => $category,
                'name' => $name,
                'original_name' => str($name)->slug()->append(".{$extension}")->toString(),
                'mime_type' => $mime,
                'size' => fake()->numberBetween(80_000, 2_400_000),
            ]);
        }

        $notes = [
            ['carlo', 'Wants to add his wife to the health plan at renewal. Prepare the family rate.'],
            ['liza', 'Prefers Viber over calls. Best time is after 6 PM.'],
            ['miguel', 'Still waiting on his medical exam schedule from the clinic.'],
            [null, 'Product training on the new VUL fund options next Thursday, 2 PM.'],
        ];

        foreach ($notes as [$client, $body]) {
            Note::factory()->for($workspace)->create([
                'user_id' => $agent->id,
                'client_id' => $client ? $created[$client]->id : null,
                'body' => $body,
            ]);
        }

        return $created;
    }

    private function policy(Client $client, Product $product, int $premium, PolicyStatus $status, ?int $renewsInDays): void
    {
        $renewalDate = $renewsInDays === null ? null : now()->addDays($renewsInDays);

        Policy::factory()->for($client)->create([
            'workspace_id' => $client->workspace_id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'type' => $product->type,
            'status' => $status,
            'application_stage' => $status === PolicyStatus::Pending ? fake()->randomElement([ApplicationStage::Underwriting, ApplicationStage::RequirementsNeeded]) : null,
            'stage_changed_at' => $status === PolicyStatus::Pending ? now()->subDays(3) : null,
            'premium' => $premium,
            'payment_frequency' => $product->payment_frequency,
            'commission_rate' => $product->commission_rate,
            'effective_date' => $renewalDate?->copy()->subYear()->toDateString() ?? now()->toDateString(),
            'renewal_term' => RenewalTerm::Custom,
            'renewal_date' => $renewalDate?->toDateString(),
            'commission_received_at' => $status === PolicyStatus::InForce && fake()->boolean(60) ? now()->subDays(fake()->numberBetween(10, 200)) : null,
        ]);
    }

    /**
     * @param  array<string, Client>  $clients
     */
    private function followUps(Workspace $workspace, array $clients): void
    {
        $lead = fn (string $name) => Lead::where('workspace_id', $workspace->id)->where('name', $name)->sole();
        $today = Carbon::today();

        $followUps = [
            ['client' => $clients['carlo'], 'type' => FollowUpType::Call, 'title' => 'Call about his renewal', 'due_at' => $today->copy()->subDay()->setTime(10, 0)],
            ['lead' => $lead('Jerome Pascual'), 'type' => FollowUpType::Meeting, 'title' => 'Meeting at his office', 'due_at' => $today->copy()->setTime(15, 0)],
            ['lead' => $lead('Ana Reyes'), 'type' => FollowUpType::Email, 'title' => 'Send the health plan quote', 'due_at' => $today->copy()->setTime(11, 0)],
            ['lead' => $lead('Ramon Villanueva'), 'type' => FollowUpType::Message, 'title' => 'Reply to his form inquiry', 'due_at' => $today->copy()->setTime(9, 30)],
            ['client' => $clients['angela'], 'type' => FollowUpType::Task, 'title' => 'Follow up her medical questionnaire', 'due_at' => $today->copy()->addDay()->setTime(10, 0)],
            ['lead' => $lead('Liza Garcia'), 'type' => FollowUpType::Call, 'title' => 'Confirm Thursday meeting', 'due_at' => $today->copy()->addDays(2)->setTime(13, 0)],
            ['client' => $clients['roberto'], 'type' => FollowUpType::Call, 'title' => 'Remind about car insurance renewal', 'due_at' => $today->copy()->addDays(3)->setTime(9, 0)],
            ['lead' => $lead('Paolo Aquino'), 'type' => FollowUpType::Message, 'title' => 'Introduce yourself on Messenger', 'due_at' => $today->copy()->subDays(3)->setTime(14, 0), 'completed_at' => $today->copy()->subDays(3)],
        ];

        foreach ($followUps as $followUp) {
            FollowUp::factory()->create([
                'workspace_id' => $workspace->id,
                'lead_id' => ($followUp['lead'] ?? null)?->id,
                'client_id' => ($followUp['client'] ?? null)?->id,
                'type' => $followUp['type'],
                'title' => $followUp['title'],
                'notes' => null,
                'due_at' => $followUp['due_at'],
                'completed_at' => $followUp['completed_at'] ?? null,
            ]);
        }
    }

    private function forms(Workspace $workspace): void
    {
        foreach ([FormTemplate::Contact, FormTemplate::QuoteRequest] as $template) {
            $form = app(CreateForm::class)->handle($workspace, new FormData($template->label(), $template));
            app(PublishForm::class)->handle($form);
        }
    }

    private function email(string $name): string
    {
        return str($name)->lower()->replace(' ', '.')->ascii()->append('@example.com')->toString();
    }

    private function phone(): string
    {
        return '09'.fake()->numerify('## ### ####');
    }
}
