<x-dialog :id="'deactivate-client-'.$client->id" title="Desativar cliente">
    <form action="{{ route('clients.destroy', $client) }}" method="POST">
        @csrf
        @method('DELETE')

        <div class="space-y-4 p-6">
            <p class="text-sm text-slate-600">
                Deseja desativar
                <strong>{{ $client->name }}</strong>?
                O registro será preservado.
            </p>

            <div class="flex gap-2">
                <button type="button" data-close
                        class="secondary-button flex-1">
                    Cancelar
                </button>

                <button type="submit"
                        class="danger-button flex-1">
                    Desativar
                </button>
            </div>
        </div>
    </form>
</x-dialog>