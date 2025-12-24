<script setup>
import { ref, onMounted } from 'vue'

const news = ref([])
const lang = ref('summary') // 'summary' | 'en' | 'pl' | 'es'

onMounted(async () => {
    const res = await fetch('/api/news') // dzięki proxy
    news.value = await res.json()
})
</script>

<template>
    <main class="p-4">
        <div class="mb-4 space-x-2">
            <button @click="lang = 'summary'">Summary</button>
            <button @click="lang = 'en'">EN</button>
            <button @click="lang = 'pl'">PL</button>
            <button @click="lang = 'es'">ES</button>
        </div>

        <ul>
            <li v-for="item in news" :key="item.id" class="mb-3">
                <h2 class="font-bold">{{ item.title }}</h2>
                <p>
                    {{ item.analysis?.[lang] ?? item.title }}
                </p>
            </li>
        </ul>
    </main>
</template>


