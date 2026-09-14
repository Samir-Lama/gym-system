<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $data): Model;

    public function update(Model $model, array $data): Model;

    public function delete(Model $model): void;

    public function find(int $id): ?Model;

    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator;

    public function deleteByIds(array $ids): int;
}
