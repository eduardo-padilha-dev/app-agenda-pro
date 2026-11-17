<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfessionalAvailabilityRequest;
use App\Models\Professional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProfessionalAvailabilityController extends Controller
{
    public function update(UpdateProfessionalAvailabilityRequest $request, Professional $professional): RedirectResponse
    {
        $intervals = $request->validated('intervals') ?? [];

        DB::transaction(function () use ($professional, $intervals) {
            $lockedProfessional = Professional::query()->lockForUpdate()->findOrFail($professional->id);
            abort_unless($lockedProfessional->active, 404);

            $lockedProfessional->availabilities()->delete();
            foreach ($intervals as $weekday => $dayIntervals) {
                foreach ($dayIntervals as $interval) {
                    $lockedProfessional->availabilities()->create([
                        'weekday' => (int) $weekday,
                        'starts_at' => $interval['starts_at'],
                        'ends_at' => $interval['ends_at'],
                    ]);
                }
            }
        });

        return redirect()->route('professionals.index')->with('success', 'Disponibilidade atualizada com sucesso!');
    }
}
