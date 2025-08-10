<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix users table
        if (Schema::hasTable('users')) {
            // Check if id column exists but is not auto-increment
            $userIdColumn = DB::select("SHOW COLUMNS FROM users WHERE Field = 'id'");
            if (!empty($userIdColumn) && strpos($userIdColumn[0]->Extra, 'auto_increment') === false) {
                // First check if there's a primary key
                $primaryKey = DB::select("SHOW KEYS FROM users WHERE Key_name = 'PRIMARY'");

                // Drop and recreate the id column as auto-increment
                Schema::table('users', function (Blueprint $table) use ($primaryKey) {
                    // First, drop any foreign keys that reference users.id
                    $foreignKeys = DB::select("
                        SELECT TABLE_NAME, CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                        AND REFERENCED_TABLE_NAME = 'users'
                        AND REFERENCED_COLUMN_NAME = 'id'
                    ");

                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }

                    // If there's a primary key, drop it first
                    if (!empty($primaryKey)) {
                        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
                    }

                    // Now modify the id column
                    DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                });
            }
        }

        // Fix roles table
        if (Schema::hasTable('roles')) {
            // Check if id column exists but is not auto-increment
            $roleIdColumn = DB::select("SHOW COLUMNS FROM roles WHERE Field = 'id'");
            if (!empty($roleIdColumn) && strpos($roleIdColumn[0]->Extra, 'auto_increment') === false) {
                // First check if there's a primary key
                $primaryKey = DB::select("SHOW KEYS FROM roles WHERE Key_name = 'PRIMARY'");

                // Drop and recreate the id column as auto-increment
                Schema::table('roles', function (Blueprint $table) use ($primaryKey) {
                    // First, drop any foreign keys that reference roles.id
                    $foreignKeys = DB::select("
                        SELECT TABLE_NAME, CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                        AND REFERENCED_TABLE_NAME = 'roles'
                        AND REFERENCED_COLUMN_NAME = 'id'
                    ");

                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }

                    // If there's a primary key, drop it first
                    if (!empty($primaryKey)) {
                        DB::statement('ALTER TABLE roles DROP PRIMARY KEY');
                    }

                    // Now modify the id column
                    DB::statement('ALTER TABLE roles MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it would break the database
    }
};
