<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Column sorting for list pages driven by ?sort=&dir=, rendered by <x-sort-th>.
 * Only whitelisted keys are accepted; the resolved key and direction are stored on the request for the headers.
 */
class Sort
{
    /**
     * @param  array<string, string|Closure(Builder, string): mixed>  $columns  key => column, or closure ordering the query
     */
    public static function apply(Builder $query, Request $request, array $columns, string $default, string $defaultDir = 'desc'): Builder
    {
        $sort = array_key_exists((string) $request->query('sort'), $columns) ? (string) $request->query('sort') : $default;
        $dir = in_array($request->query('dir'), ['asc', 'desc'], true) && $sort === $request->query('sort') ? $request->query('dir') : $defaultDir;

        $request->attributes->set('sort', $sort);
        $request->attributes->set('dir', $dir);

        $column = $columns[$sort];
        $column instanceof Closure ? $column($query, $dir) : $query->orderBy($column, $dir);

        return $query->orderBy($query->getModel()->getQualifiedKeyName(), $dir);
    }

    /**
     * Orders by a column of a belongs-to relation (lab name, operator…).
     */
    public static function byRelated(string $table, string $expression, string $foreignKey): Closure
    {
        return fn (Builder $query, string $dir) => $query->orderBy(
            DB::table($table)->selectRaw($expression)->whereColumn("{$table}.id", $foreignKey),
            $dir
        );
    }

    public static function byUser(string $foreignKey): Closure
    {
        return self::byRelated('users', "CONCAT_WS(' ', first_name, last_name)", $foreignKey);
    }
}
