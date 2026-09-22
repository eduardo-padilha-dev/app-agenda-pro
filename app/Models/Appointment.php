<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = ['status', 'notes', 'cancelled_at', 'cancellation_reason'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(AppointmentItem::class);
    }

    protected function startsAt(): Attribute
    {
        return Attribute::get(fn () => $this->items->min('starts_at'));
    }

    protected function endsAt(): Attribute
    {
        return Attribute::get(fn () => $this->items->max('ends_at'));
    }

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
