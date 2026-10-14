<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\Ports\CategoryRepositoryInterface;
use Illuminate\Http\JsonResponse;

final class CategoryController
{
    private CategoryRepositoryInterface $catRepo;

    public function __construct(CategoryRepositoryInterface $catRepo)
    {
        $this->catRepo = $catRepo;
    }

    public function index(): JsonResponse
    {
        $categories = $this->catRepo->findAll();
        $data = array_map(fn($c) => $c->toArray(), $categories);

        return new JsonResponse($data, 200);
    }
}
