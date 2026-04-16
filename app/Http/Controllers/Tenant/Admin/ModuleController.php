<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Module;
use App\Support\LessonDuration;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $modules = Module::query()
            ->with([
                'courses:id,title,slug',
                'lessons' => fn ($q) => $q
                    ->select(['id', 'module_id', 'type', 'duration_seconds', 'position'])
                    ->orderBy('position'),
                'lessons.videoLesson' => fn ($q) => $q->select(['lesson_id', 'duration_seconds']),
            ])
            ->withCount(['courses', 'lessons'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $moduleDurations = [];
        foreach ($modules as $module) {
            $totals = LessonDuration::sumForLessons($module->lessons);
            $moduleDurations[$module->getKey()] = $totals;
        }

        return view('tenant.admin.modules.index', [
            'modules' => $modules,
            'q' => $q,
            'moduleDurations' => $moduleDurations,
        ]);
    }

    public function create()
    {
        return view('tenant.admin.modules.create', [
            'module' => new Module,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $module = Module::create(['title' => $data['title']]);

        return redirect()
            ->route('tenant.admin.modules.index')
            ->with('toast', 'Modulo creato. Associalo a un corso dal builder.');
    }

    public function edit(Module $module)
    {
        return view('tenant.admin.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $module->update(['title' => $data['title']]);

        return back()->with('toast', 'Modulo aggiornato.');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return redirect()
            ->route('tenant.admin.modules.index')
            ->with('toast', 'Modulo eliminato.');
    }
}
