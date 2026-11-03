<x-layout title="Detail Rekap Nilai - {{ $course->name }}">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 font-mono text-sm font-semibold bg-blue-100 text-blue-800 rounded-lg">{{ $course->code }}</span>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $course->name }}</h1>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    Dosen: <strong class="text-gray-800">{{ $course->lecturer->name }}</strong> &bull; {{ $course->sks }} SKS
                </p>
            </div>
            <a href="{{ route('grade-recap.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-semibold">Total Mahasiswa</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $studentsCount }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-semibold">Total Tugas</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $assignmentsCount }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-semibold">Total Pengumpulan</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $gradesCount }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-semibold">Rata-rata Kelas</div>
            <div class="text-3xl font-bold text-gray-900 mt-1">
                {{ $avgPercentage }}%
            </div>
        </div>
    </div>

    <!-- Grade Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Mahasiswa</th>
                        <th class="px-4 py-3">NIM/NIP</th>
                        <th class="px-4 py-3">Email</th>
                        @foreach($assignments as $assignment)
                            <th class="px-4 py-3 text-center max-w-32 truncate" title="{{ $assignment->title }}">
                                {{ $assignment->title }}<br>
                                <span class="text-xs text-gray-400">/{{ $assignment->max_score }}</span>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center">Rata-rata</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $student->name }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $student->nim_nip }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $student->email }}</td>
                        @php
                            $studentGrades = $gradesByStudent->get($student->id, collect());
                            $totalScore = 0;
                            $totalMaxScore = 0;
                            $gradedCount = 0;
                        @endphp
                        @foreach($assignments as $assignment)
                            @php
                                $grade = $studentGrades->where('assignment_id', $assignment->id)->first();
                            @endphp
                            <td class="px-4 py-3 text-center">
                                @if($grade)
                                    @php
                                        $percentage = ($grade->score / $assignment->max_score) * 100;
                                        $totalScore += $grade->score;
                                        $totalMaxScore += $assignment->max_score;
                                        $gradedCount++;
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($percentage >= 80) bg-green-100 text-green-800
                                        @elseif($percentage >= 60) bg-blue-100 text-blue-800
                                        @elseif($percentage >= 40) bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ $grade->score }}/{{ $assignment->max_score }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-center font-semibold">
                            @if($gradedCount > 0)
                                {{ round(($totalScore / $totalMaxScore) * 100, 1) }}%
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($gradedCount == $assignmentsCount)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Lengkap</span>
                            @elseif($gradedCount > 0)
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">{{ $gradedCount }}/{{ $assignmentsCount }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500 rounded-full">Belum</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layout>