<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Runs whenever the built-in health route (/up) is hit. Any thrown
 * exception makes /up return a 500, which is what uptime monitors key on.
 */
class EnsureApplicationIsHealthy
{
    public function handle(DiagnosingHealth $event): void
    {
        $this->checkDatabase();
        $this->checkPrivateStorage();
        $this->checkQueueConfiguration();
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            throw new RuntimeException('Health check failed: database is unreachable.', previous: $e);
        }
    }

    private function checkPrivateStorage(): void
    {
        $disk = Storage::disk('private');
        $probe = '.health/'.Str::uuid()->toString().'.txt';

        try {
            $disk->put($probe, 'ok');

            if ($disk->get($probe) !== 'ok') {
                throw new RuntimeException('Probe file readback mismatch.');
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('Health check failed: private storage is not writable.', previous: $e);
        } finally {
            try {
                $disk->delete($probe);
            } catch (\Throwable) {
                // Best effort cleanup only.
            }
        }
    }

    private function checkQueueConfiguration(): void
    {
        $connection = (string) config('queue.default');

        // The spec supports `database` (with worker or cron fallback) and
        // `sync` as a degraded-but-working mode. Anything else is a
        // misconfiguration for this application.
        if (! in_array($connection, ['database', 'sync'], true)) {
            throw new RuntimeException(
                "Health check failed: unsupported queue connection [{$connection}]; expected database or sync."
            );
        }

        if ($connection === 'database' && ! DB::getSchemaBuilder()->hasTable('jobs')) {
            throw new RuntimeException('Health check failed: queue is set to database but the jobs table is missing.');
        }
    }
}
