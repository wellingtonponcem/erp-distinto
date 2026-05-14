/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./*.php",
    "./api/**/*.php",
    "./financeiro/**/*.php",
    "./gerenciamento/**/*.php",
    "./includes/**/*.php",
    "./precificacao/**/*.php",
    "./roteiros/**/*.php",
  ],
  theme: {
    extend: {
      fontFamily: { sans: ['Outfit', 'sans-serif'] },
      colors: {
        distinto: {
          ink: '#111111',
          paper: '#ffffff',
          line: '#ececec',
          muted: '#777777'
        }
      }
    },
  },
  plugins: [],
}
