<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseBuilderController extends Controller
{
    public function show(Course $course)
    {
        $course->load([
            'modules' => fn ($q) => $q->orderByPivot('position')->withCount('lessons'),
        ]);

        $attachedIds = $course->modules()->get()->pluck('id');
        $availableModules = Module::query()
            ->when($attachedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $attachedIds))
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('tenant.admin.courses.builder', [
            'course' => $course,
            'availableModules' => $availableModules,
        ]);
    }

    public function attachModule(Request $request, Course $course)
    {
        $data = $request->validate([
            'module_id' => ['required', 'uuid', Rule::exists('modules', 'id')],
        ]);

        $module = Module::findOrFail($data['module_id']);

        if ($this->moduleInCourse($course, $module)) {
            return back()->withErrors(['module_id' => 'Questo modulo è già associato al corso.']);
        }

        $max = (int) DB::table('course_module')->where('course_id', $course->id)->max('position');

        $course->modules()->attach($module->id, [
            'id' => (string) Str::uuid(),
            'position' => $max + 1,
            'required' => $request->boolean('is_required'),
        ]);

        return back()->with('toast', 'Modulo associato al corso.');
    }

    public function updateModule(Request $request, Course $course, Module $module)
    {
        abort_unless($this->moduleInCourse($course, $module), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $module->update([
            'title' => $data['title'],
        ]);

        $course->modules()->updateExistingPivot($module->id, [
            'required' => $request->boolean('is_required'),
        ]);

        return back()->with('toast', 'Modulo aggiornato.');
    }

    public function destroyModule(Course $course, Module $module)
    {
        abort_unless($this->moduleInCourse($course, $module), 404);
        $course->modules()->detach($module->id);

        return back()->with('toast', 'Modulo rimosso dal corso.');
    }

    public function moveModule(Course $course, Module $module, string $direction)
    {
        abort_unless($this->moduleInCourse($course, $module), 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $current = DB::table('course_module')
            ->where('course_id', $course->id)
            ->where('module_id', $module->id)
            ->first();

        if (! $current) {
            abort(404);
        }

        $target = DB::table('course_module')
            ->where('course_id', $course->id)
            ->when($direction === 'up', fn ($q) => $q->where('position', '<', $current->position)->orderByDesc('position'))
            ->when($direction === 'down', fn ($q) => $q->where('position', '>', $current->position)->orderBy('position'))
            ->first();

        if ($target) {
            DB::transaction(function () use ($course, $current, $target) {
                DB::table('course_module')
                    ->where('course_id', $course->id)
                    ->where('module_id', $current->module_id)
                    ->update(['position' => $target->position]);

                DB::table('course_module')
                    ->where('course_id', $course->id)
                    ->where('module_id', $target->module_id)
                    ->update(['position' => $current->position]);
            });
        }

        return back();
    }

    private function moduleInCourse(Course $course, Module $module): bool
    {
        return $course->modules()->where('modules.id', $module->getKey())->exists();
    }
}
