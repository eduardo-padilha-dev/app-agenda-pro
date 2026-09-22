<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
