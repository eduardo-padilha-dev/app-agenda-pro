<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleBlockRequest;
use App\Models\Professional;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ScheduleBlockController extends Controller
{
    public function store(StoreScheduleBlockRequest $request, Professional $professional): RedirectResponse
    {
        $data = $request->validated();
        $timezone = config('app.business_timezone');

        DB::transaction(function () use ($professional, $data, $timezone) {
            $lockedProfessional = Professional::query()->lockForUpdate()->findOrFail($professional->id);
            abort_unless($lockedProfessional->active, 404);

            $lockedProfessional->blocks()->create([
                'starts_at' => CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], $timezone)->utc(),
                'ends_at' => CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['ends_at'], $timezone)->utc(),
                'reason' => $data['reason'] ?? null,
            ]);
        });

        return redirect()->route('professionals.index')->with('success', 'Bloqueio adicionado com sucesso!');
    }

    public function destroy(Professional $professional, int $block): RedirectResponse
    {
        DB::transaction(function () use ($professional, $block) {
            $lockedProfessional = Professional::query()->lockForUpdate()->findOrFail($professional->id);
            $lockedProfessional->blocks()->findOrFail($block)->delete();
        });

        return redirect()->route('professionals.index')->with('success', 'Bloqueio removido com sucesso!');
    }
}
