<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Get current enum values
        $currentEnumValues = DB::select(DB::raw("SHOW COLUMNS FROM devices WHERE Field = 'status'"))[0]->Type;
        
        // Parse the enum values
        preg_match("/^enum\((.*)\)$/", $currentEnumValues, $matches);
        $values = str_getcsv($matches[1], ",", "'");
        
        // Add 'REJECTED' if it doesn't exist
        if (!in_array('REJECTED', $values)) {
            $values[] = 'REJECTED';
            
            // Create new enum string
            $enumString = "'" . implode("','", $values) . "'";
            
            // Modify the column
            DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM($enumString) NOT NULL DEFAULT 'ONLINE'");
        }

        // Add rejected_at timestamp if it doesn't exist
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
        });
    }

    public function down()
    {
        // Remove rejected_at column
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });

        // Get current enum values
        $currentEnumValues = DB::select(DB::raw("SHOW COLUMNS FROM devices WHERE Field = 'status'"))[0]->Type;
        
        // Parse the enum values
        preg_match("/^enum\((.*)\)$/", $currentEnumValues, $matches);
        $values = str_getcsv($matches[1], ",", "'");
        
        // Remove 'REJECTED'
        $values = array_filter($values, fn($value) => $value !== 'REJECTED');
        
        // Create new enum string
        $enumString = "'" . implode("','", $values) . "'";
        
        // Modify the column
        DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM($enumString) NOT NULL DEFAULT 'ONLINE'");
    }
}; 