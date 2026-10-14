<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\MostBorrowedReportService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReportController
{
    private MostBorrowedReportService $reportService;

    public function __construct(MostBorrowedReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function mostBorrowed(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if (!$from || !$to) {
            return ErrorResponse::create('VALIDATION_ERROR', 'Los parámetros from y to son obligatorios.', 422, [
                ['field' => 'dates', 'issue' => 'parámetros from y to requeridos']
            ]);
        }

        try {
            $report = $this->reportService->execute($from, $to);
            return new JsonResponse($report, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }
}
