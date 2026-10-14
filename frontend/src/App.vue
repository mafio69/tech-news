<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 text-white p-8">
        <header class="max-w-4xl mx-auto mb-12">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent mb-4">
                Tech News AI
            </h1>
            <p class="text-xl opacity-80">
                Świeże tech newsy z automatycznymi tłumaczeniami PL/EN/ES
            </p>
        </header>

        <div v-if="loading" class="flex justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else-if="news.length === 0" class="text-center py-20">
            <p class="text-2xl opacity-60">
                Brak newsów. Uruchom: <code>php bin/console app:scrape-news</code>
            </p>
        </div>

        <div v-else class="max-w-4xl mx-auto space-y-6">
            <div
                v-for="item in news"
                :key="item.id"
                class="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-8 border border-slate-700 hover:border-blue-500/50 transition-all duration-300"
            >
                <!-- tytuł zależny od wybranego języka -->
                <h2 class="text-2xl font-bold mb-4">
                    {{ currentTitle(item) }}
                </h2>

                <!-- bańki językowe -->
                <div class="flex gap-3 mb-4">
                    <button
                        v-for="lang in ['original','en','pl','es']"
                        :key="lang"
                        @click="setLang(lang)"
                        class="px-3 py-1 rounded-full text-sm font-medium border transition hover:border-blue-400"
                        :class="preferredLang === lang
              ? 'bg-blue-500 text-white border-blue-500'
              : 'bg-slate-800 border-slate-600 text-slate-200'"
                    >
                        <span v-if="lang === 'original'">🌐 ORG</span>
                        <span v-else-if="lang === 'en'">US EN</span>
                        <span v-else-if="lang === 'pl'">PL PL</span>
                        <span v-else>ES ES</span>
                    </button>
                </div>

                <!-- podsumowanie (tylko jeśli jest) -->
                <p
                    v-if="currentSummary(item)"
                    class="text-lg mb-6 opacity-90 leading-relaxed"
                >
                    {{ currentSummary(item) }}
                </p>

                <!-- trzy wiersze z tytułami w językach – możesz zostawić lub kiedyś wyrzucić -->
                <div class="grid grid-cols-3 gap-4 mb-6 text-sm">
                    <div>
            <span class="inline-block w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">
              US
            </span>
                        <p class="font-medium">
                            {{ item.analysis?.en?.slice(0, 80) || item.title }}…
                        </p>
                    </div>
                    <div>
            <span class="inline-block w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">
              PL
            </span>
                        <p>
                            {{ item.analysis?.pl?.slice(0, 80) || item.title }}…
                        </p>
                    </div>
                    <div>
            <span class="inline-block w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">
              ES
            </span>
                        <p>
                            {{ item.analysis?.es?.slice(0, 80) || item.title }}…
                        </p>
                    </div>
                </div>

                <a
                    :href="item.url"
                    target="_blank"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 rounded-xl font-medium transition-all duration-300"
                >
                    Czytaj oryginalny artykuł →
                </a>
            </div>
        </div>

        <div class="mt-12 text-center opacity-60 text-sm">
            <p>Dane z RSS: HackerNews, TechCrunch, ArsTechnica | Analiza AI</p>
            <button
                @click="refreshNews"
                class="mt-4 px-6 py-2 bg-slate-700 hover:bg-slate-600 rounded-xl transition-colors"
            >
                🔄 Odśwież ({{ news.length }} newsów)
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const news = ref([])
const loading = ref(true)

const LABELS = {
    summary: 'Streszczenie',
    en: 'English',
    pl: 'Polski',
    es: 'Español',
}

const parsedAnalysis = (item) => {
    if (!item?.analysis) {
        return null
    }

    try {
        const data = typeof item.analysis === 'string'
            ? JSON.parse(item.analysis)
            : item.analysis

        if (!data.summary) {
            return null
        }

        return {
            summary: data.summary,
            en: data.en ?? data.summary,
            pl: data.pl ?? data.summary,
            es: data.es ?? data.summary,
        }
    } catch (e) {
        console.error('Invalid analysis JSON for item', item.id, e)
        return null
    }
}

const currentText = (item) => {
    const analysis = parsedAnalysis(item)
    if (!analysis) {
        return item.title
    }

    return analysis[lang.value] ?? analysis.summary ?? item.title
}

const getDomain = (url) => {
    try {
        return new URL(url).hostname.replace(/^www\./, '')
    } catch {
        return ''
    }
}

const formatDate = (iso) => {
    if (!iso) return ''
    const d = new Date(iso)
    return d.toLocaleString('pl-PL', {
        day: '2-digit',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    })
}

onMounted(async () => {
    const res = await fetch('/api/news')
    news.value = await res.json()
})
</script>

<template>
<main class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-zinc-950 text-slate-50">
        <header class="max-w-5xl mx-auto px-4 pt-10 pb-4 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-3">
        <span class="bg-gradient-to-r from-blue-400 via-cyan-300 to-violet-400 bg-clip-text text-transparent">
          Tech News AI
        </span>
            </h1>
            <p class="text-sm text-slate-400 mb-5">
                Wybrane tech‑newsy z AI‑owym streszczeniem – bez opuszczania strony.
            </p>

            <div class="inline-flex items-center gap-2 rounded-full bg-slate-800/80 border border-slate-700/70 px-3 py-1.5 text-xs md:text-sm">
                <span class="text-slate-300">Wybierz język:</span>
                <div class="flex gap-1">
                    <button
                        v-for="option in ['summary','en','pl','es']"
                        :key="option"
                        class="px-3 py-1 rounded-full font-medium transition-colors"
                        :class="lang === option
              ? 'bg-blue-500 text-white shadow-sm'
              : 'text-slate-200 hover:bg-slate-700/80'"
                        @click="lang = option"
                    >
                        {{ LABELS[option] }}
                    </button>
                </div>
            </div>
        </header>

        <section class="max-w-5xl mx-auto px-4 pb-10">
            <div
                v-for="item in news"
                :key="item.id"
                class="border-t border-slate-800/80 py-6"
            >
                <div class="flex items-baseline gap-2 text-xs text-slate-400 mb-2">
          <span class="font-semibold text-slate-300">
            {{ getDomain(item.url) || 'źródło' }}
          </span>
                    <span class="h-1 w-1 rounded-full bg-slate-500"></span>
                    <span>
            {{ formatDate(item.createdAt) }}
          </span>
                </div>

                <h2 class="text-xl md:text-2xl font-semibold mb-3">
                    <a
                        :href="item.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-blue-400 hover:text-blue-300 underline-offset-2 hover:underline"
                    >
                        {{ item.title }}
                    </a>
                </h2>

                <div class="mb-2 text-xs text-slate-400">
                    <span class="uppercase tracking-wide mr-1">WYBIERZ JĘZYK:</span>
                    <span class="font-semibold text-slate-200">
            {{ LABELS[lang] }}
          </span>
                </div>

                <div class="rounded-xl bg-slate-900/90 border border-slate-700/70 px-4 py-3 text-sm leading-relaxed text-slate-100 shadow-sm">
                    {{ currentText(item) }}
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
          <span class="font-semibold text-emerald-400">
            AI Analysis Ready
          </span>
                    <a
                        :href="item.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-blue-400 hover:text-blue-300 hover:underline underline-offset-2"
                    >
                        Czytaj pełny artykuł →
                    </a>
                </div>
            </div>

            <footer class="border-t border-slate-800/80 pt-4 mt-6 text-center text-xs text-slate-500">
                © 2025 Tech News AI
            </footer>
        </section>
    </main>
</template>
