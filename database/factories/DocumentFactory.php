<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state. The owner follows the client's owner.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => fn (array $attributes) => Client::find($attributes['client_id'])->user_id,
            'policy_id' => null,
            'category' => DocumentCategory::Other,
            'name' => fake()->words(3, true),
            'original_name' => 'scan.pdf',
            'path' => fn () => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => 120_000,
            'notes' => null,
        ];
    }
}
