import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    server: {
        port: 5173,
        allowedHosts: ['.ngrok-free.dev'],   // albo konkretnie: ['misapprehensively-lightish-maia.ngrok-free.dev']
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
            },
        },
    },
})
