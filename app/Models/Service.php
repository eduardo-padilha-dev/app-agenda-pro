<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = ['name', 'description', 'duration_minutes', 'price_cents', 'active'];

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class);
    }

    public function appointmentItems(): HasMany
    {
        return $this->hasMany(AppointmentItem::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'duration_minutes' => 'integer', 'price_cents' => 'integer'];
    }
}
