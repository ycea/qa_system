import react from '@vitejs/plugin-react';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const apiTarget = env.VITE_API_PROXY_TARGET ?? 'http://localhost:8000';

  return {
    plugins: [react()],
    test: {
      coverage: {
        provider: 'v8',
        reporter: ['text', 'json-summary', 'html'], // <-- json-summary обязателен!
        reportsDirectory: './coverage',
      },
    },
    server: {
      port: 3000,
      host: true,
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: true,
          secure: false,
          headers: { Host: 'localhost' },
        },
        '/sanctum': {
          target: apiTarget,
          changeOrigin: true,
          secure: false,
          headers: { Host: 'localhost' },
        },
      },
    },
  };
});
