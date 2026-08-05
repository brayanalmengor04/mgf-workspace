<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateDoctorCommand extends Command
{
    protected $signature = 'mgf:migrate-doctor';

    protected $description = 'Verifica que el esquema de BD coincida con el baseline esperado';

    /**
     * @var list<string>
     */
    private array $expectedTables = [
        'activity_log',
        'budget_item_templates',
        'budget_plan_items',
        'budget_plans',
        'cache',
        'cache_locks',
        'calendar_events',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'quote_items',
        'quote_templates',
        'quotes',
        'sessions',
        'users',
    ];

    public function handle(): int
    {
        $ok = true;

        $existingTables = collect(Schema::getTableListing())
            ->map(fn (string $table): string => str_contains($table, '.')
                ? substr($table, strrpos($table, '.') + 1)
                : $table)
            ->values();

        $missing = collect($this->expectedTables)
            ->diff($existingTables)
            ->values();

        $extra = $existingTables
            ->diff($this->expectedTables)
            ->reject(fn (string $table): bool => str_starts_with($table, 'sqlite_'))
            ->values();

        if ($missing->isNotEmpty()) {
            $ok = false;
            $this->error('Tablas faltantes: '.$missing->implode(', '));
        }

        if ($extra->isNotEmpty()) {
            $this->warn('Tablas extra (no bloquean): '.$extra->implode(', '));
        }

        $migrationFiles = count(glob(database_path('migrations/*.php')) ?: []);
        $migrationRows = (int) DB::table('migrations')->count();

        $this->line("Archivos de migración: {$migrationFiles}");
        $this->line("Registros en migrations: {$migrationRows}");

        if ($migrationFiles === 0 && $migrationRows > 0) {
            $this->info('Estado post-squash detectado: registros históricos sin archivos PHP (OK en producción).');
        } elseif ($migrationFiles > 0 && $migrationRows === 0) {
            $this->warn('BD vacía con archivos de migración pendientes (instalación nueva).');
        } elseif ($migrationFiles === 0 && file_exists(database_path('schema/mysql-schema.sql'))) {
            $this->info('Baseline schema dump presente.');
        }

        if ($ok) {
            $this->info('mgf:migrate-doctor — esquema OK');

            return self::SUCCESS;
        }

        $this->error('mgf:migrate-doctor — esquema incompleto');

        return self::FAILURE;
    }
}
