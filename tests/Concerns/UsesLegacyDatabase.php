<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * The "legacy" connection points at the test database. Its tables are created from the real
 * schema of the old system (tests/Fixtures/legacy_schema.sql, structure only) and emptied before each test.
 */
trait UsesLegacyDatabase
{
    protected function setUpLegacyDatabase(): void
    {
        $legacy = DB::connection('legacy');
        $schema = (string) file_get_contents(base_path('tests/Fixtures/legacy_schema.sql'));

        foreach (array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $schema)))) as $statement) {
            $legacy->statement($statement);
            preg_match('/`([a-z_]+)`/', $statement, $table);
            $legacy->table($table[1])->delete();
        }
    }

    /**
     * Insert a legacy row; columns left out get MySQL's implicit defaults (the connection is not strict).
     *
     * @param  array<string, mixed>  $values
     */
    protected function legacyRow(string $table, array $values): void
    {
        DB::connection('legacy')->table($table)->insert($values);
    }
}
