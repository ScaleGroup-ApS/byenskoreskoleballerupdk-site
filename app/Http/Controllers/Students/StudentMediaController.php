<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentMediaController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'collection' => ['required', 'string', 'in:documents,photos'],
        ]);

        $student->addMediaFromRequest('file')
            ->toMediaCollection($request->input('collection'));

        return back()->with('success', 'Fil uploadet.');
    }

    public function show(Student $student, Media $media): StreamedResponse
    {
        $this->authorize('view', $student);

        abort_unless($media->model_id === $student->id, 404);

        return $media->toInlineResponse($media->file_name);
    }

    public function destroy(Student $student, Media $media): RedirectResponse
    {
        $this->authorize('update', $student);

        abort_unless($media->model_id === $student->id, 404);

        $media->delete();

        return back()->with('success', 'Fil slettet.');
    }
}
