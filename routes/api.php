<?php

use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\Controllers\BookController;
use App\Adapters\Inbound\Http\Controllers\CategoryController;
use App\Adapters\Inbound\Http\Controllers\HealthController;
use App\Adapters\Inbound\Http\Controllers\LoanController;
use App\Adapters\Inbound\Http\Controllers\MemberController;
use App\Adapters\Inbound\Http\Controllers\ReportController;
use App\Adapters\Inbound\Http\Controllers\UserController;
use Illuminate\Routing\Router;

/** @var Router $router */

$router->group(['prefix' => 'api/v1'], function (Router $r) {
    // 3.1 Salud y Autenticación pública
    $r->get('health', [HealthController::class, '__invoke']);
    $r->post('auth/login', [AuthController::class, 'login']);

    // 3.2 Usuarios (solo admin)
    $r->post('users', [UserController::class, 'createLibrarian']);

    // 3.3 Categorías
    $r->get('categories', [CategoryController::class, 'index']);

    // 3.4 Libros
    $r->get('books', [BookController::class, 'index']);
    $r->post('books', [BookController::class, 'store']);
    $r->get('books/{id}/cover', [BookController::class, 'getCover']);
    $r->put('books/{id}/cover', [BookController::class, 'uploadCover']);
    $r->delete('books/{id}/cover', [BookController::class, 'deleteCover']);
    $r->get('books/{id}', [BookController::class, 'show']);
    $r->put('books/{id}', [BookController::class, 'update']);
    $r->delete('books/{id}', [BookController::class, 'destroy']);

    // 3.5 Socios
    $r->get('members', [MemberController::class, 'index']);
    $r->get('members/{id}', [MemberController::class, 'show']);
    $r->post('members', [MemberController::class, 'store']);

    // 3.6 Préstamos
    $r->post('loans', [LoanController::class, 'store']);
    $r->get('loans', [LoanController::class, 'index']);
    $r->get('loans/overdue', [LoanController::class, 'overdue']); // Debe ir antes de loans/{id}
    $r->get('loans/{id}', [LoanController::class, 'show']);
    $r->post('loans/{loan_id}/items/{item_id}/return', [LoanController::class, 'returnItem']);

    // 3.7 Reporte (solo admin)
    $r->get('reports/most-borrowed', [ReportController::class, 'mostBorrowed']);
});
