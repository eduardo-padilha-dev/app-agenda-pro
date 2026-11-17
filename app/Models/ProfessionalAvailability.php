<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalAvailability extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'starts_at', 'ends_at'];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }
}
