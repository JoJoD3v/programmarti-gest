@extends('layouts.support')

@section('title', 'Impostazioni assistenza — ProgrammArti')
@section('page-title', 'Impostazioni assistenza email')

@section('content')
    @php
        $shares = array_map('intval', (array) $settings['assignment_shares']);
        $sharesActive = $shares !== [];
        $equal = $agents->isEmpty() ? [] : array_fill_keys($agents->pluck('id')->all(), intdiv(100, $agents->count()));
        if ($equal !== []) {
            $equal[array_key_first($equal)] += 100 - array_sum($equal);
        }
        $average = $sharesActive ? (int) round(array_sum($shares) / max(1, count(array_filter($shares)))) : null;
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <section class="card">
            <h2 class="card-title">Distribuzione automatica dei ticket</h2>
            <div class="p-5 space-y-4 text-sm">
                @if ($agents->isEmpty())
                    <p class="text-slate-600">Nessun dipendente abilitato: spunta «Gestisce i ticket email» nella scheda del dipendente.</p>
                @else
                    <p class="text-slate-600">
                        @if ($sharesActive)
                            Percentuali attive dal {{ \Illuminate\Support\Carbon::parse($settings['assignment_shares_since'])->format('d/m/Y H:i') }}: ogni nuovo ticket va a chi è più indietro rispetto alla sua quota.
                        @else
                            Percentuali non attive: ogni nuovo ticket va a chi ne ha meno aperti.
                        @endif
                        Chi è segnato come non disponibile viene saltato e la sua quota si divide tra gli altri. Salvare fa ripartire il conteggio.
                    </p>

                    <form method="POST" action="{{ route('support.settings.shares') }}" id="shares-form" class="space-y-3">
                        @csrf @method('PUT')
                        @foreach ($agents as $agent)
                            @php($value = (int) old("shares.{$agent->id}", $sharesActive ? ($shares[$agent->id] ?? $average) : $equal[$agent->id]))
                            <div class="flex items-center gap-3">
                                <label for="share-{{ $agent->id }}" class="w-48 truncate font-medium">
                                    {{ $agent->name }}
                                    @unless ($agent->ticket_available)<span class="ml-1 text-xs font-normal text-amber-700">non disponibile</span>@endunless
                                    @if ($sharesActive && ! array_key_exists($agent->id, $shares))<span class="ml-1 text-xs font-normal text-indigo-700">nuovo</span>@endif
                                </label>
                                <input type="range" min="0" max="100" step="1" id="share-{{ $agent->id }}" name="shares[{{ $agent->id }}]" value="{{ $value }}" class="share-input flex-1 accent-indigo-600">
                                <output class="w-12 text-right tabular-nums" for="share-{{ $agent->id }}">{{ $value }}%</output>
                            </div>
                        @endforeach

                        @error('shares')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <p>Totale: <span id="shares-total" class="font-semibold tabular-nums">100%</span></p>
                            <button type="button" id="shares-equal" class="btn btn-sm btn-secondary">Dividi in parti uguali</button>
                            <button type="submit" id="shares-save" class="btn btn-sm btn-primary">Salva percentuali</button>
                        </div>
                    </form>

                    @if ($sharesActive)
                        <form method="POST" action="{{ route('support.settings.shares.reset') }}" onsubmit="return confirm('Disattivare le percentuali?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-slate-600 hover:underline">Disattiva percentuali (torna a «chi ne ha meno aperti»)</button>
                        </form>
                    @endif
                @endif
            </div>
        </section>

        <form method="POST" action="{{ route('support.settings.update') }}" class="space-y-6">
            @csrf @method('PUT')

            <section class="card">
                <h2 class="card-title">Risposte</h2>
                <div class="p-5 space-y-4 text-sm">
                    <div>
                        <label class="block font-medium mb-1" for="reply_signature">Firma in fondo alle risposte</label>
                        <textarea id="reply_signature" name="reply_signature" rows="3" maxlength="1000" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg">{{ old('reply_signature', $settings['reply_signature']) }}</textarea>
                        <p class="text-xs text-slate-600 mt-1">Puoi usare {dipendente_nome}. Lascia vuoto per non aggiungere firma.</p>
                    </div>
                    <input type="hidden" name="personal_saved_replies_enabled" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="personal_saved_replies_enabled" value="1" @checked(old('personal_saved_replies_enabled', $settings['personal_saved_replies_enabled']))> Ogni dipendente può creare risposte salvate personali</label>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Pulizia casella Gmail</h2>
                <div class="p-5 text-sm space-y-2">
                    <label class="block font-medium mb-1" for="gmail_trash_after_hours">Sposta nel cestino Gmail le email dei ticket chiusi dopo (ore)</label>
                    <input id="gmail_trash_after_hours" name="gmail_trash_after_hours" type="number" min="1" max="8760" class="filter-input w-32" value="{{ old('gmail_trash_after_hours', $settings['gmail_trash_after_hours']) }}" placeholder="disattivata">
                    <p class="text-xs text-slate-600">Lascia vuoto per non toccare mai Gmail. Il ticket resta completo nel gestionale; Gmail svuota il cestino da solo dopo 30 giorni. I thread condivisi con un ticket ancora aperto non vengono toccati.</p>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Classificazione automatica</h2>
                <div class="p-5 text-sm space-y-2">
                    <input type="hidden" name="llm_classifier_enabled" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="llm_classifier_enabled" value="1" @checked(old('llm_classifier_enabled', $settings['llm_classifier_enabled']))> Usa l'AI per stabilire la tipologia</label>
                    <p class="text-xs text-slate-600">Se la spegni, i ticket restano «da classificare» (le regole mittente continuano a funzionare) e vengono assegnati comunque.</p>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Bozze di risposta AI</h2>
                <div class="p-5 space-y-4 text-sm">
                    <p class="px-3 py-2 rounded bg-amber-50 text-amber-800 text-xs">Prima di attivarle compila le <a class="underline" href="{{ route('support.knowledge.index') }}">informazioni per le risposte AI</a> (spedizioni, resi, garanzia): senza policy le bozze sono piene di segnaposto.</p>
                    <input type="hidden" name="drafts_enabled" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="drafts_enabled" value="1" @checked(old('drafts_enabled', $settings['drafts_enabled']))> Attiva bozze AI</label>

                    <div>
                        <label class="block font-medium mb-1" for="drafts_audience">Disponibili per</label>
                        <select id="drafts_audience" name="drafts_audience" class="filter-input">
                            @foreach (['admins' => 'Solo amministratori', 'selected_users' => 'Utenti selezionati', 'all' => 'Tutti i dipendenti abilitati'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('drafts_audience', $settings['drafts_audience']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="selected-users" class="hidden">
                        <p class="font-medium mb-1">Utenti selezionati</p>
                        <div class="grid grid-cols-2 gap-1">
                            @foreach ($users as $user)
                                <label class="flex items-center gap-2"><input type="checkbox" name="drafts_selected_user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, old('drafts_selected_user_ids', $settings['drafts_selected_user_ids'])))> {{ $user->name }}</label>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="drafts_pregenerate" value="0">
                    <label class="flex items-center gap-2"><input type="checkbox" name="drafts_pregenerate" value="1" @checked(old('drafts_pregenerate', $settings['drafts_pregenerate']))> Pregenera in background la bozza quando il cliente risponde (solo se le bozze sono attive)</label>
                    <div>
                        <label class="block font-medium mb-1" for="drafts_tone_rules">Tono e regole</label>
                        <textarea id="drafts_tone_rules" name="drafts_tone_rules" rows="4" maxlength="3000" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg" placeholder="Es. dare del lei; non promettere date esatte; firma: Il team ProgrammArti">{{ old('drafts_tone_rules', $settings['drafts_tone_rules']) }}</textarea>
                    </div>
                </div>
            </section>

            <button class="btn btn-primary" type="submit">Salva impostazioni</button>
        </form>

        <section class="card">
            <h2 class="card-title">Ultime modifiche</h2>
            @if ($logs->isEmpty())
                <p class="p-5 text-sm text-slate-600">Nessuna modifica registrata.</p>
            @else
                <ul class="divide-y divide-slate-50 text-sm">
                    @foreach ($logs as $log)
                        <li class="px-5 py-2.5"><span class="font-medium">{{ $log->key }}</span>: <code>{{ json_encode($log->old_value, JSON_UNESCAPED_UNICODE) }}</code> → <code>{{ json_encode($log->new_value, JSON_UNESCAPED_UNICODE) }}</code>
                            <span class="text-xs text-slate-600">· {{ $log->user?->name ?? 'sistema' }}, {{ $log->created_at->format('d/m/Y H:i') }}</span></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var audience = document.getElementById('drafts_audience');
            var box = document.getElementById('selected-users');
            var sync = function () { box.classList.toggle('hidden', audience.value !== 'selected_users'); };
            audience.addEventListener('change', sync); sync();
        })();

        (function () {
            var form = document.getElementById('shares-form');
            if (!form) { return; }
            var inputs = Array.prototype.slice.call(form.querySelectorAll('.share-input'));
            var total = document.getElementById('shares-total');
            var save = document.getElementById('shares-save');
            var refresh = function () {
                var sum = 0;
                inputs.forEach(function (input) {
                    sum += parseInt(input.value, 10);
                    form.querySelector('output[for="' + input.id + '"]').textContent = input.value + '%';
                });
                total.textContent = sum + '%';
                total.classList.toggle('text-red-600', sum !== 100);
                save.disabled = sum !== 100;
            };
            inputs.forEach(function (input) { input.addEventListener('input', refresh); });
            document.getElementById('shares-equal').addEventListener('click', function () {
                var base = Math.floor(100 / inputs.length);
                inputs.forEach(function (input, i) { input.value = base + (i === 0 ? 100 - base * inputs.length : 0); });
                refresh();
            });
            refresh();
        })();
    </script>
@endpush
