import react from '@vitejs/plugin-react';
import fs from 'node:fs';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

// Served by MAMP over HTTPS at oric-lms.local:8890, so the dev server has to be
// HTTPS on the same host too — a plain http://localhost:5173 is blocked as mixed content.
const host = 'oric-lms.local';
const certs = '/Applications/MAMP/Library/OpenSSL/certs';
const tls = fs.existsSync(`${certs}/${host}.key`)
    ? {
        key: fs.readFileSync(`${certs}/${host}.key`),
        cert: fs.readFileSync(`${certs}/${host}.crt`),
    }
    : undefined;

export default defineConfig({
    server: {
        host,
        https: tls,
        cors: true,
        hmr: { host },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
