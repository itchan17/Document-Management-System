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
            // Drop the existing foreign key and column
            $table->dropForeign(['folder']);
            $table->dropColumn('folder');    // Drop the column

            // Recreate the column with the new foreign key constraint
            $table->foreignId('folder')
                ->nullable() 
                ->constrained('folders') 
                ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Reverse the change;
            $table->dropForeign(['folder']);
            $table->dropColumn('folder');

            $table->foreignId('folder')
                ->nullable()
                ->constrained('folders');
        });
    }
};
