import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'hsl(210, 70%, 45%)',
                    dark: 'hsl(210, 72%, 36%)',
                    light: 'hsl(210, 70%, 95%)',
                    50: 'hsl(210, 70%, 97%)',
                    100: 'hsl(210, 70%, 93%)',
                    500: 'hsl(210, 70%, 45%)',
                    600: 'hsl(210, 72%, 38%)',
                    700: 'hsl(210, 75%, 30%)',
                },
                success: {
                    DEFAULT: 'hsl(142, 71%, 45%)',
                    light: 'hsl(142, 71%, 95%)',
                },
                warning: {
                    DEFAULT: 'hsl(38, 92%, 50%)',
                    light: 'hsl(38, 92%, 95%)',
                },
                danger: {
                    DEFAULT: 'hsl(0, 72%, 51%)',
                    light: 'hsl(0, 72%, 96%)',
                },
                neutral: {
                    900: 'hsl(220, 13%, 18%)',
                    700: 'hsl(220, 13%, 35%)',
                    500: 'hsl(220, 13%, 50%)',
                    300: 'hsl(220, 13%, 75%)',
                    200: 'hsl(220, 14%, 90%)',
                    100: 'hsl(220, 14%, 96%)',
                    50: 'hsl(220, 14%, 98%)',
                },
                surface: '#ffffff',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: '12px',
                btn: '8px',
                modal: '16px',
            },
            boxShadow: {
                sm: '0 1px 2px rgba(0, 0, 0, 0.05)',
                md: '0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06)',
                lg: '0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)',
            },
        },
    },

    plugins: [forms],
};
