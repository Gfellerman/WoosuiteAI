import path from 'path';
import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, '.', '');
    return {
      server: {
        port: 3000,
        host: '0.0.0.0',
      },
      plugins: [react()],
      define: {
        'process.env.API_KEY': JSON.stringify(env.GEMINI_API_KEY),
        'process.env.GEMINI_API_KEY': JSON.stringify(env.GEMINI_API_KEY)
      },
      resolve: {
        alias: {
          '@': path.resolve(__dirname, '.'),
        }
      },
      build: {
        outDir: 'assets', // Output to the 'assets' folder in the root
        emptyOutDir: true, // Clean the folder before build
        rollupOptions: {
          input: {
            app: path.resolve(__dirname, 'index.html'),
          },
          output: {
            entryFileNames: 'woosuite-app.js',
            assetFileNames: (assetInfo) => {
              if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                return 'woosuite-app.css';
              }
              return '[name][extname]';
            },
          },
        },
      },
    };
});
