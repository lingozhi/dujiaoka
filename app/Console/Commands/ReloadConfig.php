<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReloadConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:reload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload configuration without restarting the container';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing configuration cache...');
        $this->call('config:clear');

        $this->info('Clearing application cache...');
        $this->call('cache:clear');

        $this->info('Clearing view cache...');
        $this->call('view:clear');

        $this->info('Clearing route cache...');
        $this->call('route:clear');

        $this->info('Rebuilding configuration cache...');
        $this->call('config:cache');

        $this->info('✓ Configuration reloaded successfully!');
        $this->warn('Note: Queue workers need container restart to reload.');

        return 0;
    }
}
