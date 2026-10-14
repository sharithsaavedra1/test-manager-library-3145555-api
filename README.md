# Backend API — Manager Library (PHP / Laravel)

Backend de *Manager Library* implementado bajo principios innegociables de la Constitución y arquitectura hexagonal (Puertos y Adaptadores).

## Arquitectura y Principios de Diseño

1. **Dominio Puro (Artículo I):**
   - Modelos: `Book`, `Category`, `Member`, `User`, `Loan`, `LoanItem`.
   - Value Objects: `Isbn`, `Quantity`, `DateRange`.
   - Excepciones de negocio con códigos mapeados a `api-contract.md`.

2. **Puertos y Adaptadores (Artículos II y III):**
   - Puertos de Salida: Repositorios, `TokenIssuerInterface`, `PasswordHasherInterface`, `CoverStorageInterface`.
   - Adaptadores: Implementaciones en PostgreSQL 16 (esquema `library`), Argon2id/Bcrypt y almacenamiento local.
   - Bootstrap (`bootstrap/app.php`): Único punto de composición.

3. **Invariantes del Motor (Artículos V, VI y VII):**
   - Tablas en singular (`category`, `book`, `member`, `loan`, `loan_item`, `user`).
   - Restricciones CHECK: `ck_book_available_range`, `ck_book_total_positive`, `ck_user_role`.
   - Índices únicos parciales (`isbn` activo).
   - El estado del préstamo (`open`, `overdue`, `closed`) se calcula dinámicamente y no se persiste.
   - Las líneas de préstamo congelan `book_title`, `isbn` y `category_name` en el instante del préstamo (Artículo VIII).

## Instrucciones de Ejecución

1. Configurar variables de entorno:
   ```bash
   cp .env.example .env
   ```

2. Ejecutar migraciones (creación de tablas, restricciones y semilla de categorías):
   ```bash
   php artisan migrate
   ```

3. Sembrar usuario administrador inicial desde variables de entorno:
   ```bash
   php artisan seed:admin
   ```

4. Iniciar el servidor local:
   ```bash
   php artisan serve
   ```
   El backend estará disponible en `http://127.0.0.1:8000/api/v1`.
