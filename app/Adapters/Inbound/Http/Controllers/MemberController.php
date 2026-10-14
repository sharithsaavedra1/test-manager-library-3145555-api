<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\MemberService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MemberController
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->query('q');
        $page = (int)$request->query('page', 1);
        $size = (int)$request->query('size', 20);

        $result = $this->memberService->searchMembers($query, $page, $size);
        return new JsonResponse($result, 200);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $member = $this->memberService->getMemberById($id);
            return new JsonResponse($member, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $member = $this->memberService->createMember(
                (string)$request->input('full_name', ''),
                (string)$request->input('document_number', '')
            );
            return new JsonResponse($member, 201);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }
}
