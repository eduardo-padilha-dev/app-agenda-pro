<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'show_inactive' => ['nullable', 'boolean'],
        ]);
        $search = trim($filters['search'] ?? '');
        $showInactive = $request->boolean('show_inactive');

        $services = Service::query()->with('professionals')
            ->when(! $showInactive, fn (Builder $query) => $query->where('active', true))
            ->when($search !== '', fn (Builder $query) => $query->whereLike('name', '%'.$search.'%'))
            ->orderBy('name')->orderBy('id')->paginate(10)->withQueryString();

        return view('services.index', [
            'services' => $services,
            'professionals' => Professional::query()->where('active', true)->orderBy('name')->get(),
            'search' => $search,
            'showInactive' => $showInactive,
            'title' => 'Serviços',
            'page' => 'servicos',
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data) {
            $service = Service::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'price_cents' => self::priceInCents($data['price']),
            ]);
            $service->professionals()->sync($data['professional_ids'] ?? []);
        });

        return redirect()->route('services.index')->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        abort_unless($service->active, 404);
        $data = $request->validated();
        DB::transaction(function () use ($service, $data) {
            $service->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'price_cents' => self::priceInCents($data['price']),
            ]);
            $service->professionals()->sync($data['professional_ids'] ?? []);
        });

        return redirect()->route('services.index')->with('success', 'Serviço atualizado com sucesso!');
    }

    public function destroy(Service $service): RedirectResponse
    {
        abort_unless($service->active, 404);
        $service->update(['active' => false]);

        return redirect()->route('services.index')->with('success', 'Serviço desativado com sucesso!');
    }

    public function restore(Service $service): RedirectResponse
    {
        abort_if($service->active, 404);
        $service->update(['active' => true]);

        return redirect()->route('services.index')->with('success', 'Serviço reativado com sucesso!');
    }

    private static function priceInCents(string $price): int
    {
        [$reais, $centavos] = array_pad(explode('.', $price, 2), 2, '');

        return ((int) $reais * 100) + (int) str_pad($centavos, 2, '0');
    }
}
