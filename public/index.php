<?php

if (php_sapi_name() === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($urlPath !== '/' && file_exists(__DIR__ . $urlPath)) {
        return false;
    }
}

$bootstrap = require __DIR__ . '/../bootstrap/app.php';

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/** @var \Illuminate\Container\Container $container */
$container = $bootstrap['container'];
/** @var \Illuminate\Routing\Router $router */
$router = $bootstrap['router'];
/** @var \App\Adapters\Inbound\Http\Middleware\CorsMiddleware $corsMiddleware */
$corsMiddleware = $bootstrap['corsMiddleware'];
/** @var \App\Adapters\Inbound\Http\Middleware\AuthMiddleware $authMiddleware */
$authMiddleware = $bootstrap['authMiddleware'];

require __DIR__ . '/../routes/api.php';

$request = Request::capture();
$contentType = $request->header('Content-Type', '');
if (str_contains($contentType, 'json') || $request->isJson()) {
    $decoded = json_decode($request->getContent(), true);
    if (is_array($decoded)) {
        $request->request->replace($decoded);
    }
}

$container->instance('request', $request);
$container->instance(Request::class, $request);

// Manejo de petición a través de CORS y Enrutamiento
$response = $corsMiddleware->handle($request, function ($req) use ($router, $authMiddleware) {
    if ($req->isMethod('OPTIONS')) {
        return response('', 204);
    }

    $path = trim($req->getPathInfo(), '/');
    $method = $req->getMethod();

    // Rutas públicas que no requieren token
    $publicRoutes = [
        'api/v1/health',
        'api/v1/auth/login',
    ];

    // GET /api/v1/books/{id}/cover también puede ser visible para el navegador
    $isPublicCover = ($method === 'GET' && preg_match('#^api/v1/books/[^/]+/cover$#', $path));

    $isPublic = in_array($path, $publicRoutes, true) || $isPublicCover;

    // Rutas exclusivas para admin (CA-01.3 & Contrato §3)
    $adminOnly = (
        ($method === 'POST' && $path === 'api/v1/users') ||
        ($method === 'POST' && $path === 'api/v1/books') ||
        ($method === 'PUT' && preg_match('#^api/v1/books/[^/]+$#', $path)) ||
        ($method === 'DELETE' && preg_match('#^api/v1/books/[^/]+$#', $path)) ||
        ($method === 'PUT' && preg_match('#^api/v1/books/[^/]+/cover$#', $path)) ||
        ($method === 'DELETE' && preg_match('#^api/v1/books/[^/]+/cover$#', $path)) ||
        ($method === 'GET' && $path === 'api/v1/reports/most-borrowed')
    );

    if (!$isPublic) {
        $requiredRole = $adminOnly ? 'admin' : null;
        $authResponse = $authMiddleware->handle($req, function ($authedReq) use ($router) {
            return $router->dispatch($authedReq);
        }, $requiredRole);

        return $authResponse;
    }

    return $router->dispatch($req);
});

// Manejo de errores de ruta
if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
    $response->send();
}
