<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CourseRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'title' => 'nullable|string|max:255',
            'start_time' => 'nullable|required_with:end_time|date_format:Y-m-d H:i:s',
            'end_time' => 'nullable|required_with:start_time|date_format:Y-m-d H:i:s|after:start_time',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Course::query()
            ->with('teacher.user:id,name')
            ->select([
                'id',
                'teacher_id',
                'title',
                'start_time',
                'end_time',
            ])
            ->selectRaw('
            CASE
                WHEN people_limit = 0 THEN NULL
                ELSE GREATEST(
                    0,
                    people_limit - (
                        SELECT COUNT(*)
                        FROM course_registrations
                        WHERE course_id = courses.id
                        AND (
                            status = ?
                            OR (
                                status = ?
                                AND confirm_expires_at > NOW()
                            )
                        )
                    )
                )
            END AS available_slots
        ', [
                CourseRegistrationStatus::Confirmed->value,
                CourseRegistrationStatus::Pending->value,
            ])
            ->where('status', 1)
            ->where('start_time', '>=', now())
            ->when(isset($validated['teacher_id']), function ($query) use ($validated) {
                $query->where('teacher_id', $validated['teacher_id']);
            })
            ->when(isset($validated['title']), function ($query) use ($validated) {
                $query->where('title', 'like', '%' . $validated['title'] . '%');
            })
            ->when(isset($validated['start_time']), function ($query) use ($validated) {
                $query->where('end_time', '>=', $validated['start_time']);
            })
            ->when(isset($validated['end_time']), function ($query) use ($validated) {
                $query->where('start_time', '<=', $validated['end_time']);
            });

        $courses = $query->orderBy('start_time')->paginate($validated['per_page'] ?? 15);

        return response()->json($courses);
    }


    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $course->load([
            'teacher.user:id,name',
        ]);

        return response()->json(
            array_merge(
                $course->only([
                    'id',
                    'teacher_id',
                    'title',
                    'description',
                    'start_time',
                    'end_time',
                    'people_limit',
                ]),
                [
                    'teacher' => [
                        'id' => $course->teacher?->id,
                        'name' => $course->teacher?->user?->name,
                    ],
                ]
            )
        );
    }


    public function register(Request $request, int $id)
    {
        // 在交易外先檢查使用者是否為學生，避免持有交易鎖定資料庫資源太久
        $user = auth()->user();

        // 檢查是否為學生
        if (!$user || !$user->isStudent() || !$user->student) {
            return response()->json(['message' => '只有學生可以報名課程'], 403);
        }

        $key = "course_registration_{$user->id}_{$id}";
        $lock = null;

        try {
            // 防止同一學生重複送出報名請求
            $lock = Cache::lock($key, 30)->block(2);

            return DB::transaction(function () use ($id, $user) {

                $student = $user->student;

                // 鎖定課程，避免同時報名造成名額超額
                $course = Course::where('id', $id)->lockForUpdate()->firstOrFail();

                // 檢查課程是否啟用
                if (!$course->status) {
                    return response()->json(['message' => '課程尚未啟用'], 400);
                }

                // 檢查課程是否已經開始
                if ($course->start_time <= now()) {
                    return response()->json(['message' => '課程已經開始，無法報名'], 400);
                }

                // 將該學生該課程已逾期的 Pending 更新為 Expired
                Artisan::call('course-registrations:expire', [
                    '--student_id' => $student->id,
                    '--course_id' => $course->id,
                ]);

                // 檢查是否已有有效報名
                $hasActiveRegistration = $student->registrations()
                    ->where('course_id', $course->id)
                    ->whereIn('status', [
                        CourseRegistrationStatus::Pending->value,
                        CourseRegistrationStatus::Confirmed->value,
                    ])
                    ->exists();

                if ($hasActiveRegistration) {
                    return response()->json(['message' => '您已經報名過該課程'], 400);
                }

                // 檢查課程人數限制
                if (
                    $course->people_limit > 0 &&
                    $course->registrations()
                    ->useWritePdo()
                    ->where(function ($query) {
                        $query->where(
                            'status',
                            CourseRegistrationStatus::Confirmed->value
                        )->orWhere(function ($query) {
                            $query->where(
                                'status',
                                CourseRegistrationStatus::Pending->value
                            )->where('confirm_expires_at', '>', now());
                        });
                    })
                    ->count() >= $course->people_limit
                ) {
                    return response()->json(['message' => '課程人數已滿'], 400);
                }

                // 每次報名都建立新的紀錄，不覆蓋歷史資料
                $student->registrations()->create([
                    'course_id' => $course->id,
                    'status' => CourseRegistrationStatus::Pending,
                    'confirm_expires_at' => now()->addMinutes(CourseRegistration::CONFIRMATION_TTL_MINUTES),
                    'email_sent_at' => null,
                    'cancelled_at' => null,
                ]);

                return response()->json(['message' => 'Success'], 201);
            });
        } catch (\Throwable $e) {

            // 記錄實際錯誤，方便除錯
            Log::error('Failed to register course.', [
                'user_id' => $user->id ?? null,
                'course_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => '系統忙碌中，請稍後再試'
            ], 503);
        } finally {
            $lock?->release();
        }
    }
}
