<?php

declare(strict_types=1);

namespace Akira\Followable\Commands;

use Illuminate\Console\Command;

final class FollowableCommand extends Command
{
    public $signature = 'followable';

    public $description = 'My command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
