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
            //Masalah 1: urutan migrate salah: karena file create_courses _table.php dibuat sebelum create_users_table.php, maka foreign key lecturer_id tidak bisa dibuat karena tabel users belum ada. Solusinya adalah mengubah urutan nama file migration agar create_users_table.php dijalankan terlebih dahulu.
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
