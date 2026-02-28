<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::withCount('students')
            ->latest()
            ->paginate(15);

        return Inertia::render('teams/index', [
            'teams' => $teams,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('teams/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $team = Team::create($validated);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Hold oprettet.');
    }

    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load('students.user');

        return Inertia::render('teams/show', [
            'team' => $team,
        ]);
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('teams/edit', [
            'team' => $team,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $team->update($validated);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Hold opdateret.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Hold slettet.');
    }
}
