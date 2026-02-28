<?php

namespace App\Http\Controllers\Offers;

use App\Enums\OfferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offers\StoreOfferRequest;
use App\Http\Requests\Offers\UpdateOfferRequest;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Offer::class);

        $offers = Offer::latest()->paginate(15);

        return Inertia::render('offers/index', [
            'offers' => $offers,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Offer::class);

        return Inertia::render('offers/create', [
            'offerTypes' => collect(OfferType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->name,
            ]),
        ]);
    }

    public function store(StoreOfferRequest $request): RedirectResponse
    {
        Offer::create($request->validated());

        return redirect()->route('offers.index')
            ->with('success', 'Tilbud oprettet.');
    }

    public function edit(Offer $offer): Response
    {
        $this->authorize('update', $offer);

        return Inertia::render('offers/edit', [
            'offer' => $offer,
            'offerTypes' => collect(OfferType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->name,
            ]),
            'courses' => $offer->courses()->orderBy('start_at')->get(),
        ]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): RedirectResponse
    {
        $offer->update($request->validated());

        return redirect()->route('offers.index')
            ->with('success', 'Tilbud opdateret.');
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return redirect()->route('offers.index')
            ->with('success', 'Tilbud slettet.');
    }
}
