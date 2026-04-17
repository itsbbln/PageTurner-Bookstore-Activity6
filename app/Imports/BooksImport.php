<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Category;
use App\Models\ImportLog;
use App\Rules\IsbnRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

class BooksImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts, SkipsOnFailure, WithEvents, ShouldQueue
{
    use Importable;
    use SkipsFailures;

    public function __construct(
        public readonly int $importLogId,
        public readonly bool $updateExisting = false,
    ) {
    }

    public function model(array $row)
    {
        // HeadingRow formatter is "slug" => expected keys:
        // isbn, title, author, price, stock, category, description
        $isbn = $this->normalizeIsbn($row['isbn'] ?? null);
        $title = trim((string) ($row['title'] ?? ''));
        $author = trim((string) ($row['author'] ?? ''));
        $price = (float) ($row['price'] ?? 0);
        $stock = (int) ($row['stock'] ?? 0);
        $categoryName = trim((string) ($row['category'] ?? ''));
        $description = isset($row['description']) ? (string) $row['description'] : null;

        $categoryId = Category::where('name', $categoryName)->value('id');

        if ($this->updateExisting) {
            $book = Book::firstOrNew(['isbn' => $isbn]);
            $book->fill([
                'category_id' => $categoryId,
                'title' => $title,
                'author' => $author,
                'price' => $price,
                'stock_quantity' => $stock,
                'description' => $description,
            ]);
            $book->save();

            return null; // already persisted
        }

        return new Book([
            'category_id' => $categoryId,
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'price' => $price,
            'stock_quantity' => $stock,
            'description' => $description,
        ]);
    }

    public function rules(): array
    {
        $uniqueRule = $this->updateExisting ? 'sometimes' : 'unique:books,isbn';

        return [
            '*.isbn' => ['required', new IsbnRule(), $uniqueRule],
            '*.title' => ['required', 'string', 'max:255'],
            '*.author' => ['required', 'string', 'max:255'],
            '*.price' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            '*.stock' => ['required', 'integer', 'min:0'],
            '*.category' => ['required', 'exists:categories,name'],
            '*.description' => ['nullable', 'string'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            '*.isbn' => 'ISBN',
            '*.title' => 'Title',
            '*.author' => 'Author',
            '*.price' => 'Price',
            '*.stock' => 'Stock',
            '*.category' => 'Category',
            '*.description' => 'Description',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function () {
                ImportLog::whereKey($this->importLogId)->update([
                    'status' => 'running',
                    'started_at' => now(),
                ]);
            },
            AfterChunk::class => function (AfterChunk $event) {
                $chunkSize = (int) $event->getConcernable()->chunkSize();
                ImportLog::whereKey($this->importLogId)->update([
                    'processed_rows' => DB::raw("processed_rows + {$chunkSize}"),
                ]);
            },
            AfterImport::class => function () {
                $failures = $this->failures();
                $failedRows = $failures->count();

                $failurePayload = $failures->take(200)->map(function ($failure) {
                    return [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                })->values()->all();

                $status = $failedRows > 0 ? 'completed_with_errors' : 'completed';

                $log = ImportLog::find($this->importLogId);
                if (! $log) {
                    return;
                }

                $reportPath = null;
                if ($failedRows > 0) {
                    $reportPath = "imports/books/failure_reports/{$log->uuid}.json";
                    Storage::disk($log->stored_disk)->put($reportPath, json_encode([
                        'import_uuid' => $log->uuid,
                        'failed_rows' => $failedRows,
                        'failures' => $failurePayload,
                    ], JSON_PRETTY_PRINT));
                }

                $log->update([
                    'status' => $status,
                    'failed_rows' => $failedRows,
                    'failures' => $failurePayload,
                    'failure_report_path' => $reportPath,
                    'finished_at' => now(),
                ]);
            },
            ImportFailed::class => function (ImportFailed $event) {
                ImportLog::whereKey($this->importLogId)->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                ]);
            },
        ];
    }

    private function normalizeIsbn(?string $value): string
    {
        $raw = strtoupper(trim((string) $value));
        return str_replace([' ', '-'], '', $raw);
    }
}

