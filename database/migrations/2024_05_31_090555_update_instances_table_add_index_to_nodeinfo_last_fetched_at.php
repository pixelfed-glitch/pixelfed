<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $schemaManager->listTableIndexes('instances');
            if (! array_key_exists('instances_nodeinfo_last_fetched_index', $indexesFound)) {
                $table->index('nodeinfo_last_fetched');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $schemaManager->listTableIndexes('instances');
            if (array_key_exists('instances_nodeinfo_last_fetched_index', $indexesFound)) {
                $table->dropIndex('instances_nodeinfo_last_fetched_index');
            }
        });
    }
};
