<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\CategoryRepositoryInterface;
use App\Domain\Model\Category;

final class CategoryRepository extends DatabaseRepository implements CategoryRepositoryInterface
{
    public function findAll(): array
    {
        $rows = $this->table('category')->orderBy('name', 'asc')->get();
        return $rows->map(fn($r) => new Category($r->id, $r->name))->all();
    }

    public function findById(string $id): ?Category
    {
        $row = $this->table('category')->where('id', $id)->first();
        if (!$row) {
            return null;
        }
        return new Category($row->id, $row->name);
    }

    public function save(Category $category): void
    {
        $this->table('category')->updateOrInsert(
            ['id' => $category->getId()],
            ['name' => $category->getName()]
        );
    }
}
