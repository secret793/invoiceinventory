<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add auto-increment to assign_to_agents id column
        DB::statement('ALTER TABLE assign_to_agents MODIFY id INT AUTO_INCREMENT');
    }

    public function down(): void
    {
        //
    }
};
