<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\LendBooksService;
use App\Application\Services\LoanQueryService;
use App\Application\Services\OverdueLoansService;
use App\Application\Services\ReturnItemService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoanController
{
    private LendBooksService $lendBooksService;
    private ReturnItemService $returnItemService;
    private LoanQueryService $loanQueryService;
    private OverdueLoansService $overdueLoansService;

    public function __construct(
        LendBooksService $lendBooksService,
        ReturnItemService $returnItemService,
        LoanQueryService $loanQueryService,
        OverdueLoansService $overdueLoansService
    ) {
        $this->lendBooksService = $lendBooksService;
        $this->returnItemService = $returnItemService;
        $this->loanQueryService = $loanQueryService;
        $this->overdueLoansService = $overdueLoansService;
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user', []);
        $userId = $authUser['sub'] ?? '';

        $memberId = (string)$request->input('member_id', '');
        $items = (array)$request->input('items', []);
        $loanedAt = $request->input('loaned_at');

        try {
            $loan = $this->lendBooksService->execute($memberId, $userId, $items, $loanedAt);
            return new JsonResponse($loan, 201);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');
        $memberId = $request->query('member_id');
        $page = (int)$request->query('page', 1);
        $size = (int)$request->query('size', 20);

        $result = $this->loanQueryService->listLoans($from, $to, $status, $memberId, $page, $size);
        return new JsonResponse($result, 200);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $loan = $this->loanQueryService->getLoanById($id);
            return new JsonResponse($loan, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        }
    }

    public function returnItem(string $loanId, string $itemId): JsonResponse
    {
        try {
            $item = $this->returnItemService->execute($loanId, $itemId);
            return new JsonResponse($item, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function overdue(): JsonResponse
    {
        $result = $this->overdueLoansService->execute();
        return new JsonResponse($result, 200);
    }
}
