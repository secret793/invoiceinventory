<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('permissions')) {
            // Get the database connection
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");
            
            if ($driver === 'mysql') {
                // MySQL specific approach
                $this->fixMySQLTable();
            } else {
                // Generic approach for other database systems
                $this->fixGenericTable();
            }
        }
    }

    /**
     * Fix the permissions table for MySQL
     */
    private function fixMySQLTable()
    {
        // Check if id column exists and its properties
        $hasAutoIncrement = false;
        $hasId = false;
        
        $columns = DB::select('SHOW COLUMNS FROM permissions');
        foreach ($columns as $column) {
            if ($column->Field === 'id') {
                $hasId = true;
                if (strpos($column->Extra, 'auto_increment') !== false) {
                    $hasAutoIncrement = true;
                }
                break;
            }
        }
        
        if (!$hasId) {
            // If id doesn't exist, add it
            // First, check if there's data in the table
            $hasData = DB::table('permissions')->count() > 0;
            
            if ($hasData) {
                // Create a new temporary table with correct structure
                Schema::create('permissions_temp', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    // Add all other columns from the original table
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
                
                // Copy data from the original table to the temporary table
                DB::statement('INSERT INTO permissions_temp (name, guard_name, created_at, updated_at) SELECT name, guard_name, created_at, updated_at FROM permissions');
                
                // Drop the original table
                Schema::drop('permissions');
                
                // Rename the temporary table to the original table name
                Schema::rename('permissions_temp', 'permissions');
            } else {
                // If no data, simply recreate the table
                Schema::drop('permissions');
                Schema::create('permissions', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
            }
        } elseif (!$hasAutoIncrement) {
            // If id exists but isn't auto-increment
            // First, check if there's data in the table
            $hasData = DB::table('permissions')->count() > 0;
            
            if ($hasData) {
                // Create a new temporary table with correct structure
                Schema::create('permissions_temp', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    // Add all other columns from the original table
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
                
                // Copy data from the original table to the temporary table
                DB::statement('INSERT INTO permissions_temp (name, guard_name, created_at, updated_at) SELECT name, guard_name, created_at, updated_at FROM permissions');
                
                // Drop the original table
                Schema::drop('permissions');
                
                // Rename the temporary table to the original table name
                Schema::rename('permissions_temp', 'permissions');
            } else {
                // If no data, simply recreate the table
                Schema::drop('permissions');
                Schema::create('permissions', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
            }
        }
    }

    /**
     * Fix the permissions table for other database systems
     */
    private function fixGenericTable()
    {
        // For non-MySQL databases, use a more generic approach
        if (Schema::hasColumn('permissions', 'id')) {
            // Check if id is auto-increment
            $isAutoIncrement = false;
            
            // Different databases have different ways to check for auto-increment
            // This is a simplified approach
            try {
                $columnInfo = DB::connection()->getDoctrineColumn('permissions', 'id');
                $isAutoIncrement = $columnInfo->getAutoincrement();
            } catch (Exception $e) {
                // If we can't determine, assume it's not auto-increment
                $isAutoIncrement = false;
            }
            
            if (!$isAutoIncrement) {
                // Create a new table with correct structure
                Schema::create('permissions_new', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    // Add all other columns from the original table
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
                
                // Copy data
                $permissions = DB::table('permissions')->get();
                foreach ($permissions as $permission) {
                    DB::table('permissions_new')->insert([
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'created_at' => $permission->created_at,
                        'updated_at' => $permission->updated_at
                    ]);
                }
                
                // Drop old table and rename new one
                Schema::drop('permissions');
                Schema::rename('permissions_new', 'permissions');
            }
        } else {
            // If id column doesn't exist, recreate the table
            $hasData = DB::table('permissions')->count() > 0;
            
            if ($hasData) {
                // Create a new table with correct structure
                Schema::create('permissions_new', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
                
                // Copy data
                DB::statement('INSERT INTO permissions_new (name, guard_name, created_at, updated_at) SELECT name, guard_name, created_at, updated_at FROM permissions');
                
                // Drop old table and rename new one
                Schema::drop('permissions');
                Schema::rename('permissions_new', 'permissions');
            } else {
                // If no data, simply recreate the table
                Schema::drop('permissions');
                Schema::create('permissions', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();
                });
            }
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        // This is a fix migration, reverting is not recommended
        // as it would return to a broken state
    }
};