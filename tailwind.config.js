/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './includes/**/*.php',
    './account/**/*.php',
    './admin/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        vanta: {
          black: '#0A0A0A',
          jet: '#0F0F0F',
          dark: '#2A2A2A',
          light: '#CFCFCF',
          white: '#F5F5F5',
          lime: '#B7FF2A'
        }
      },
      fontFamily: {
        display: ['Barlow Condensed', 'Arial Narrow', 'sans-serif'],
        sans: ['Manrope', 'Arial', 'sans-serif']
      }
    }
  },
  plugins: []
};

