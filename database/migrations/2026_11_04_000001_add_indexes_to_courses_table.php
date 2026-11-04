<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // courses.status difilter di CourseController::index() WHERE status='active'
            $table->index('status');
            // courses.lecturer_id difilter di dashboard dosen WHERE lecturer_id=?
            // (FK constraint sudah ada, tapi index eksplisit untuk query dosen)
            $table->index('lecturer_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['lecturer_id']);
        });
    }
};
