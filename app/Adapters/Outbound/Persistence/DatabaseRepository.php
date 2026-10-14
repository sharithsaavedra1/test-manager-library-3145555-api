<?php

namespace App\Adapters\Outbound\Persistence;

use Illuminate\Support\Facades\DB;

abstract class DatabaseRepository
{
    protected function table(string $tableName)
    {
        $driver = DB::connection()->getDriverName();
        $schema = env('DB_SCHEMA', 'library');
        $fullTable = ($driver === 'pgsql') ? "{$schema}.{$tableName}" : $tableName;

        return DB::table($fullTable);
    }
}
