<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class ClassController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassModel::orderBy('level_group')->orderBy('name');
        if ($request->filled('search_class')) {
            $query->where('name', 'like', '%' . $request->search_class . '%')
                ->orWhere('level_group', 'like', '%' . $request->search_class . '%');
        }
        $classes = $query->paginate(15)->withQueryString();
        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $levelGroups = ['JSS', 'SS', 'PRIMARY', 'SYSTEM']; // Define available level groups
        return view('admin.classes.create', compact('levelGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:classes,name',
            'level_group' => ['required', 'string', 'max:10', Rule::in(['JSS', 'SS', 'PRIMARY', 'SYSTEM'])],
            'description' => 'nullable|string|max:255',
        ]);

        ClassModel::create($validated);
        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }

    public function edit(ClassModel $class)
    {
        $levelGroups = ['JSS', 'SS', 'PRIMARY', 'SYSTEM'];
        return view('admin.classes.edit', compact('class', 'levelGroups'));
    }

    public function update(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('classes')->ignore($class->id)],
            'level_group' => ['required', 'string', 'max:10', Rule::in(['JSS', 'SS', 'PRIMARY', 'SYSTEM'])],
            'description' => 'nullable|string|max:255',
        ]);

        $class->update($validated);
        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassModel $class)
    {
        if ($class->users()->count() > 0) {
            return redirect()->route('admin.classes.index')->with('error', 'Cannot delete class: It has associated users.');
        }
        if ($class->subjects()->count() > 0) {
            return redirect()->route('admin.classes.index')->with('error', 'Cannot delete class: It has associated subjects.');
        }

        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Class deleted successfully.');
    }
}
