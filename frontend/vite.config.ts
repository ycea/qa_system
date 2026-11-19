// frontend/vite.config.ts
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    host: true,
    proxy: {
      '/api': {
        target: 'http://nginx:80', // <-- Имя сервиса Nginx и его порт внутри Docker
        changeOrigin: true,
        secure: false,
      },
      '/sanctum': {
        target: 'http://nginx:80', // <-- То же самое для Sanctum
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
