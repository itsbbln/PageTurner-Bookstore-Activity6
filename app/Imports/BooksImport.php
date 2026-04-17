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
use Maatwebsite\Excel\Validators\Failure;

class BooksImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts, SkipsOnFailure, WithEvents, ShouldQueue
{
    use Importable;

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

        if (! $categoryId) {
            // Let validation handle it, but keep a guard for safety.
            return null;
        }

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
            $this->incrementSuccessfulRows();

            return null; // already persisted
        }

        $this->incrementSuccessfulRows();

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
            'isbn' => ['required', new IsbnRule(), $uniqueRule],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'category' => ['required', 'exists:categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'isbn' => 'ISBN',
            'title' => 'Title',
            'author' => 'Author',
            'price' => 'Price',
            'stock' => 'Stock',
            'category' => 'Category',
            'description' => 'Description',
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
                // no-op: counts are consolidated in AfterImport from success + failures
            },
            AfterImport::class => function () {
                $log = ImportLog::find($this->importLogId);
                if (! $log) {
                    return;
                }

                $failedRows = (int) $log->failed_rows;
                $successfulRows = (int) $log->successful_rows;
                $processedRows = $successfulRows + $failedRows;
                $status = $failedRows > 0 ? 'completed_with_errors' : 'completed';

                $reportPath = $log->failure_report_path;
                if ($failedRows > 0 && ! empty($log->failures)) {
                    $reportPath = "imports/books/failure_reports/{$log->uuid}.json";
                    Storage::disk($log->stored_disk)->put($reportPath, json_encode([
                        'import_uuid' => $log->uuid,
                        'failed_rows' => $failedRows,
                        'successful_rows' => $successfulRows,
                        'processed_rows' => $processedRows,
                        'failures' => $log->failures,
                    ], JSON_PRETTY_PRINT));
                }

                $log->update([
                    'status' => $status,
                    'processed_rows' => $processedRows,
                    'total_rows' => $processedRows,
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

    /**
     * Persist validation failures in DB (works across queued chunks).
     */
    public function onFailure(Failure ...$failures): void
    {
        $failurePayload = collect($failures)->map(function (Failure $failure) {
            return [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        })->values();

        ImportLog::whereKey($this->importLogId)->update([
            'failed_rows' => DB::raw('failed_rows + ' . $failurePayload->count()),
        ]);

        $log = ImportLog::find($this->importLogId);
        if (! $log) {
            return;
        }

        $existingFailures = collect($log->failures ?? []);
        $merged = $existingFailures
            ->concat($failurePayload)
            ->take(200)
            ->values()
            ->all();

        $log->update([
            'failures' => $merged,
        ]);
    }

    private function incrementSuccessfulRows(): void
    {
        ImportLog::whereKey($this->importLogId)->update([
            'successful_rows' => DB::raw('successful_rows + 1'),
        ]);
    }

    private function normalizeIsbn(?string $value): string
    {
        $raw = strtoupper(trim((string) $value));
        return str_replace([' ', '-'], '', $raw);
    }
}

