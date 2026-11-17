@extends('layouts.base')
@section('body')
<div class="flex h-full items-center justify-center overflow-y-auto bg-slate-50 p-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center"><x-brand class="mb-2 justify-center" /><p class="text-sm text-slate-500">Sistema interno de agendamento</p></div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form action="{{ route('authenticate') }}" method="post" class="space-y-4 p-6" novalidate>
                @csrf
                <div><h1 class="font-display text-lg font-semibold">Entrar</h1><p class="mt-0.5 text-sm text-slate-500">Acesso restrito à equipe interna.</p></div>
                <x-flash />
                <x-field label="E-mail" name="email" type="email" autocomplete="email" placeholder="seu@email.com" value="{{ old('email') }}" required />
                <div class="relative"><x-field label="Senha" name="password" type="password" autocomplete="current-password" placeholder="••••••••" class="pr-12" required /><button type="button" data-toggle-password aria-label="Mostrar senha" aria-pressed="false" class="icon-button absolute bottom-0.5 right-1"><x-icon name="eye" size="16" /></button></div>
                <button type="submit" class="primary-button w-full py-2.5">Entrar</button>
                <div class="text-center"><button type="button" data-auth-target="forgot" class="text-sm text-slate-500 hover:underline">Esqueci minha senha</button></div>
            </form>
            <form method="dialog" class="space-y-4 p-6" data-auth-view="forgot" data-preview-form="forgot" hidden>
                <div><h1 class="font-display text-lg font-semibold">Recuperar senha</h1><p class="mt-0.5 text-sm text-slate-500">Enviaremos um link para o seu e-mail.</p></div>
                <x-field label="E-mail" name="email" type="email" autocomplete="email" placeholder="seu@email.com" required />
                <button type="submit" class="primary-button w-full py-2.5">Enviar link de recuperação</button>
                <div class="text-center"><button type="button" data-auth-target="login" class="text-sm text-slate-500 hover:underline">Voltar ao login</button></div>
            </form>
            <div class="space-y-4 p-6 text-center" data-auth-view="sent" hidden>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600"><x-icon name="check" size="24" /></div>
                <h2 class="font-display text-lg font-semibold">Link enviado</h2>
                <p class="text-sm text-slate-500">Se <strong data-forgot-email></strong> tiver uma conta, você receberá as instruções em breve.</p>
                <p class="text-xs text-slate-400">Demonstração visual: nenhum e-mail foi enviado.</p>
                <button type="button" data-auth-target="login" class="text-sm font-medium text-teal-600 hover:underline">Voltar ao login</button>
            </div>
        </div>
        <p class="mt-6 text-center text-xs text-slate-400">Protótipo — dados fictícios</p>
        <a href="{{ route('prototype', 'agenda') }}" class="mt-3 block text-center text-xs text-teal-600 hover:underline">Explorar telas sem autenticação</a>
        <noscript><p class="mt-3 text-sm text-slate-500">Use o link acima para explorar as telas.</p></noscript>
    </div>
</div>
@endsection
