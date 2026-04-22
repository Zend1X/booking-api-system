<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'location',
        'equipment',
        'created_by'
    ];

    protected $casts = [
        'equipment' => 'array'
    ];

    protected $appends = ['average_rating'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAverageRatingAttribute()
    {
        return $this->bookings()
            ->whereHas('review')
            ->with('review')
            ->get()
            ->avg('review.rating') ?? 0;
    }
} 
