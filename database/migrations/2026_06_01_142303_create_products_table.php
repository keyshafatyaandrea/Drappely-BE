<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name', 100);
            $table->text('description')->nullable(); 
            $table->string('category', 100)->nullable(); 
            $table->string('size', 20)->nullable();
            $table->string('pattern', 100)->nullable(); 
            $table->string('color', 50)->nullable(); 
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2);
            $table->integer('stock')->default(0);
            $table->string('image_path')->nullable(); 
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};