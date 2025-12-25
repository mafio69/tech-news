<script setup>
import { ref, onMounted } from 'vue'

const news = ref([])
const lang = ref('summary') // 'summary' | 'en' | 'pl' | 'es'

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
