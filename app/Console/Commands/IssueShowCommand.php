<?php

namespace App\Console\Commands;

use App\Models\IssueReport;
use Illuminate\Console\Command;

class IssueShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:show {identifier : Issue code (e.g. BUG-20260918-XXXX) or numeric ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display full technical diagnostic bundle and AI prompt for an issue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('identifier');

        $issue = is_numeric($id)
            ? IssueReport::with(['user', 'mru', 'verifier', 'resolver'])->find($id)
            : IssueReport::with(['user', 'mru', 'verifier', 'resolver'])->where('issue_code', $id)->first();

        if (! $issue) {
            $this->error("❌ Issue not found with identifier [{$id}].");

            return 1;
        }

        $this->line($issue->toAiPrompt());

        return 0;
    }
}
