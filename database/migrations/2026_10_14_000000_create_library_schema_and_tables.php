<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $prefix = ($driver === 'pgsql') ? 'library.' : '';

        if ($driver === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS library');
        }

        // 1. category
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}category (
            id UUID PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE
        )");

        // 2. book
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}book (
            id UUID PRIMARY KEY,
            title VARCHAR(250) NOT NULL,
            author VARCHAR(200) NOT NULL,
            isbn CHAR(13) NOT NULL,
            category_id UUID NOT NULL REFERENCES {$prefix}category(id) ON DELETE RESTRICT,
            total_copies INTEGER NOT NULL,
            available_copies INTEGER NOT NULL,
            cover_key VARCHAR(512) NULL,
            deleted_at TIMESTAMPTZ NULL,
            CONSTRAINT ck_book_available_range CHECK (0 <= available_copies AND available_copies <= total_copies),
            CONSTRAINT ck_book_total_positive CHECK (total_copies >= 1)
        )");

        // Índice único parcial para ISBN sobre activos (ADR-003)
        if ($driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uq_book_isbn_active ON {$prefix}book (isbn) WHERE deleted_at IS NULL");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_book_cat_title ON {$prefix}book (category_id, title) WHERE deleted_at IS NULL");
        } else {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uq_book_isbn_active ON {$prefix}book (isbn, deleted_at)");
        }

        // 3. member
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}member (
            id UUID PRIMARY KEY,
            full_name VARCHAR(200) NOT NULL,
            document_number VARCHAR(30) NOT NULL UNIQUE
        )");

        // 4. user
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}\"user\" (
            id UUID PRIMARY KEY,
            username VARCHAR(120) NOT NULL UNIQUE,
            password_hash VARCHAR(512) NOT NULL,
            role VARCHAR(40) NOT NULL,
            CONSTRAINT ck_user_role CHECK (role IN ('admin', 'librarian'))
        )");

        // 5. loan
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}loan (
            id UUID PRIMARY KEY,
            member_id UUID NOT NULL REFERENCES {$prefix}member(id) ON DELETE RESTRICT,
            loaned_by_user_id UUID NOT NULL REFERENCES {$prefix}\"user\"(id) ON DELETE RESTRICT,
            loaned_at TIMESTAMPTZ NOT NULL
        )");

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_loan_loaned_at ON {$prefix}loan (loaned_at)");
        }

        // 6. loan_item
        DB::statement("CREATE TABLE IF NOT EXISTS {$prefix}loan_item (
            id UUID PRIMARY KEY,
            loan_id UUID NOT NULL REFERENCES {$prefix}loan(id) ON DELETE CASCADE,
            book_id UUID NOT NULL REFERENCES {$prefix}book(id) ON DELETE RESTRICT,
            book_title VARCHAR(250) NOT NULL,
            isbn CHAR(13) NOT NULL,
            category_name VARCHAR(120) NOT NULL,
            due_at TIMESTAMPTZ NOT NULL,
            returned_at TIMESTAMPTZ NULL,
            CONSTRAINT uq_loan_book UNIQUE (loan_id, book_id)
        )");

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_loan_item_due_unreturned ON {$prefix}loan_item (due_at) WHERE returned_at IS NULL");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_loan_item_book ON {$prefix}loan_item (book_id)");
        }

        // Semilla de las cinco categorías fijas (§9 data-model.md)
        $categories = [
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Novela'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Ciencia'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Tecnología'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Historia'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Infantil'],
        ];

        foreach ($categories as $cat) {
            DB::table("{$prefix}category")->updateOrInsert(['id' => $cat['id']], ['name' => $cat['name']]);
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        $prefix = ($driver === 'pgsql') ? 'library.' : '';

        DB::statement("DROP TABLE IF EXISTS {$prefix}loan_item CASCADE");
        DB::statement("DROP TABLE IF EXISTS {$prefix}loan CASCADE");
        DB::statement("DROP TABLE IF EXISTS {$prefix}\"user\" CASCADE");
        DB::statement("DROP TABLE IF EXISTS {$prefix}member CASCADE");
        DB::statement("DROP TABLE IF EXISTS {$prefix}book CASCADE");
        DB::statement("DROP TABLE IF EXISTS {$prefix}category CASCADE");
    }
};
