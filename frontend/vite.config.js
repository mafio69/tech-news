import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    server: {
        host: true,  // ← PUBLICZNY HOST
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:3000',
                changeOrigin: true,
            }
        },
        allowedHosts: [  // ← NGROK DOMENY
            '.ngrok-free.app',
            '.ngrok.io',
            'misapprehensively-lightish-maia.ngrok-free.dev'
        ]
    }
})
