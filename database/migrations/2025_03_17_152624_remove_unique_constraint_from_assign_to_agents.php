<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            // Just add the foreign key if it doesn't exist
            if (!Schema::hasColumn('assign_to_agents', 'allocation_point_id')) {
                $table->unsignedBigInteger('allocation_point_id')->nullable();
            }
            
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('assign_to_agents');
            $exists = false;
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['allocation_point_id']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $table->foreign('allocation_point_id')
                    ->references('id')
                    ->on('allocation_points')
                    ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        // No need for down migration as we're just ensuring structure
    }
};
