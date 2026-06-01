<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    #[\Override]
    protected $fillable = [
        'user_id',
        'gender',
        'birth_date',
        'location',
        'website',
    ];

    #[\Override]
    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
