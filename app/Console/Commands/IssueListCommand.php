<?php

namespace App\Console\Commands;

use App\Models\IssueReport;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class IssueListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:list {--status= : Filter by status (pending, verified, spam, resolved)} {--severity= : Filter by severity (low, medium, high, critical)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all reported issues and bugs for AI agent review';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = IssueReport::with('user')->latest('id');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($severity = $this->option('severity')) {
            $query->where('severity', $severity);
        }

        $issues = $query->take(30)->get();

        if ($issues->isEmpty()) {
            $this->info('🎉 No issues found matching criteria.');

            return 0;
        }

        $headers = ['Code', 'Title', 'Category', 'Severity', 'Status', 'Reporter', 'Created'];
        $rows = $issues->map(function ($issue) {
            return [
                $issue->issue_code,
                Str::limit($issue->title, 40),
                $issue->category,
                $issue->severity,
                $issue->status,
                $issue->user ? "{$issue->user->name} (#{$issue->user->id})" : 'Guest',
                $issue->created_at->format('Y-m-d H:i'),
            ];
        });

        $this->table($headers, $rows);
        $this->info("Total: {$issues->count()} issues displayed. Inspect with: php artisan issue:show <CODE>");

        return 0;
    }
}
