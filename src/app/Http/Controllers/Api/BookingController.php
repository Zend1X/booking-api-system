<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            $bookings = Booking::with(['room', 'user'])->paginate(20);
        } else {
            $bookings = $user->bookings()->with('room')->paginate(20);
        }

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $roomId = $request->room_id;

        $conflict = Booking::where('room_id', $roomId)
            ->active()
            ->where(function($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function($q) use ($start, $end) {
                        $q->where('start_time', '<', $start)
                            ->where('end_time', '>', $end);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'error' => 'Room is already booked for this time period'
            ], 409);
        }

        $booking = Booking::create([
            'room_id' => $roomId,
            'user_id' => auth()->id(),
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'active'
        ]);

        return response()->json($booking->load(['room', 'user']), 201);
    }

    public function show(Booking $booking)
    {
        if (!auth()->user()->isAdmin() && $booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json($booking->load(['room', 'user', 'review']));
    }

    public function cancel(Booking $booking)
    {
        if (!auth()->user()->isAdmin() && $booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'Booking is already cancelled'], 400);
        }

        if ($booking->end_time < now()) {
            return response()->json(['error' => 'Cannot cancel past booking'], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
}