<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\SendVerifyEmailJob;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        $request->validate([
            'status' => 'boolean',
            'name' => 'string|max:255',
            'email' => 'string|email|max:255',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ]);

        $query = Teacher::with('user')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->when($request->filled('email'), function ($query) use ($request) {
                $query->whereHas('user', function ($query) use ($request) {
                    $query->where('email', 'like', '%' . $request->input('email') . '%');
                });
            });

        $user = auth()->user();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }


        $perPage = $request->input('per_page', 15);
        $teachers = $query->paginate($perPage);

        return response()->json($teachers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Teacher::class);

        $request->validate([
            'status' => 'required|boolean',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {

            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'email_verified_at' => now(),
                ]);
                $user->teacher()->create([
                    'status' => $request->input('status'),
                    'name' => $request->input('name'),
                ]);

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('Failed to create teacher', [
                'request' => $request->all(),
                'exception' => $e,
            ]);
            throw $e;
        }

        return response()->json(
            $user->teacher->load('user'),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);
        return response()->json($teacher->load('user'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $this->authorize('update', $teacher);
        $request->validate([
            'status' => 'sometimes|boolean',
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8|confirmed',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($teacher->user_id),
            ],
        ]);

        try {
            DB::transaction(function () use ($request, $teacher) {
                $data = [];
                if ($request->filled('email')) {
                    $data = [
                        'email' => $request->input('email'),
                        'email_verified_token' => Str::uuid()->toString(),
                        'email_token_expiration' => now()->addMinutes(30),
                    ];
                }

                if ($request->filled('password')) {
                    $data['password'] = Hash::make($request->input('password'));
                }

                $teacher->user->update($data);

                $teacher->update($request->only(['status', 'name']));
            });
        } catch (Throwable $e) {
            Log::error('Failed to update teacher', [
                'user_id' => $teacher->user_id,
                'request' => $request->all(),
                'exception' => $e,
            ]);
            throw $e;
        }
        if ($request->filled('email')) {

            SendVerifyEmailJob::dispatch($teacher->user)
                ->afterCommit()
                ->onQueue('emails');
        }

        return response()->json($teacher->fresh()->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);
        try {
            $teacher->user->delete();
        } catch (Throwable $e) {
            Log::error('Failed to delete teacher', [
                'user_id' => $teacher->user_id,
                'teacher_id' => $teacher->id,
                'exception' => $e,
            ]);
            throw $e;
        }

        return response()->noContent();
    }
}