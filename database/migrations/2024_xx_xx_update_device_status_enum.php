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
        
        // Add 'RETRIEVED' if it doesn't exist
        if (!in_array('RETRIEVED', $values)) {
            $values[] = 'RETRIEVED';
            
            // Create new enum string
            $enumString = "'" . implode("','", $values) . "'";
            
            // Modify the column
            DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM($enumString) NOT NULL DEFAULT 'ONLINE'");
        }
    }

    public function down()
    {
        // Get current enum values
        $currentEnumValues = DB::select(DB::raw("SHOW COLUMNS FROM devices WHERE Field = 'status'"))[0]->Type;
        
        // Parse the enum values
        preg_match("/^enum\((.*)\)$/", $currentEnumValues, $matches);
        $values = str_getcsv($matches[1], ",", "'");
        
        // Remove 'RETRIEVED'
        $values = array_filter($values, fn($value) => $value !== 'RETRIEVED');
        
        // Create new enum string
        $enumString = "'" . implode("','", $values) . "'";
        
        // Modify the column
        DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM($enumString) NOT NULL DEFAULT 'ONLINE'");
    }
}; 