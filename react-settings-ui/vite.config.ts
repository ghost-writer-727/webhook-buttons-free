import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig(({ command }) => ({
  plugins: [react()],
  optimizeDeps: {
    exclude: ['lucide-react'],
  },
  resolve: {
    // Outside WordPress (dev server) there is no wp.i18n global; use the pass-through shim.
    alias: command === 'serve' ? { '@wordpress/i18n': resolve(__dirname, 'src/i18n-shim.ts') } : {},
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: false,
    // Build as regular app, not as a library
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
      // Translation functions come from WordPress itself (script dependency 'wp-i18n'). Keeping them
      // external means the built file still contains `.__("text", "webhook-buttons")` calls, which is
      // what wordpress.org (and `wp i18n make-pot`) scans to build the language packs.
      external: ['@wordpress/i18n'],
      output: {
        // WordPress loads main.js as a classic script, so module-scope names would become globals
        // and can clash with WP's own (`wp`). An IIFE keeps every name inside one function.
        format: 'iife',
        inlineDynamicImports: true,
        globals: { '@wordpress/i18n': 'wp.i18n' },
        // Fixed file name: wordpress.org keys JS translations by the md5 of this path. Never hash it.
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]'
      }
    }
  },
  define: {
    // Fix for "process is not defined" error
    'process.env': {}
  }
}));
