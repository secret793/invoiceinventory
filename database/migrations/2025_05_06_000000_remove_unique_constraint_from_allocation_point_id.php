<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Schema\Index;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if the unique constraint exists
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('assign_to_agents');

        foreach ($indexes as $index) {
            // Check if this is a unique index on allocation_point_id
            if ($index->isUnique() && in_array('allocation_point_id', $index->getColumns())) {
                // Drop the index
                Schema::table('assign_to_agents', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index->getName());
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to add the unique constraint back in the down method
        // as it would likely cause issues with existing data
    }
};

