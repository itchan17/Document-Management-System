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
        Schema::table('documents', function (Blueprint $table) {
            // Ensure the 'deleted_by' column is nullable
            $table->unsignedBigInteger('deleted_by')->nullable()->change();

            // Drop the existing foreign key if it exists
            $table->dropForeign(['deleted_by']);

            // Add the foreign key with ON DELETE SET NULL
            $table->foreign('deleted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop the foreign key with ON DELETE SET NULL
            $table->dropForeign(['deleted_by']);

            // Restore the original foreign key behavior
            $table->foreign('deleted_by')
                  ->references('id')
                  ->on('users');
        });
    }

};
