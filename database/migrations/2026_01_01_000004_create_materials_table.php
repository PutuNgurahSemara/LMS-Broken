<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            //Masalah 3: Mengubah onDelete pada uploaded_by dari restrictOnDelete() menjadi cascadeOnDelete() karena material dianggap sebagai data turunan dari user yang mengunggahnya. Ketika user tersebut dihapus, data material yang diunggah olehnya juga akan dihapus secara otomatis sehingga tidak menyisakan data material yang masih mereferensikan user yang sudah dihapus.
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['file', 'link']);
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('external_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        //Masalah 5: Pada migration create_materials_table.php, method down() kosong sehingga tabel materials tidak memiliki perintah untuk dihapus ketika migration di-rollback. Solusinya adalah menambahkan kode Schema::dropIfExists('materials'); pada method down() untuk menghapus tabel materials saat migration di-rollback. Migration dapat di-rollback dengan benar dan tabel materials akan ikut dihapus.
        Schema::dropIfExists('materials');
    }
};
