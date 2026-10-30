<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\ExportStatus;
use App\Models\WorkspaceExport;
use App\Notifications\WorkspaceExportReadyNotification;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class WriteWorkspaceExport
{
    /**
     * Writes one CSV per kind of record into a zip, then tells the person who asked. Rows are
     * read lazily, so a large workspace never sits in memory at once.
     */
    public function handle(WorkspaceExport $export): void
    {
        $path = "exports/{$export->workspace_id}/{$export->id}.zip";
        $disk = Storage::disk(WorkspaceExport::DISK);
        $disk->makeDirectory(dirname($path));

        $zip = new ZipArchive;
        if ($zip->open($disk->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Couldn't create the export zip at {$path}.");
        }

        foreach ($this->tables($export->workspace_id) as $file => $query) {
            $zip->addFromString("{$file}.csv", $this->csv($query));
        }

        $zip->close();

        DB::transaction(fn () => $export->update(['status' => ExportStatus::Ready, 'path' => $path, 'completed_at' => now()]));

        Cache::tags("workspace:{$export->workspace_id}")->flush();

        $export->requester?->notify(new WorkspaceExportReadyNotification($export));
    }

    /**
     * Every kind of record the workspace keeps, by file name. Histories and requirements belong to
     * a lead or policy rather than the workspace, so they're reached through those.
     *
     * @return array<string, Builder>
     */
    private function tables(string $workspaceId): array
    {
        $owned = fn (string $table) => DB::table($table)->where('workspace_id', $workspaceId);
        $leadIds = DB::table('leads')->where('workspace_id', $workspaceId)->select('id');
        $policyIds = DB::table('policies')->where('workspace_id', $workspaceId)->select('id');

        return [
            'leads' => $owned('leads'),
            'lead-activities' => DB::table('lead_activities')->whereIn('lead_id', $leadIds),
            'clients' => $owned('clients'),
            'policies' => $owned('policies'),
            'policy-events' => DB::table('policy_events')->whereIn('policy_id', $policyIds),
            'policy-renewals' => $owned('policy_renewals'),
            'application-requirements' => DB::table('application_requirements')->whereIn('policy_id', $policyIds),
            'follow-ups' => $owned('follow_ups'),
            'notes' => $owned('notes'),
            'products' => $owned('products'),
            'documents' => $owned('documents'),
            'forms' => $owned('forms'),
            'form-responses' => $owned('form_responses'),
        ];
    }

    /** The rows as CSV, with a header row of column names; empty tables still get their header. */
    private function csv(Builder $query): string
    {
        $stream = fopen('php://temp', 'r+');
        $wroteHeader = false;

        foreach ($query->orderBy('id')->lazy() as $row) {
            $row = array_diff_key((array) $row, ['workspace_id' => true]);

            if (! $wroteHeader) {
                fputcsv($stream, array_keys($row));
                $wroteHeader = true;
            }

            fputcsv($stream, $row);
        }

        if (! $wroteHeader) {
            fputcsv($stream, array_values(array_diff(DB::getSchemaBuilder()->getColumnListing($query->from), ['workspace_id'])));
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
