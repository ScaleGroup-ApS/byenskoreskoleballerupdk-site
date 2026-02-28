<?php

namespace App\Http\Controllers\Offers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offers\StoreCourseRequest;
use App\Models\Course;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;

class CourseController extends Controller
{
    public function store(StoreCourseRequest $request, Offer $offer): RedirectResponse
    {
        $offer->courses()->create($request->validated());

        return redirect()->back()
            ->with('success', 'Kursusdato tilføjet.');
    }

    public function destroy(Offer $offer, Course $course): RedirectResponse
    {
        $this->authorize('delete', $offer);

        $course->delete();

        return redirect()->route('offers.edit', $offer)
            ->with('success', 'Kursusdato fjernet.');
    }
}
