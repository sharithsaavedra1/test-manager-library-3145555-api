<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Domain\Exceptions\BusinessException;
use App\Domain\Model\User;
use App\Domain\Model\Book;
use App\Domain\Model\Loan;
use App\Domain\Model\LoanItem;
use App\Domain\Model\Member;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use Carbon\Carbon;

echo "=== CORRIENDO BATERÍA DE PRUEBAS DE EVIDENCIA SDD (Ficha 3145555) ===\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertCondition(bool $cond, string $message) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($cond) {
        $testsPassed++;
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        throw new Exception("Prueba fallida: {$message}");
    }
}

// ----------------------------------------------------
// HU-01: Iniciar Sesión y Usuarios
// ----------------------------------------------------
echo ">> Ejecutando pruebas para HU-01 y HU-02...\n";
$user = new User('u1', '  Ana  ', 'hash123', 'librarian');
assertCondition($user->getUsername() === 'ana', 'CA-02.2: El username se normaliza en minúsculas y sin espacios');
assertCondition($user->isLibrarian() && !$user->isAdmin(), 'CA-02.1: El usuario tiene rol librarian');

try {
    new User('u2', 'test', 'hash', 'invalid_role');
    assertCondition(false, 'Debe rechazar roles inválidos');
} catch (BusinessException $e) {
    assertCondition($e->getErrorCode() === 'VALIDATION_ERROR', 'Rechaza roles diferentes a admin o librarian');
}

// ----------------------------------------------------
// HU-03: Catálogo y Libros (ISBN 13 dígitos y copias)
// ----------------------------------------------------
echo "\n>> Ejecutando pruebas para HU-03 y HU-04...\n";
$isbn = new Isbn('9780307474728');
assertCondition((string)$isbn === '9780307474728', 'CA-03.2: ISBN válido de 13 dígitos');

try {
    new Isbn('12345');
    assertCondition(false, 'Debe fallar si ISBN no tiene 13 dígitos');
} catch (BusinessException $e) {
    assertCondition($e->getErrorCode() === 'VALIDATION_ERROR', 'Rechaza ISBN menor a 13 dígitos');
}

$book = new Book('b1', 'Cien años de soledad', 'Gabo', $isbn, 'c1', 4);
assertCondition($book->getAvailableCopies() === 4, 'CA-03.1: Disponibles arrancan iguales al total');

$book->withdraw();
assertCondition($book->getAvailableCopies() === 3, 'CA-06.3: Descuenta 1 disponible al prestar');

$book->restock();
assertCondition($book->getAvailableCopies() === 4, 'CA-07.1: Suma 1 disponible al devolver');

// ----------------------------------------------------
// HU-06: Préstamos (1 a 3 líneas, sin repetir, 14 días)
// ----------------------------------------------------
echo "\n>> Ejecutando pruebas para HU-06, HU-07 y HU-08...\n";
$now = Carbon::parse('2026-10-14T10:00:00Z');
$due = (clone $now)->addDays(14);

$item1 = new LoanItem('i1', 'l1', 'b1', 'Cien años de soledad', '9780307474728', 'Novela', $due);
$loan = new Loan('l1', 'm1', 'u1', $now, [$item1]);

assertCondition(count($loan->getItems()) === 1, 'CA-06.1: Préstamo creado con una línea');
assertCondition($loan->calculateStatus($now) === 'open', 'CA-08.1: Estado es open si no está vencido ni devuelto');

// Duplicado de libro en préstamo (CA-06.2)
try {
    $itemDuplicate = new LoanItem('i2', 'l1', 'b1', 'Cien años de soledad', '9780307474728', 'Novela', $due);
    $loan->addItem($itemDuplicate);
    assertCondition(false, 'No debe permitir libros repetidos');
} catch (BusinessException $e) {
    assertCondition($e->getErrorCode() === 'DUPLICATE_BOOK_IN_LOAN', 'CA-06.2: Rechaza libro repetido en el mismo préstamo (422)');
}

// Vencimiento a más de 14 días
$futureTime = (clone $now)->addDays(15);
assertCondition($item1->isOverdue($futureTime), 'CA-09.1: Detecta libro vencido cuando due_at < now');
assertCondition($loan->calculateStatus($futureTime) === 'overdue', 'CA-08.1: Estado cambia a overdue dinámicamente');

// Devolución
$item1->markAsReturned($futureTime);
assertCondition($item1->isReturned(), 'CA-07.1: Registra returned_at');
assertCondition($loan->calculateStatus($futureTime) === 'closed', 'CA-07.4: Estado cambia a closed cuando todas están devueltas');

try {
    $item1->markAsReturned($futureTime);
    assertCondition(false, 'No debe permitir devolver dos veces');
} catch (BusinessException $e) {
    assertCondition($e->getErrorCode() === 'ITEM_ALREADY_RETURNED', 'CA-07.2: Devolver dos veces responde 409 ITEM_ALREADY_RETURNED');
}

echo "\n=======================================================\n";
echo "RESULTADO: {$testsPassed} de {$totalTests} pruebas pasaron satisfactoriamente (100% ÉXITO).\n";
echo "=======================================================\n";
