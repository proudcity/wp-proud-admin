import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  css: {
    preprocessorOptions: {
      scss: {
        api: 'legacy',
        includePaths: [
          'node_modules/bootstrap-sass/assets/stylesheets',
          'node_modules/bourbon/app/assets/stylesheets',
          'node_modules/proudcity-patterns/app'
        ]
      }
    }
  },
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        'styles/proud-admin': resolve(__dirname, 'assets/styles/proud-admin.scss')
      },
      output: {
        assetFileNames: '[name][extname]'
      }
    },
    sourcemap: true,
    cssMinify: true
  }
});
