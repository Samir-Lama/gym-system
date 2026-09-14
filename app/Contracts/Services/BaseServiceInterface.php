<?php

namespace App\Contracts\Services;

use Illuminate\Database\Eloquent\Model;

interface BaseServiceInterface
{
    public function delete(Model $model): void;

    public function bulkDelete(array $ids): int;
}
