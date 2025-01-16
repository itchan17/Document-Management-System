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
            $table->dropForeign(['user_id']);
        });

        // Recreate the foreign key constraint without cascading delete
        Schema::table('documents', function (Blueprint $table) {
            // Set the user_id to nullable and remove cascade on delete
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Recreate the foreign key constraint with onDelete('set null')
            $table->foreign('user_id')
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
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

    }
};
