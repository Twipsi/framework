<?php

namespace Twipsi\Components\Database\Language;

use Twipsi\Foundation\Exceptions\NotSupportedException;

class SQLiteLanguage extends Language
{
    /**
     * All the available operators.
     *
     * @var string[]
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '|', '<<', '>>',
    ];

    /**
     * Get the statement scheme map to build.
     *
     * @param string $statement
     * @return string
     * @throws NotSupportedException
     */
    public function getScheme(string $statement): string
    {
        return match ($statement) {
            'insert' => /** @lang text */ "insert into {table} {insertColumns} {values}",
            'ignore' => /** @lang text */ "insert or ignore into {table} {insertColumns} {values}",
            'upsert' => /** @lang text */ "insert into {table} {insertColumns} {values} on conflict () do update set {updateColumns}",
            'select' => /** @lang text */ "select {aggregate} {columns} from {table} {joins} {where} {groups} {havings} {orders} {limit} {offset}",
            'update' => /** @lang text */ "update {table} {join} set {updateColumns} {where} {orders} {limit}",
            'delete' => /** @lang text */ "delete {alias} from {table} {join} {where} {orders} {limit}",
            default => throw new NotSupportedException(sprintf("Statement '%s' not supported", $statement)),
        };
    }
}