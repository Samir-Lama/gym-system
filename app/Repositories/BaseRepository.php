<?php

namespace App\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected Model $model
    ) {}

    public function create(array $data): Model
    {
        return $this->model
            ->newQuery()
            ->create($data);
    }

    public function update(
        Model $model,
        array $data
    ): Model {
        $model->update($data);

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    public function find(int $id): ?Model
    {
        return $this->model
            ->newQuery()
            ->find($id);
    }

    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->newQuery()
            ->latest()
            ->paginate($perPage);
    }

    public function deleteByIds(array $ids): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->delete();
    }
}
