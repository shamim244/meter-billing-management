<?php

namespace App\Console\Commands;

use App\Models\IssueReport;
use Illuminate\Console\Command;

class IssueResolveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:resolve {identifier : Issue code or numeric ID} {--notes= : Summary of the resolution or code changes made}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark an issue resolved with resolution notes after AI agent code fix';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('identifier');
        $notes = $this->option('notes');

        if (empty($notes)) {
            $notes = $this->ask('Please enter a brief summary of the resolution / code fix:');
        }

        if (empty($notes)) {
            $this->error('❌ Resolution notes are required.');

            return 1;
        }

        $issue = is_numeric($id)
            ? IssueReport::find($id)
            : IssueReport::where('issue_code', $id)->first();

        if (! $issue) {
            $this->error("❌ Issue not found with identifier [{$id}].");

            return 1;
        }

        $issue->update([
            'status' => 'resolved',
            'ai_resolution_notes' => trim($notes),
            'resolved_at' => now(),
        ]);

        $this->info("✅ Issue [{$issue->issue_code}] marked as RESOLVED.");
        $this->line("📝 Notes: {$notes}");

        return 0;
    }
}
