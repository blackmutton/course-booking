<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $validated = $request->validate([
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meeting_url' => 'nullable|string|url|max:255',
            'people_limit' => 'nullable|integer|min:1',
            'status' => 'nullable|boolean',
            'start_time' => 'required_with:end_time|date_format:Y-m-d H:i:s',
            'end_time' => 'required_with:start_time|date_format:Y-m-d H:i:s|after:start_time',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();

        $query = Course::query()
            ->with('teacher.user:id,name')
            ->select([
                'id',
                'teacher_id',
                'title',
                'people_limit',
                'status',
                'start_time',
                'end_time',
            ])
            ->when(isset($validated['teacher_id']), function ($query) use ($validated) {
                $query->where('teacher_id', $validated['teacher_id']);
            })
            ->when(isset($validated['title']), function ($query) use ($validated) {
                $query->where('title', 'like', '%' . $validated['title'] . '%');
            })
            ->when(isset($validated['description']), function ($query) use ($validated) {
                $query->where(
                    'description',
                    'like',
                    '%' . $validated['description'] . '%'
                );
            })
            ->when(isset($validated['meeting_url']), function ($query) use ($validated) {
                $query->where('meeting_url', $validated['meeting_url']);
            })
            ->when(isset($validated['people_limit']), function ($query) use ($validated) {
                $query->where('people_limit', $validated['people_limit']);
            })
            ->when(isset($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->when(isset($validated['start_time']), function ($query) use ($validated) {
                $query->where('start_time', '>=', $validated['start_time']);
            })
            ->when(isset($validated['end_time']), function ($query) use ($validated) {
                $query->where('end_time', '<=', $validated['end_time']);
            });

        // Teacher 只能看到自己的課程
        if ($user->isTeacher()) {
            $query->whereHas('teacher', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $courses = $query->orderBy('start_time')->paginate($validated['per_page'] ?? 15);

        return response()->json($courses);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_url' => 'nullable|string|url|max:255',
            'people_limit' => 'required|integer|min:1',
            'status' => 'boolean',
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ]);

        $user = auth()->user();

        if ($user->isAdmin()) {
            $teacherValidated = $request->validate([
                'teacher_id' => 'required|integer|exists:teachers,id',
            ]);

            // Admin 可以指定任何 Teacher
            $teacher = Teacher::findOrFail($teacherValidated['teacher_id']);
        } else {
            // Teacher 自動使用自己的 Teacher profile
            $teacher = $user->teacher;

            if (!$teacher) {
                abort(403, 'You are not a teacher.');
            }
        }

        // 非 Admin 會在這裡確認 teacher 是否屬於自己
        // laravel的Policy會自動注入登入者的User
        $this->authorize('create', $teacher);

        // 不信任前端傳來的 teacher_id
        $validated['teacher_id'] = $teacher->id;


        $course = Course::create($validated);

        return response()->json(
            $course->load('teacher.user'),
            201
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $this->authorize('view', $course);

        return response()->json(
            $course->load('teacher.user')
        );
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'meeting_url' => 'sometimes|nullable|string|url|max:255',
            'people_limit' => 'sometimes|integer|min:1',
            'status' => 'sometimes|boolean',
            'start_time' => 'sometimes|date_format:Y-m-d H:i:s',
            'end_time' => 'sometimes|date_format:Y-m-d H:i:s',
        ]);

        $startTime = isset($validated['start_time'])
            ? Carbon::parse($validated['start_time'])
            : $course->start_time;

        $endTime = isset($validated['end_time'])
            ? Carbon::parse($validated['end_time'])
            : $course->end_time;

        if ($endTime->lessThanOrEqualTo($startTime)) {
            return response()->json([
                'message' => 'End time must be after start time.'
            ], 422);
        }


        $user = auth()->user();

        if ($user->isAdmin()) {
            $teacherValidated = $request->validate([
                'teacher_id' => 'required|integer|exists:teachers,id',
            ]);
            // Admin 可以指定任何 Teacher
            $teacher = Teacher::findOrFail($teacherValidated['teacher_id']);
        } else {
            // Teacher 自動使用自己的 Teacher profile
            $teacher = $user->teacher;

            if (!$teacher) {
                abort(403, 'You are not a teacher.');
            }
        }

        $validated['teacher_id'] = $teacher->id;

        $course->update($validated);

        return response()->json($course->refresh()->load('teacher.user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        $course->delete();

        return response()->noContent();
    }
}