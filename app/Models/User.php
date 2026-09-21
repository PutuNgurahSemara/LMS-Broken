<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
//Masalah 4: Pada model User, terdapat:protected $guarded = []; Hal ini memungkinkan semua atribut dapat diisi massal, yang berpotensi menimbulkan risiko keamanan. Solusinya adalah mengganti protected $guarded = []; dengan protected $fillable = ['name', 'email', 'password']; untuk membatasi atribut yang dapat diisi massal hanya pada name, email, dan password.
    protected $fillable = [
    'name',
    'email',
    'password',
];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function taughtCourses()
    {
        return $this->hasMany(Course::class, 'lecturer_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)->withPivot('enrolled_at')->withTimestamps();
    }
}
