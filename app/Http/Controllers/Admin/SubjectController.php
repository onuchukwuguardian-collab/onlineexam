<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('classModel')->orderBy('name');
        if ($request->class_id_filter) {
            $query->where('class_id', $request->class_id_filter);
        }
        if ($request->filled('search_subject')) {
            $query->where('name', 'like', '%' . $request->search_subject . '%');
        }
        $subjects = $query->paginate(15)->withQueryString();
        $classes = ClassModel::orderBy('name')->get(); // For filter dropdown
        return view('admin.subjects.index', compact('subjects', 'classes'));
    }

    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        return view('admin.subjects.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id);
                }),
            ],
            'class_id' => 'required|exists:classes,id',
            'exam_duration_minutes' => 'required|integer|min:1',
        ]);

        Subject::create($validated);
        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        $classes = ClassModel::orderBy('name')->get();
        return view('admin.subjects.edit', compact('subject', 'classes'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects')->where(function ($query) use ($request) {
                    return $query->where('class_id', $request->class_id);
                })->ignore($subject->id),
            ],
            'class_id' => 'required|exists:classes,id',
            'exam_duration_minutes' => 'required|integer|min:1',
        ]);

        $subject->update($validated);
        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->questions()->count() > 0) {
            return redirect()->route('admin.subjects.index')->with('error', 'Cannot delete subject. It has associated questions.');
        }
        $subject->delete();
        return redirect()->route('admin.subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
