import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/filament/dashboard/theme.css", // Main entry file
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            input: {
                // Primary entry file
                main: "resources/css/filament/dashboard/theme.css",
            },
            output: {
                // Set the fixed name for the final CSS file in resources/css
                assetFileNames: "css/filament/style.css", // Path inside resources/css
            },
        },
    },
});
