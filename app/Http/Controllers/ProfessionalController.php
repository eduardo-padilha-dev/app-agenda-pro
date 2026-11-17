<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfessionalRequest;
use App\Http\Requests\UpdateProfessionalRequest;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'show_inactive' => ['nullable', 'boolean'],
        ]);
        $search = trim($filters['search'] ?? '');
        $showInactive = $request->boolean('show_inactive');
        $phoneSearch = preg_match('/^[0-9\s()+.\-]+$/', $search)
            ? preg_replace('/\D/', '', $search) : '';
        $phoneExpression = 'phone';
        $phoneBindings = [];
        foreach ([' ', '(', ')', '-', '+', '.', "\t", "\n", "\r"] as $character) {
            $phoneExpression = "replace($phoneExpression, ?, '')";
            $phoneBindings[] = $character;
        }

        $professionals = Professional::query()->with(['availabilities', 'blocks'])
            ->when(! $showInactive, fn (Builder $query) => $query->where('active', true))
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereLike('name', '%'.$search.'%')
                    ->orWhereLike('phone', '%'.$search.'%')
                    ->when($phoneSearch !== '', fn (Builder $query) => $query->orWhereRaw(
                        "$phoneExpression LIKE ?", [...$phoneBindings, '%'.$phoneSearch.'%']
                    ))
            ))
            ->orderBy('name')->orderBy('id')->paginate(10)->withQueryString();

        return view('professionals.index', [
            'professionals' => $professionals,
            'search' => $search,
            'showInactive' => $showInactive,
            'title' => 'Profissionais',
            'page' => 'profissionais',
        ]);
    }

    public function store(StoreProfessionalRequest $request): RedirectResponse
    {
        Professional::create($request->validated());

        return redirect()->route('professionals.index')->with('success', 'Profissional cadastrado com sucesso!');
    }

    public function update(UpdateProfessionalRequest $request, Professional $professional): RedirectResponse
    {
        abort_unless($professional->active, 404);
        $professional->update($request->validated());

        return redirect()->route('professionals.index')->with('success', 'Profissional atualizado com sucesso!');
    }

    public function destroy(Professional $professional): RedirectResponse
    {
        abort_unless($professional->active, 404);
        $professional->update(['active' => false]);

        return redirect()->route('professionals.index')->with('success', 'Profissional desativado com sucesso!');
    }

    public function restore(Professional $professional): RedirectResponse
    {
        abort_if($professional->active, 404);
        $professional->update(['active' => true]);

        return redirect()->route('professionals.index')->with('success', 'Profissional reativado com sucesso!');
    }
}
