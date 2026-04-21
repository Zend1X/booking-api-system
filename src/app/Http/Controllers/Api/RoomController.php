<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => []]);
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->has('location')) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        $rooms = $query->paginate(10);
        
        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'equipment' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $room = Room::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'location' => $request->location,
            'equipment' => $request->equipment ?? [],
            'created_by' => auth()->id()
        ]);

        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        $room->load(['bookings' => function($query) {
            $query->active()->where('end_time', '>', now());
        }]);

        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'capacity' => 'integer|min:1',
            'location' => 'string|max:255',
            'equipment' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $room->update($request->only(['name', 'capacity', 'location', 'equipment']));

        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        $busyRoomIds = Booking::active()
            ->where(function($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function($q) use ($start, $end) {
                        $q->where('start_time', '<', $start)
                            ->where('end_time', '>', $end);
                    });
            })
            ->pluck('room_id');

        $query = Room::whereNotIn('id', $busyRoomIds);

        if ($request->has('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->has('location')) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        $freeRooms = $query->get();

        return response()->json($freeRooms);
    }

    public function schedule(Room $room, Request $request)
    {
        $date = $request->has('date') 
            ? Carbon::parse($request->date) 
            : now();

        $bookings = $room->bookings()
            ->active()
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'room' => $room,
            'date' => $date->toDateString(),
            'bookings' => $bookings
        ]);
    }
}