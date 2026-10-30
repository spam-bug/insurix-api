<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Imports\StartImport;
use App\DataTransferObject\Forms\ImportResponsesData;
use App\Enums\FormFieldType;
use App\Enums\ImportField;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportFormResponses
{
    public function __construct(private StartImport $startImport) {}

    /**
     * Turns the chosen responses into a spreadsheet, one row each, and starts an import from it,
     * so they go through the same steps as a file: matching columns, checking every row,
     * duplicates by email or phone, suggested fixes. Linked questions are matched to their
     * detail already; the agent matches the rest. The import remembers each row's response.
     */
    public function handle(Form $form, User $user, ImportResponsesData $data): Import
    {
        $responses = $form->responses()->whereIn('id', $data->responseIds)->oldest()->get();
        $columns = $this->columns($responses);
        $path = tempnam(sys_get_temp_dir(), 'responses');
        $file = fopen($path, 'w');

        fputcsv($file, array_column($columns, 'label'), escape: '');
        foreach ($responses as $response) {
            fputcsv($file, array_map(fn (array $column) => $this->answerText($response, $column['field']), $columns), escape: '');
        }
        fclose($file);

        $import = $this->startImport->handle($form->workspace, $user, $data->kind, new UploadedFile($path, "{$form->name} responses.csv", 'text/csv', null, true));
        unlink($path);
        $kindFields = array_column(ImportField::for($data->kind), 'value');
        $linked = [];

        foreach ($columns as $position => $column) {
            $link = $column['field']['link'] ?? null;

            if ($link !== null && in_array($link, $kindFields, true)) {
                $linked[$link] = $position;
            }
        }

        // A full name makes separate first and last name columns unnecessary, as in a file.
        if (isset($linked[ImportField::Name->value])) {
            unset($linked[ImportField::FirstName->value], $linked[ImportField::LastName->value]);
        }

        DB::transaction(fn () => $import->update([
            'mapping' => $this->withoutTakenColumns($import->mapping ?? [], $linked) + $linked,
            'responses' => $responses->values()->mapWithKeys(fn (FormResponse $response, int $index) => [$index + 2 => $response->id])->all(),
        ]));

        return $import;
    }

    /**
     * One column per question across the responses, in form order, so answers to a question that
     * was later removed still come along. Layout blocks ask nothing and are left out.
     *
     * @param  Collection<int, FormResponse>  $responses
     * @return list<array{label: string, field: array<string, mixed>}>
     */
    private function columns(Collection $responses): array
    {
        $columns = [];

        foreach ($responses as $response) {
            foreach ($response->fields as $field) {
                if (! FormFieldType::from($field['type'])->isLayout()) {
                    $columns[$field['id']] ??= ['label' => $field['label'] ?: $field['id'], 'field' => $field];
                }
            }
        }

        return array_values($columns);
    }

    /**
     * The answer as a spreadsheet cell: choices by their label, yes/no as words.
     *
     * @param  array<string, mixed>  $field
     */
    private function answerText(FormResponse $response, array $field): string
    {
        $answer = $response->answers[$field['id']] ?? null;
        $labels = array_column($field['options'] ?? [], 'label', 'id');

        return match (true) {
            $answer === null => '',
            is_bool($answer) => $answer ? 'Yes' : 'No',
            is_array($answer) => implode(', ', array_map(fn (string $id) => $labels[$id] ?? $id, $answer)),
            // Linked choices keep their stored value (such as "health"), which the import reads.
            isset($labels[$answer]) && ($field['link'] ?? null) === null => $labels[$answer],
            default => (string) $answer,
        };
    }

    /**
     * The import's own guesses, minus any that use a column or detail a linked question already fills.
     *
     * @param  array<string, int>  $guessed
     * @param  array<string, int>  $linked
     * @return array<string, int>
     */
    private function withoutTakenColumns(array $guessed, array $linked): array
    {
        $nameTaken = isset($linked[ImportField::Name->value]) || isset($linked[ImportField::FirstName->value]) || isset($linked[ImportField::LastName->value]);

        return array_filter($guessed, fn (int $column, string $field) => ! isset($linked[$field])
            && ! in_array($column, $linked, true)
            && ! ($nameTaken && in_array($field, [ImportField::Name->value, ImportField::FirstName->value, ImportField::LastName->value], true)), ARRAY_FILTER_USE_BOTH);
    }
}
