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
        $currentEnumValues = DB::select(DB::raw("SHOW COLUMNS FROM device_retrievals WHERE Field = 'retrieval_status'"))[0]->Type;
        
        // Parse the enum values
        preg_match("/^enum\((.*)\)$/", $currentEnumValues, $matches);
        $values = str_getcsv($matches[1], ",", "'");
        
        // Add 'RETURNED' if it doesn't exist
        if (!in_array('RETURNED', $values)) {
            $values[] = 'RETURNED';
            
            // Create new enum string
            $enumString = "'" . implode("','", $values) . "'";
            
            // Modify the column
            DB::statement("ALTER TABLE device_retrievals MODIFY COLUMN retrieval_status ENUM($enumString) NOT NULL DEFAULT 'NOT_RETRIEVED'");
        }
    }

    public function down()
    {
        // First update any RETURNED status to NOT_RETRIEVED
        DB::statement("
            UPDATE device_retrievals 
            SET retrieval_status = 'NOT_RETRIEVED' 
            WHERE retrieval_status = 'RETURNED'
        ");

        // Get current enum values
        $currentEnumValues = DB::select(DB::raw("SHOW COLUMNS FROM device_retrievals WHERE Field = 'retrieval_status'"))[0]->Type;
        
        // Parse the enum values
        preg_match("/^enum\((.*)\)$/", $currentEnumValues, $matches);
        $values = str_getcsv($matches[1], ",", "'");
        
        // Remove 'RETURNED'
        $values = array_filter($values, fn($value) => $value !== 'RETURNED');
        
        // Create new enum string
        $enumString = "'" . implode("','", $values) . "'";
        
        // Modify the column
        DB::statement("ALTER TABLE device_retrievals MODIFY COLUMN retrieval_status ENUM($enumString) NOT NULL DEFAULT 'NOT_RETRIEVED'");
    }
}; 