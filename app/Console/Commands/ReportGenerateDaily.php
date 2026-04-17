<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ReportGenerateDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-daily {--date= : Date (Y-m-d) for the report; defaults to yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily sales summary report and email administrators';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $from = (clone $date);
        $to = (clone $date)->endOfDay();

        $orders = Order::whereBetween('created_at', [$from, $to])->get(['id', 'status', 'total_amount', 'created_at']);
        $revenue = $orders->where('status', 'completed')->sum('total_amount');
        $countByStatus = $orders->groupBy('status')->map->count()->toArray();

        $payload = [
            'date' => $from->toDateString(),
            'total_orders' => $orders->count(),
            'revenue_completed' => (float) $revenue,
            'status_counts' => $countByStatus,
        ];

        $path = "reports/daily_sales/{$from->toDateString()}.json";
        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT));

        $admins = User::where('role', 'admin')->pluck('email')->filter()->values()->all();
        $recipients = array_values(array_unique(array_filter($admins)));

        if (count($recipients) > 0) {
            Mail::raw(
                "Daily Sales Report ({$from->toDateString()})\n\n" . json_encode($payload, JSON_PRETTY_PRINT),
                function ($message) use ($recipients, $from) {
                    $message->to($recipients)
                        ->subject("PageTurner Daily Sales Report - {$from->toDateString()}");
                }
            );
        }

        $this->info("Generated daily report for {$from->toDateString()} (stored at {$path}).");

        return self::SUCCESS;
    }
}
