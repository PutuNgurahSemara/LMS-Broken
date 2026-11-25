<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserUnitTest extends TestCase
{
    public function test_user_has_dosen_role(): void
    {
        $user = new User(['name' => 'Dr. Bambang']);
        $user->role = 'dosen';

        // Masalah 5: Tanpa assertion bermakna
        // Kesalahan: Menggunakan assertTrue(true) yang selalu bernilai true tanpa menguji atribut model sama sekali (atribut role juga tidak masuk $fillable sehingga harus diset langsung)
        // Perbaikan: Set properti role dan uji menggunakan assertion yang bermakna: $this->assertSame('dosen', $user->role)
        $this->assertSame('dosen', $user->role);
    }
}
