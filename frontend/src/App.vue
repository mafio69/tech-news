<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 text-white p-8">
        <header class="max-w-4xl mx-auto mb-12">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent mb-4">
                Tech News AI
            </h1>
            <p class="text-xl opacity-80">Świeże tech newsy z automatycznymi tłumaczeniami PL/EN/ES</p>
        </header>

        <div v-if="loading" class="flex justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else-if="news.length === 0" class="text-center py-20">
            <p class="text-2xl opacity-60">Brak newsów. Uruchom: <code>php bin/console app:scrape-news</code></p>
        </div>

        <div v-else class="max-w-4xl mx-auto space-y-6">
            <div v-for="item in news" :key="item.id"
                 class="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-8 border border-slate-700 hover:border-blue-500/50 transition-all duration-300">
                <h2 class="text-2xl font-bold mb-4">{{ item.analysis?.pl || item.title }}</h2>
                <p class="text-lg mb-6 opacity-90 leading-relaxed">{{ item.analysis?.summary || 'Brak podsumowania' }}</p>
                <div class="grid grid-cols-3 gap-4 mb-6 text-sm">
                    <div><span class="inline-block w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">🇺🇸</span><p>{{ item.analysis?.en?.slice(0,60) || item.title }}...</p></div>
                    <div><span class="inline-block w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">🇵🇱</span><p>{{ item.analysis?.pl?.slice(0,60) || item.title }}...</p></div>
                    <div><span class="inline-block w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center text-xs font-bold mb-1">🇪🇸</span><p>{{ item.analysis?.es?.slice(0,60) || item.title }}...</p></div>
                </div>
                <a :href="item.url" target="_blank" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 rounded-xl font-medium transition-all duration-300">
                    Czytaj oryginalny artykuł →
                </a>
            </div>
        </div>

        <div class="mt-12 text-center opacity-60 text-sm">
            <p>Dane z RSS: HackerNews, TechCrunch, ArsTechnica | Analiza AI</p>
            <button @click="refreshNews" class="mt-4 px-6 py-2 bg-slate-700 hover:bg-slate-600 rounded-xl transition-colors">
                🔄 Odśwież ({{ news.length }} newsów)
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const news = ref([])
const loading = ref(true)

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

onMounted(fetchNews)
const refreshNews = () => fetchNews()
</script>
