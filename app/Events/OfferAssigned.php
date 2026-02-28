<?php

namespace App\Events;

use App\States\StudentBalanceState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class OfferAssigned extends Event
{
    #[StateId(StudentBalanceState::class)]
    public int $student_id;

    public int $offer_id;

    public string $offer_name;

    public float $offer_price;

    public function apply(StudentBalanceState $state): void
    {
        $state->total_owed += $this->offer_price;
    }
}
