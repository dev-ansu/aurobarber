<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait UsesPublicSchema
{
    public function getConnectionName(): ?string
    {
        return config('database.default') === 'pgsql' ? 'pgsql_public' : parent::getConnectionName();
    }
}
