import path from 'path';
import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    // Load env file based on `mode` in the current working directory.
    // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
    const env = loadEnv(mode, '.', '');
    return {
      server: {
        port: 3000,
        host: '0.0.0.0',
      },
      plugins: [react()],
      resolve: {
        alias: {
          '@': path.resolve(__dirname, 'src'),
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
              // Ensure CSS goes to the right file name
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
