<?php

namespace App\Services;

use App\Contracts\Repositories\BaseRepositoryInterface;
use App\Contracts\Services\BaseServiceInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService implements BaseServiceInterface
{
    public function __construct(
        protected BaseRepositoryInterface $repository
    ) {}

    public function delete(Model $model): void
    {
        $this->repository->delete($model);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }
}
