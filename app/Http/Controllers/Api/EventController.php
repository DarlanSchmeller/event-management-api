<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Traits\CanLoadRelationships;
use Illuminate\Support\Facades\Gate;

/**
 * @OA\Info(
 *     title="Event Management API",
 *     version="1.0.0",
 *     description="Documentation for the Event Management API"
 * ),
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local API Server"
 * )
 */
class EventController extends Controller
{
    use CanLoadRelationships;

    private const RELATIONS = ['user', 'attendees', 'attendees.user'];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('index', 'show');
        $this->authorizeResource(Event::class, 'event');
    }

    /**
     * @OA\Get(
     *     path="/api/events",
     *     summary="List all events",
     *     description="Returns a paginated list of events with relationships such as user and attendees.",
     *     tags={"Events"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function index()
    {
        $query = $this->loadRelationships(Event::query(), self::RELATIONS);

        return EventResource::collection($query->latest()->paginate());
    }

    /**
     * @OA\Post(
     *     path="/api/events",
     *     summary="Create a new event",
     *     description="Authenticated users can create new events.",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "start_time", "end_time"},
     *             @OA\Property(property="name", type="string", example="Tech Meetup 2025"),
     *             @OA\Property(property="description", type="string", example="A meetup for software engineers."),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-06-01T10:00:00Z"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-06-01T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Event created successfully"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(Request $request)
    {
        $event = Event::create([
            ...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]),
            'user_id' => $request->user()->id
        ]);

        return new EventResource($this->loadRelationships($event));
    }

    /**
     * @OA\Get(
     *     path="/api/events/{event}",
     *     summary="Show a specific event",
     *     description="Retrieve details about a specific event, including related user and attendees.",
     *     tags={"Events"},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function show(Event $event)
    {
        $event->load('user', 'attendees');
        return new EventResource($this->loadRelationships($event));
    }

    /**
     * @OA\Put(
     *     path="/api/events/{event}",
     *     summary="Update an event",
     *     description="Update event details. Only the event owner can perform this action.",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Event Name"),
     *             @OA\Property(property="description", type="string", example="Updated event description"),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-06-10T10:00:00Z"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-06-10T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Event updated successfully"),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function update(Request $request, Event $event)
    {
        // if (Gate::denies('update-event', $event)) {
        //     abort(403, 'You are not authorized to update this event');
        // }

        $event->update(
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time',
            ])
        );

        return new EventResource($this->loadRelationships($event));
    }

    /**
     * @OA\Delete(
     *     path="/api/events/{event}",
     *     summary="Delete an event",
     *     description="Remove an event from the system. Only the event owner can delete it.",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Event deleted successfully"),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response(status: 204);
    }
}
