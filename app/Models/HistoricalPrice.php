<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'date',
        'close_price',
    ];

    protected $casts = [
        'close_price' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Normalize the date to a plain "Y-m-d" string on write so lookups (e.g. updateOrCreate)
     * match exactly regardless of database engine (SQLite stores dates as literal text).
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Carbon::parse($value),
            set: fn (string|Carbon $value) => Carbon::parse($value)->toDateString(),
        );
    }
}
