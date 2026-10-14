<?php

namespace App\Adapters\Inbound\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        $allowedOrigin = $origin ?: '*';

        if ($request->isMethod('OPTIONS')) {
            $resp = new Response('', 204);
            $resp->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $resp->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $resp->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            if ($origin) {
                $resp->headers->set('Access-Control-Allow-Credentials', 'true');
            }
            return $resp;
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            if ($origin) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $response;
    }
}
