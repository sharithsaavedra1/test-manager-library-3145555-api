<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\Controllers\BookController;
use App\Adapters\Inbound\Http\Controllers\CategoryController;
use App\Adapters\Inbound\Http\Controllers\HealthController;
use App\Adapters\Inbound\Http\Controllers\MemberController;
use App\Adapters\Inbound\Http\Controllers\LoanController;
use App\Adapters\Inbound\Http\Controllers\ReportController;
use App\Adapters\Inbound\Http\Controllers\UserController;
use App\Adapters\Inbound\Http\Middleware\AuthMiddleware;
use App\Adapters\Inbound\Http\Middleware\CorsMiddleware;
use App\Adapters\Outbound\Persistence\BookRepository;
use App\Adapters\Outbound\Persistence\CategoryRepository;
use App\Adapters\Outbound\Persistence\LoanRepository;
use App\Adapters\Outbound\Persistence\MemberRepository;
use App\Adapters\Outbound\Persistence\ReportReader;
use App\Adapters\Outbound\Persistence\UserRepository;
use App\Adapters\Outbound\Security\ArgonPasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Adapters\Outbound\Storage\LocalCoverStorage;
use App\Application\Ports\BookRepositoryInterface;
use App\Application\Ports\CategoryRepositoryInterface;
use App\Application\Ports\CoverStorageInterface;
use App\Application\Ports\LoanRepositoryInterface;
use App\Application\Ports\MemberRepositoryInterface;
use App\Application\Ports\PasswordHasherInterface;
use App\Application\Ports\ReportReaderInterface;
use App\Application\Ports\TokenIssuerInterface;
use App\Application\Ports\UserRepositoryInterface;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

// 1. Cargar variables de entorno si existe .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $_ENV[$k] = $v;
            putenv("{$k}={$v}");
        }
    }
}

// 2. Contenedor de Inversión de Control
$container = Container::getInstance();
Container::setInstance($container);

// 3. Inicialización de Base de Datos
$capsule = new Capsule($container);
$dbConnection = env('DB_CONNECTION', 'pgsql');
$dbHost = env('DB_HOST', '127.0.0.1');
$dbPort = env('DB_PORT', '5432');
$dbDatabase = env('DB_DATABASE', 'library');
$dbUsername = env('DB_USERNAME', 'library');
$dbPassword = env('DB_PASSWORD', 'library');
$dbSchema = env('DB_SCHEMA', 'library');

if ($dbConnection === 'sqlite') {
    if (!file_exists($dbDatabase)) {
        $dir = dirname($dbDatabase);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @touch($dbDatabase);
    }
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => $dbDatabase,
        'prefix' => '',
    ]);
}
 else {
    $capsule->addConnection([
        'driver' => 'pgsql',
        'host' => $dbHost,
        'port' => $dbPort,
        'database' => $dbDatabase,
        'username' => $dbUsername,
        'password' => $dbPassword,
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => $dbSchema,
        'sslmode' => 'prefer',
    ]);
}

$capsule->setEventDispatcher(new Dispatcher($container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container->instance('db', $capsule->getDatabaseManager());
\Illuminate\Support\Facades\Facade::setFacadeApplication($container);

// 4. Artículo III: Vinculación única de Puertos con Adaptadores
$jwtSecret = env('JWT_SECRET', 'library_default_secret_jwt_key_3145555');
$coversDir = env('COVERS_DIR', __DIR__ . '/../storage/app/covers');

$container->singleton(PasswordHasherInterface::class, ArgonPasswordHasher::class);
$container->singleton(TokenIssuerInterface::class, fn() => new JwtTokenIssuer($jwtSecret));
$container->singleton(CoverStorageInterface::class, fn() => new LocalCoverStorage($coversDir));

$container->singleton(CategoryRepositoryInterface::class, CategoryRepository::class);
$container->singleton(BookRepositoryInterface::class, BookRepository::class);
$container->singleton(MemberRepositoryInterface::class, MemberRepository::class);
$container->singleton(UserRepositoryInterface::class, UserRepository::class);
$container->singleton(LoanRepositoryInterface::class, LoanRepository::class);
$container->singleton(ReportReaderInterface::class, ReportReader::class);

// 5. Configuración del Router
$events = new Dispatcher($container);
$router = new Router($events, $container);

// Middleware instances
$corsMiddleware = new CorsMiddleware();
$authMiddleware = new AuthMiddleware($container->make(TokenIssuerInterface::class));

$container->instance(Router::class, $router);
$container->instance('router', $router);

return [
    'container' => $container,
    'router' => $router,
    'capsule' => $capsule,
    'corsMiddleware' => $corsMiddleware,
    'authMiddleware' => $authMiddleware,
];
