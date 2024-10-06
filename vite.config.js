import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    build: {
        chunkSizeWarningLimit: 500,
    },
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                "resources/css/filament/app/theme.css",
            ],
            refresh: true,
        }),
    ],
});
