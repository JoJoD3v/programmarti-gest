<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Restores the production data from a phpMyAdmin dump into a freshly migrated MySQL database.
 *
 * Usage: put the dump at database/data/production.sql (gitignored), then
 * php artisan migrate:fresh --force && php artisan db:seed --class=ProductionDataSeeder --force
 */
class ProductionDataSeeder extends Seeder
{
    /**
     * Tables restored from the dump; runtime tables (cache, sessions, jobs, migrations, reset tokens) are skipped.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',
        'clients', 'projects', 'payments', 'expenses', 'works', 'appointments',
        'preventivi', 'preventivo_items', 'notifications',
    ];

    public function run(): void
    {
        $path = database_path('data/production.sql');

        if (! is_file($path)) {
            throw new RuntimeException("Dump not found: {$path}");
        }

        if (DB::table('users')->exists()) {
            throw new RuntimeException('Database is not empty: run php artisan migrate:fresh first.');
        }

        $rows = [];

        Schema::withoutForeignKeyConstraints(function () use ($path, &$rows): void {
            foreach ($this->insertStatements($path) as $table => $statement) {
                DB::unprepared($statement);
                $rows[$table] = DB::table($table)->count();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($rows as $table => $count) {
            $this->command?->info("{$table}: {$count}");
        }
    }

    /**
     * Yields the dump's INSERT statements for the restored tables.
     * phpMyAdmin escapes newlines inside values, so every statement ends on a line ending with ");".
     *
     * @return \Generator<string, string>
     */
    private function insertStatements(string $path): \Generator
    {
        $table = null;
        $statement = '';

        foreach (new \SplFileObject($path) as $line) {
            if ($table === null) {
                if (preg_match('/^INSERT INTO `(\w+)`/', $line, $match) && in_array($match[1], self::TABLES, true)) {
                    $table = $match[1];
                } else {
                    continue;
                }
            }

            $statement .= $line;

            if (str_ends_with(rtrim($line), ');')) {
                yield $table => $statement;
                $table = null;
                $statement = '';
            }
        }
    }
}
