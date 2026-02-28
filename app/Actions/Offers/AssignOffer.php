<?php

namespace App\Actions\Offers;

use App\Events\OfferAssigned;
use App\Models\Offer;
use App\Models\Student;

class AssignOffer
{
    public function handle(Student $student, Offer $offer): void
    {
        $student->offers()->syncWithoutDetaching([
            $offer->id => ['assigned_at' => now()],
        ]);

        OfferAssigned::fire(
            student_id: $student->id,
            offer_id: $offer->id,
            offer_name: $offer->name,
            offer_price: (float) $offer->price,
        );
    }
}
