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

// globalny wybór języka
const preferredLang = ref('original')
const LANG_KEY = 'tn_lang'

const detectLanguage = () => {
    const navLang = navigator.language || (navigator.languages && navigator.languages[0]) || 'en'

    if (navLang.startsWith('pl')) preferredLang.value = 'pl'
    else if (navLang.startsWith('es')) preferredLang.value = 'es'
    else if (navLang.startsWith('en')) preferredLang.value = 'en'
    else preferredLang.value = 'original'
}

const loadLang = () => {
    const stored = localStorage.getItem(LANG_KEY)
    if (stored && ['original', 'en', 'pl', 'es'].includes(stored)) {
        preferredLang.value = stored
    } else {
        detectLanguage()
    }
}

const setLang = (lang) => {
    preferredLang.value = lang
    localStorage.setItem(LANG_KEY, lang)
}

const fetchNews = async () => {
    loading.value = true
    try {
        const response = await fetch('/api/news')
        news.value = await response.json()
    } catch (error) {
        console.error('Błąd:', error)
    } finally {
        loading.value = false
    }
}

// wybór tytułu wg języka
const currentTitle = (item) => {
    const a = item.analysis || {}
    switch (preferredLang.value) {
        case 'pl':
            return a.pl || item.title
        case 'es':
            return a.es || item.title
        case 'en':
            return a.en || item.title
        case 'original':
        default:
            return item.title
    }
}

// podsumowanie – traktujemy jako PL, ale pokazujemy też dla EN/ES
const currentSummary = (item) => {
    const a = item.analysis || {}
    if (['pl', 'en', 'es'].includes(preferredLang.value) && a.summary) {
        return a.summary
    }
    return ''
}

onMounted(() => {
    loadLang()
    fetchNews()
})

const refreshNews = () => fetchNews()
</script>
