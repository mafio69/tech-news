<script setup>
import { ref, onMounted } from 'vue'

const news = ref([])
const lang = ref('summary') // 'summary' | 'en' | 'pl' | 'es'

const currentSummary = (item) => {
    let analysis = {}

    // item.analysis może być:
    // - stringiem z JSON-em (TEXT w DB)
    // - już tablicą (gdy backend zwrócił json_decode)
    if (typeof item.analysis === 'string') {
        try {
            analysis = JSON.parse(item.analysis)
        } catch (e) {
            analysis = {}
        }
    } else if (typeof item.analysis === 'object' && item.analysis !== null) {
        analysis = item.analysis
    }

    return analysis[lang.value] ?? analysis.summary ?? item.title
}

onMounted(async () => {
    const res = await fetch('/api/news')
    news.value = await res.json()
})
</script>

<template>
    <main class="min-h-screen bg-slate-900 text-white p-8">
        <header class="max-w-4xl mx-auto mb-8 text-center">
            <h1 class="text-5xl font-bold mb-6">Tech News AI</h1>

            <div class="inline-flex rounded-lg bg-slate-800 p-1 space-x-1">
                <button
                    class="px-4 py-2 rounded-md"
                    :class="lang === 'summary' ? 'bg-blue-500' : 'bg-transparent'"
                    @click="lang = 'summary'"
                >
                    Summary
                </button>
                <button
                    class="px-4 py-2 rounded-md"
                    :class="lang === 'en' ? 'bg-blue-500' : 'bg-transparent'"
                    @click="lang = 'en'"
                >
                    EN
                </button>
                <button
                    class="px-4 py-2 rounded-md"
                    :class="lang === 'pl' ? 'bg-blue-500' : 'bg-transparent'"
                    @click="lang = 'pl'"
                >
                    PL
                </button>
                <button
                    class="px-4 py-2 rounded-md"
                    :class="lang === 'es' ? 'bg-blue-500' : 'bg-transparent'"
                    @click="lang = 'es'"
                >
                    ES
                </button>
            </div>
        </header>

        <section class="max-w-4xl mx-auto space-y-4">
            <ul>
                <li
                    v-for="item in news"
                    :key="item.id"
                    class="pb-2"
                >
                    <a :href="item.url" class="text-blue-400 hover:underline">
                        {{ currentSummary(item) }}
                    </a>
                </li>
            </ul>
        </section>
    </main>
</template>
