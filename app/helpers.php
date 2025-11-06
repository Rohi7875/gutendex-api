<?php

use Illuminate\Support\Facades\Schema;

if (!function_exists('schema_has_column')) {
    function schema_has_column($table, $column)
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Exception $e) {
            return false;
        }
    }
}
