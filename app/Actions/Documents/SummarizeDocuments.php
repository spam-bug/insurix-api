<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentCategory;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class SummarizeDocuments
{
    public function __construct(private MeasureStorage $measureStorage) {}

    /**
     * The Documents page at a glance: how many files, how many wait in the inbox, how many of
     * each type (every type, zero included), and the storage used out of the limit.
     *
     * @return array{total: int, unassigned: int, by_category: array<string, int>, storage: array{used: int, limit: int}}
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("documents:{$workspace->id}:summary", function () use ($workspace) {
            $byCategory = $workspace->documents()->toBase()->selectRaw('category, count(*) as aggregate')->groupBy('category')->pluck('aggregate', 'category');

            return [
                'total' => (int) $byCategory->sum(),
                'unassigned' => $workspace->documents()->whereNull('client_id')->count(),
                'by_category' => collect(DocumentCategory::cases())
                    ->mapWithKeys(fn (DocumentCategory $category) => [$category->value => (int) ($byCategory[$category->value] ?? 0)])
                    ->all(),
                'storage' => $this->measureStorage->handle($workspace),
            ];
        });
    }
}
