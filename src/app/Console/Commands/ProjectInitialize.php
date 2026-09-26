<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ProjectInitialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:init
        {--fresh : Drop all tables and run all migrations before seeding}
        {--seed : Run the database seeders when the database is empty}
        {--shield : Regenerate Filament Shield permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the ISOC project database and local development data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Resetting the database with migrate:fresh.');
            $this->call('migrate:fresh', [
                '--force' => true,
            ]);
        } else {
            $this->call('migrate', [
                '--force' => true,
            ]);
        }

        if ($this->option('shield')) {
            foreach (['superadmin', 'admin', 'tutor', 'peserta'] as $panel) {
                $this->call('shield:generate', [
                    '--all' => true,
                    '--panel' => $panel,
                ]);
            }
        }

        $hasUsers = User::query()->exists();

        if ($this->option('seed') && $hasUsers) {
            $this->components->warn('Users already exist. Use project:init --fresh to reset and reseed.');
        }

        if (! $hasUsers) {
            $this->call('db:seed', [
                '--force' => true,
            ]);
        } else {
            $this->components->info('Users already exist. Skipping seeders.');
        }

        $this->call('filament:optimize-clear');
        $this->call('optimize:clear');

        $this->components->info('Project initialization complete.');

        return self::SUCCESS;
    }
}
