/* Tema do Tchê & Cultura para o Tailwind compilado localmente (gera site/tailwind.css).
   As cores e as fontes são as mesmas que o index.php usa no modo CDN de reserva. */
module.exports = {
    content: ['../04 Desenvolvimento Web/index.php'],
    theme: {
        extend: {
            colors: {
                cream: '#F4EFE6', creamCard: '#FDFBF7',
                terracotta: '#D05A2B', terracottaDark: '#B8491C',
                darkGreen: '#0B261A', darkGreenCard: '#123324',
                accentGold: '#E6A123',
                mapNorte: '#229A88', mapNordeste: '#F06548', mapCentro: '#F3C151',
                mapSudeste: '#297BB3', mapSul: '#781FB6'
            },
            fontFamily: {
                display: ['"Space Grotesk"', 'Verdana', 'sans-serif'],
                serif: ['"Instrument Serif"', 'Georgia', 'serif'],
                sans: ['"Plus Jakarta Sans"', 'Segoe UI', 'sans-serif']
            }
        }
    }
};
