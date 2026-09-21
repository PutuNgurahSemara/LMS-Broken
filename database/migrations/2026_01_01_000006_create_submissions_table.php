<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            //Masalah 2 (2): Tabel submissions belum memiliki unique composite pada assignment_id dan user_id. Akibatnya, satu mahasiswa dapat memiliki lebih dari satu data pengumpulan untuk tugas yang sama. Solusinya dengan menambahkan kode $table->unique(['assignment_id', 'user_id']); untuk mencegah mahasiswa memiliki data submission yang terduplikasi untuk tugas yang sama.
            $table->unique(['assignment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
