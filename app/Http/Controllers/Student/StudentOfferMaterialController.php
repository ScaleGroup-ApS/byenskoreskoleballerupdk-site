<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentOfferMaterialController extends Controller
{
    public function __invoke(Offer $offer, Media $media): StreamedResponse
    {
        $student = auth()->user()->student;

        abort_unless($student, 404);
        abort_unless($student->offers()->where('offers.id', $offer->id)->exists(), 403);
        abort_unless(
            $media->model_type === Offer::class
                && $media->model_id === $offer->id
                && $media->collection_name === 'materials',
            404
        );

        return $media->toInlineResponse($media->file_name);
    }
}
