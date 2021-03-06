<?php

use Illuminate\Database\Eloquent\Builder;

trait SoftDeletesRepository
{
    public function restore(int $id): bool
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->restore();
    }

    public function forceDelete(int $id): bool
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->forceDelete();
    }

    public function onlyTrash(): self
    {
        $this->scope(fn (Builder $builder) => $builder->onlyTrash());

        return $this;
    }

    public function withTrash(): self
    {
        $this->scope(fn (Builder $builder) => $builder->withTrash());

        return $this;
    }
}
