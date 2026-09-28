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
               // MASALAH: Dosen dihapus -> Mata kuliah ikut terhapus permanen
                $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();

                // PERBAIKAN: Kolom dibuat nullable dan aksi hapus diset ke nullOnDelete()
                //$table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
                $table->timestamps();
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('courses');
        }
    };
