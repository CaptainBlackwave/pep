<?php

namespace App\Plugins\Education\Services;

use App\Plugins\Education\Models\EducationCourse;
use App\Plugins\Education\Models\EducationStats;
use App\Core\Models\User;
use App\Plugins\Education\Models\UserEducation;
use Illuminate\Support\Facades\DB;
use App\Core\Exceptions\GameException;

class EducationService
{
    public function getAvailableCourses(User $user)
    {
        return EducationCourse::where('is_active', true)
            ->where('required_level', '<=', $user->level)
            ->get()
            ->map(function ($course) use ($user) {
                $stats = EducationStats::where('user_id', $user->id)->first();
                $course->can_enroll = ($stats?->intelligence ?? 0) >= $course->required_intelligence
                    && ($stats?->endurance ?? 0) >= $course->required_endurance
                    && $user->cash >= $course->cost;
                return $course;
            });
    }

    public function enrollInCourse(User $user, int $courseId)
    {
        // Check if user is already enrolled in a course
        $activeEnrollment = UserEducation::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->exists();

        if ($activeEnrollment) {
            throw new GameException('You are already enrolled in a course.');
        }

        $course = EducationCourse::findOrFail($courseId);

        // Verify requirements
        if ($user->level < $course->required_level) {
            throw new GameException('You do not meet the level requirement.');
        }

        $stats = EducationStats::where('user_id', $user->id)->first();
        if (($stats?->intelligence ?? 0) < $course->required_intelligence) {
            throw new GameException('You do not meet the intelligence requirement.');
        }

        if (($stats?->endurance ?? 0) < $course->required_endurance) {
            throw new GameException('You do not meet the endurance requirement.');
        }

        if ($user->cash < $course->cost) {
            throw new GameException('You do not have enough cash.');
        }

        DB::beginTransaction();
        try {
            $enrollment = UserEducation::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'started_at' => now(),
                'status' => 'in_progress',
                'progress_percentage' => 0,
            ]);

            $user->profile()->decrement('cash', $course->cost);

            DB::commit();
            return $enrollment->load('course');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function checkProgress(User $user)
    {
        $enrollment = UserEducation::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->with('course')
            ->first();

        if (!$enrollment) {
            return null;
        }

        $hoursElapsed = now()->diffInHours($enrollment->started_at);
        $totalHours = $enrollment->course->duration_hours;
        $progress = min(100, ($hoursElapsed / $totalHours) * 100);

        if ($progress >= 100 && $enrollment->status === 'in_progress') {
            $this->completeCourse($enrollment);
        } else {
            $enrollment->update(['progress_percentage' => (int)$progress]);
        }

        return $enrollment->fresh();
    }

    protected function completeCourse(UserEducation $enrollment)
    {
        DB::beginTransaction();
        try {
            $course = $enrollment->course;
            $user = $enrollment->user;

            $enrollment->update([
                'completed_at' => now(),
                'status' => 'completed',
                'progress_percentage' => 100,
            ]);

            // Award stat increases via dedicated education_stats table
            $eduStats = EducationStats::firstOrCreate(
                ['user_id' => $user->id],
                ['intelligence' => 0, 'endurance' => 0]
            );
            if ($course->intelligence_reward > 0) {
                $eduStats->increment('intelligence', $course->intelligence_reward);
            }
            if ($course->endurance_reward > 0) {
                $eduStats->increment('endurance', $course->endurance_reward);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCourseHistory(User $user)
    {
        return UserEducation::where('user_id', $user->id)
            ->with('course')
            ->orderBy('started_at', 'desc')
            ->get();
    }
}
