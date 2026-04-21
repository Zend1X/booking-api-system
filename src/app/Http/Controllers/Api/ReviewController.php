<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'You can only review your own bookings'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'Cannot review cancelled booking'], 400);
        }

        if ($booking->end_time > now()) {
            return response()->json(['error' => 'Cannot review booking before it ends'], 400);
        }

        if ($booking->review) {
            return response()->json(['error' => 'Review already exists for this booking'], 400);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json($review->load('booking'), 201);
    }
}