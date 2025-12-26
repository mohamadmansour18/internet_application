<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RunBackupWithLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:runWithLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the backup and log the result in the backup_logs table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $runAt = now();

        try {

            $backupDir = config('backup.backup.name');
            Storage::disk('google')->put($backupDir.'/.keep', 'created');

            Artisan::call('backup:run' , ['--only-db' => true]);

            Storage::disk('google')->delete($backupDir.'/.keep');

            Backup::create([
                'run_at' => $runAt,
                'status' => 'success',
                'message' => 'Backup completed successfully.',
            ]);

            Log::channel('aspect')->info('Backup finished successfully and has been logged.');

            return self::SUCCESS;

        } catch (\Throwable $e) {

            Backup::create([
                'run_at' => $runAt,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            Log::channel('aspect')->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
