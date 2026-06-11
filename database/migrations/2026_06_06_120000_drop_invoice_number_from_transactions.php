<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'invoice_number')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Use dropColumn for single column
                $table->dropColumn('invoice_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'invoice_number')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('invoice_number')->unique()->nullable();
            });
        }
    }
};
