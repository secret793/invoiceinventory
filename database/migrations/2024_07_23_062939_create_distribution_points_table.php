<?php

// database/migrations/xxxx_xx_xx_create_distribution_points_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionPointsTable extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // Distribution point name
            $table->string('location')->nullable();  // Optional location field
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_points');
    }
}
