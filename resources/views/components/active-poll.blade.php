@props(['poll'])

@php
    $hasVoted = (bool) session('poll_voted_'.$poll->getKey());
    $showResults = $poll->results_visibility === 'always' || ($poll->results_visibility === 'after_vote' && $hasVoted);
    $totalVotes = $poll->options->sum('votes_count');
@endphp

<section class="border-y border-slate-200 bg-white py-10 sm:py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl bg-blue-950 text-white shadow-xl lg:grid lg:grid-cols-[.72fr_1.28fr]">
            <div class="relative overflow-hidden p-7 sm:p-9">
                <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-orange-500/15"></div>
                <div class="relative">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-xl"><i class="fa-solid fa-square-poll-vertical"></i></span>
                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Günün anketi</p>
                    <h2 class="mt-2 text-2xl font-extrabold">{{ $poll->title }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Görüşünüz, içeriklerimizi ve hizmetlerimizi geliştirmemize yardımcı olur.</p>
                    <p class="mt-6 text-xs text-slate-400"><i class="fa-solid fa-users mr-2 text-orange-400"></i>{{ number_format($totalVotes, 0, ',', '.') }} katılım</p>
                </div>
            </div>

            <div class="bg-white p-7 text-slate-800 sm:p-9">
                <h3 class="text-lg font-extrabold leading-7 text-blue-950">{{ $poll->question }}</h3>

                @if(session('poll_status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('poll_status') }}</div>
                @elseif(session('poll_info'))
                    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm font-semibold text-blue-800"><i class="fa-solid fa-circle-info mr-2"></i>{{ session('poll_info') }}</div>
                @endif

                @error('option_id')
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800">{{ $message }}</div>
                @enderror

                @if($showResults)
                    <div class="mt-6 space-y-4">
                        @foreach($poll->options as $option)
                            @php $percentage = $totalVotes > 0 ? round(($option->votes_count / $totalVotes) * 100) : 0; @endphp
                            <div>
                                <div class="mb-1.5 flex justify-between gap-3 text-sm"><span class="font-semibold">{{ $option->text }}</span><span class="font-extrabold text-orange-600">%{{ $percentage }}</span></div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-orange-500" style="width: {{ $percentage }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @elseif($hasVoted)
                    <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Oyunuz kaydedildi. Sonuçlar anket kapandığında açıklanacaktır.</p>
                @else
                    <form method="post" action="{{ route('polls.vote', $poll) }}" class="mt-6 space-y-3">
                        @csrf
                        @foreach($poll->options as $option)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-sm font-semibold transition hover:border-orange-300 hover:bg-orange-50">
                                <input type="radio" name="option_id" value="{{ $option->getKey() }}" class="border-slate-300 text-orange-500 focus:ring-orange-500" required>
                                <span>{{ $option->text }}</span>
                            </label>
                        @endforeach
                        <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow transition hover:bg-orange-600"><i class="fa-solid fa-check-to-slot"></i>Oyumu gönder</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
