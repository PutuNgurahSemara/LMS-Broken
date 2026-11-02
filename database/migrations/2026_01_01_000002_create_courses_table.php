<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sks');
            $table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
            // Masalah 4: Index hilang pada kolom yang sering difilter
            // Kesalahan: Kolom status tidak memiliki index padahal sering difilter (where('status', ...))
            // Perbaikan: Tambahkan ->index() pada kolom status untuk mempercepat query filtering
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
