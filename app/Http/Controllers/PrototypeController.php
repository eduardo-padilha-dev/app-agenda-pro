<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrototypeController extends Controller
{
    public function __invoke(Request $request, string $page = 'login'): View
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $pages = [
            'login' => ['auth.login', 'Entrar'],
            'calendar' => ['calendar.index', 'Agenda'],
            'agendamentos' => ['appointments.index', 'Agendamentos'],
            'clientes' => ['prototype.clients', 'Clientes'],
            'profissionais' => ['prototype.professionals', 'Profissionais'],
            'servicos' => ['prototype.services', 'Serviços'],
        ];
        abort_unless(isset($pages[$page]), 404);
        $input = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $today = CarbonImmutable::now('America/Sao_Paulo')->startOfDay();
        $date = isset($input['date']) ? CarbonImmutable::parse($input['date'], $today->timezone) : $today;
        $data = json_decode(file_get_contents(resource_path('prototype/data.json')), true, flags: JSON_THROW_ON_ERROR);
        $offset = (int) CarbonImmutable::parse($data['weekStart'])->diffInDays($today->startOfWeek());

        foreach ($data['appointments'] as &$appointment) {
            foreach ($appointment['items'] as &$item) {
                $item['startTime'] = CarbonImmutable::parse($item['startTime'])->addDays($offset)->setTimezone($today->timezone)->toIso8601String();
            }
            unset($item);
        }
        unset($appointment);
        foreach ($data['blocks'] as &$block) {
            foreach (['startTime', 'endTime'] as $field) {
                $block[$field] = CarbonImmutable::parse($block[$field])->addDays($offset)->setTimezone($today->timezone)->toIso8601String();
            }
        }
        unset($block);

        return view($pages[$page][0], [
            ...$data,
            'page' => $page,
            'title' => $pages[$page][1],
            'date' => $date,
            'today' => $today,
            'week' => $date->startOfWeek(),
            'statuses' => ['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'completed' => 'Concluído', 'cancelled' => 'Cancelado', 'no_show' => 'Não compareceu'],
            'prototypeData' => [
                'appointments' => $data['appointments'],
                'professionals' => $data['professionals'],
                'clients' => $data['clients'],
                'services' => $data['services'],
            ],
        ]);
    }
}
