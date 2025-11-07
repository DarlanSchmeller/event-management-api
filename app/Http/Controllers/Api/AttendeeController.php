<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendeeResource;
use App\Http\Traits\CanLoadRelationships;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendeeController extends Controller
{
    use CanLoadRelationships;

    private const RELATIONS = ['user'];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('index', 'show', 'update');
        $this->authorizeResource(Attendee::class, 'attendee');
    }

    /**
     * @OA\Get(
     *     path="/api/events/{event}/attendees",
     *     summary="List attendees for an event",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of attendees")
     * )
     */
    public function index(Event $event)
    {
        $attendees = $this->loadRelationships(
            $event->attendees()->latest()
        );

        return AttendeeResource::collection(
            $attendees->paginate()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/events/{event}/attendees",
     *     summary="Register a user as an attendee for the event",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=201, description="Attendee created")
     * )
     */
    public function store(Request $request, Event $event)
    {
        $attendee = $this->loadRelationships(
            $event->attendees()->create([
                'user_id' => $request->user()->id,
            ])
        );

        return new AttendeeResource($attendee);
    }

    /**
     * @OA\Get(
     *     path="/api/events/{event}/attendees/{attendee}",
     *     summary="Get a specific attendee",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="attendee",
     *         in="path",
     *         required=true,
     *         description="Attendee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Attendee details")
     * )
     */
    public function show(Event $event, Attendee $attendee)
    {
        return new AttendeeResource(
            $this->loadRelationships($attendee)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/events/{event}/attendees/{attendee}",
     *     summary="Remove an attendee from an event",
     *     tags={"Attendees"},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         required=true,
     *         description="Event ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="attendee",
     *         in="path",
     *         required=true,
     *         description="Attendee ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Attendee removed")
     * )
     */
    public function destroy(Event $event, Attendee $attendee)
    {
        // if (Gate::denies('delete-attendee', [$event, $attendee])) {
        //     abort(403, 'This action is not authorized');
        // }

        $attendee->delete();

        return response(status: 204);
    }
}
