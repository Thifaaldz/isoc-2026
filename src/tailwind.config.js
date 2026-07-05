/** @type {import('tailwindcss').Config} */
const colors = require("tailwindcss/colors");
const defaultTheme = require("tailwindcss/defaultTheme");

function withOpacityValue(variable) {
    return ({ opacityValue }) => {
        if (opacityValue === undefined) {
            return `rgb(var(${variable}))`;
        }
        return `rgb(var(${variable}) / ${opacityValue})`;
    };
}

export default {
    content: [
        "./resources/**/*.blade.php",
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "on-error-container": "#93000a",
                "secondary-container": "#dbe2f9",
                "tertiary-fixed-dim": "#bdc7de",
                "secondary-fixed": "#dbe2f9",
                "on-tertiary": "#ffffff",
                "on-primary-fixed-variant": "#00468c",
                "surface-dim": "#d9dadb",
                "tertiary-fixed": "#d9e3fb",
                error: "#ba1a1a",
                "surface-container-highest": "#e1e3e4",
                "on-primary-container": "#fefcff",
                background: "#f8f9fa",
                "surface-variant": "#e1e3e4",
                "surface-container-high": "#e7e8e9",
                "inverse-surface": "#2e3132",
                "outline-variant": "#c2c6d4",
                "surface-container-lowest": "#ffffff",
                "surface-bright": "#f8f9fa",
                "primary-fixed": "#d6e3ff",
                "on-primary-fixed": "#001b3d",
                "on-background": "#191c1d",
                "on-secondary-fixed": "#141b2c",
                "secondary-fixed-dim": "#bfc6dc",
                "primary-fixed-dim": "#a9c7ff",
                "on-secondary-fixed-variant": "#3f4759",
                outline: "#727783",
                "inverse-primary": "#a9c7ff",
                "surface-container-low": "#f3f4f5",
                "on-primary": "#ffffff",
                "surface-container": "#edeeef",
                "on-tertiary-container": "#fefcff",
                "on-error": "#ffffff",
                secondary: "#565e71",
                "primary-container": "#3474cc",
                "on-surface": "#191c1d",
                "on-tertiary-fixed-variant": "#3d475a",
                "on-secondary": "#ffffff",
                "on-surface-variant": "#424752",
                "error-container": "#ffdad6",
                surface: "#f8f9fa",
                "on-secondary-container": "#5c6477",
                "tertiary-container": "#6b758a",
                "surface-tint": "#0f5db4",
                "on-tertiary-fixed": "#111c2d",
                tertiary: "#525c70",
                "inverse-on-surface": "#f0f1f2",
                primary: "#075bb1",
            },
            spacing: {
                gutter: "24px",
                "container-max": "1280px",
                "section-gap-md": "64px",
                "section-gap-lg": "96px",
                "margin-desktop": "32px",
                "margin-mobile": "16px",
            },
            fontFamily: {
                "label-sm": ["Inter", ...defaultTheme.fontFamily.sans],
                "headline-sm": ["Inter", ...defaultTheme.fontFamily.sans],
                "display-lg-mobile": ["Inter", ...defaultTheme.fontFamily.sans],
                "body-md": ["Inter", ...defaultTheme.fontFamily.sans],
                "body-lg": ["Inter", ...defaultTheme.fontFamily.sans],
                "display-lg": ["Inter", ...defaultTheme.fontFamily.sans],
                "label-md": ["Inter", ...defaultTheme.fontFamily.sans],
                "headline-md": ["Inter", ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                "label-sm": ["12px", { lineHeight: "18px", fontWeight: "600" }],
                "headline-sm": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                "display-lg-mobile": ["36px", { lineHeight: "44px", fontWeight: "700" }],
                "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                "display-lg": ["48px", { lineHeight: "60px", fontWeight: "700" }],
                "label-md": ["14px", { lineHeight: "20px", fontWeight: "500" }],
                "headline-md": ["30px", { lineHeight: "38px", fontWeight: "600" }],
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
}
