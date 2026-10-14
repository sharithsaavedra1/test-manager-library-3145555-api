<?php

namespace App\Application\Ports;

use App\Domain\Model\Category;

interface CategoryRepositoryInterface
{
    /** @return Category[] */
    public function findAll(): array;
    public function findById(string $id): ?Category;
    public function save(Category $category): void;
}
