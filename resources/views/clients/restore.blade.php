<x-dialog :id="'restore-client-'.$client->id" title="Reativar cliente">
    <form action="{{ route('clients.restore', $client) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-4 p-6">
            <p class="text-sm text-slate-600">
                Deseja reativar <strong>{{ $client->name }}</strong>?
                O cliente voltará à lista de ativos. Seus dados serão preservados.
            </p>
            <div class="flex gap-2">
                <button type="button" data-close class="secondary-button flex-1">Cancelar</button>
                <button type="submit" class="primary-button flex-1">Reativar</button>
            </div>
        </div>
    </form>
</x-dialog>
