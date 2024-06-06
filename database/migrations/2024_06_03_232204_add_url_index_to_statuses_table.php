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
        Schema::table('statuses', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $schemaManager->listTableIndexes('statuses');
            if (! array_key_exists('statuses_url_index', $indexesFound)) {
                $table->index('url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $schemaManager->listTableIndexes('statuses');
            if (array_key_exists('statuses_url_index', $indexesFound)) {
                $table->dropIndex('statuses_url_index');
            }
        });
    }
};
