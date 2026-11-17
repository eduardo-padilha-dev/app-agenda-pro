<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
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
        // Normalize formatting in existing records without rewriting stored phones.
        $phoneExpression = 'phone';
        $phoneBindings = [];
        foreach ([' ', '(', ')', '-', '+', '.', "\t", "\n", "\r"] as $character) {
            $phoneExpression = "replace($phoneExpression, ?, '')";
            $phoneBindings[] = $character;
        }

        $clients = Client::query()
            ->when($showInactive, fn (Builder $query) => $query->withTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereLike('name', '%'.$search.'%')
                    ->orWhereLike('phone', '%'.$search.'%')
                    ->when($phoneSearch !== '', fn (Builder $query) => $query->orWhereRaw(
                        "$phoneExpression LIKE ?",
                        [...$phoneBindings, '%'.$phoneSearch.'%']
                    ))
            ))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'search' => $search,
            'showInactive' => $showInactive,
            'title' => 'Clientes',
            'page' => 'clientes',
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente cadastrada com sucesso!');
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente atualizada com sucesso!');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente desativada com sucesso!');
    }

    public function restore(Client $client): RedirectResponse
    {
        abort_unless($client->trashed(), 404);

        $client->restore();

        return redirect()->route('clients.index')->with('success', 'Cliente reativada com sucesso!');
    }
}
