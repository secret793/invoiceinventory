<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add auto-increment to confirmed_affixeds id column
        DB::statement('ALTER TABLE confirmed_affixeds MODIFY id INT AUTO_INCREMENT');
    }

    public function down(): void
    {
        //
    }
};
