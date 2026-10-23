/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        vb: {
          50: '#fdf2f4',
          100: '#fce7ea',
          200: '#f9d2d8',
          300: '#f4aab6',
          400: '#ec768b',
          500: '#df4866',
          600: '#ba2e45',      // Vermelho Rubi Oficial do Logo Vila Boa
          700: '#b82e45',      // Botões oficiais do site vilaboa.com.br
          800: '#9c2337',      // Hover / Tom mais fechado
          900: '#831b2d',      // Vinho profundo
          dark: '#1f133a',     // Grafite / Ameixa escuro institucional
          charcoal: '#2e2c31', // Tom das letras do logotipo
          gray: '#727272',
          light: '#f8fafc'
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      boxShadow: {
        'mobile': '0 -4px 20px -2px rgba(0, 0, 0, 0.08)',
        'card': '0 8px 30px rgba(0, 0, 0, 0.06)',
      }
    },
  },
  plugins: [],
}
