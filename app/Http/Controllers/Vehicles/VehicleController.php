<?php

namespace App\Http\Controllers\Vehicles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::latest()->paginate(15);

        return Inertia::render('vehicles/index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Vehicle::class);

        return Inertia::render('vehicles/create');
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::create($request->validated());

        return redirect()->route('vehicles.index')
            ->with('success', 'Køretøj oprettet.');
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('update', $vehicle);

        return Inertia::render('vehicles/edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('vehicles.index')
            ->with('success', 'Køretøj opdateret.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Køretøj slettet.');
    }
}
