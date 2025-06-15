import preset from "../../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    presets: [preset],
    content: [
        "./app/Filament/**/*.php", // Include all Filament-related PHP files
        "./resources/**/*.blade.php", // Include all Blade view files
        "./resources/css/filament/dashboard/**/*.css", // Include CSS files under admin directory
    ],
    safelist: [
        "bg-red-100", // Safelist for background color red
        "border-s-2", // Safelist for the left/right border style
        "border-orange-600", // Safelist for orange border color
        "opacity-30", // Safelist for opacity class
    ],
    theme: {
        extend: {
            colors: {
                "fi-sidebar": "#680714", // Custom color for fi-sidebar
            },
        },
    },
    plugins: [],
};
