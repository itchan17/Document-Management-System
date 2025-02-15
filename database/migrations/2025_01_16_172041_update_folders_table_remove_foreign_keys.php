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
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
        });

        // Recreate the foreign key constraints with 'onDelete' set to 'set null'
        Schema::table('folders', function (Blueprint $table) {
            // Set the 'created_by' and 'deleted_by' columns to nullable
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->unsignedBigInteger('deleted_by')->nullable()->change();

            // Add foreign key constraints with 'onDelete' set to 'set null'
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            $table->foreign('deleted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);

            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->unsignedBigInteger('deleted_by')->nullable(false)->change();
        });
    }
};
