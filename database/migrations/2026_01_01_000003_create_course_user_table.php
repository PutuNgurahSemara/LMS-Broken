<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();
            //Masalah 2 (1): Tabel course_user belum memiliki unique composite pada course_id dan user_id, sehingga mahasiswa yang sama dapat terdaftar dua kali pada mata kuliah yang sama. Solusinya dengan menambahkan $table->unique(['course_id', 'user_id']); untuk Mencegah data enrollment mahasiswa terduplikasi pada mata kuliah yang sama.
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
