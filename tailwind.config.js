/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './includes/**/*.php',
    './template-parts/**/*.php',
    './templates/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: { sans: ['IRANYekanXVF', 'sans-serif'] },
      colors: {
        brand: { 50: '#f5f1ff', 100: '#ece4ff', 500: '#7042f5', 600: '#5b2be8', 700: '#4a1fd0', 900: '#17152b' },
      },
    },
  },
  plugins: [],
};
