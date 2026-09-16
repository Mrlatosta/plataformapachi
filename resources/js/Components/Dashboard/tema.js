// Paleta y formatos compartidos por los componentes del dashboard.
// Colores categoricos en orden fijo (validados para daltonismo): no reordenar.

export const color = {
    serie1: '#2a78d6',
    serie2: '#eb6834',
    serie3: '#1baf7a',
    neutro: '#b4b2a9',
    superficie: '#fcfcfb',
    tinta: '#0b0b0b',
    tinta2: '#52514e',
    tenue: '#898781',
    rejilla: '#e1e0d9',
    eje: '#c3c2b7',
    bueno: '#0ca30c',
    buenoTexto: '#006300',
    critico: '#d03b3b',
    alerta: '#fab219',
};

// Rampa secuencial azul (claro -> oscuro) para magnitud
export const rampaAzul = ['#cde2fb', '#b7d3f6', '#9ec5f4', '#86b6ef', '#6da7ec', '#5598e7', '#3987e5', '#2a78d6', '#256abf', '#1c5cab', '#184f95', '#104281', '#0d366b'];

const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const monedaCompacta = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', notation: 'compact', maximumFractionDigits: 1 });
const numero = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });

export const fmt = {
    moneda: (v) => moneda.format(v ?? 0),
    monedaCorta: (v) => (Math.abs(v ?? 0) >= 10000 ? monedaCompacta.format(v) : moneda.format(v ?? 0)),
    numero: (v) => numero.format(v ?? 0),
    pct: (v, dec = 0) => (v === null || v === undefined ? '—' : `${(v * 100).toFixed(dec)}%`),
    horas: (h) => {
        if (h === null || h === undefined) return '—';
        if (h < 1) return `${Math.round(h * 60)} min`;
        if (h < 48) return `${numero.format(h)} h`;
        return `${numero.format(h / 24)} días`;
    },
};

// Opciones base de Chart.js: rejilla tenue, tooltip claro, sin leyenda
export const opcionesBase = () => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 400 },
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#ffffff',
            titleColor: color.tinta,
            bodyColor: color.tinta2,
            borderColor: color.rejilla,
            borderWidth: 1,
            padding: 10,
            boxPadding: 4,
            usePointStyle: true,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { color: color.eje },
            ticks: { color: color.tenue, font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
        },
        y: {
            beginAtZero: true,
            grid: { color: color.rejilla },
            border: { display: false },
            ticks: { color: color.tenue, font: { size: 11 }, maxTicksLimit: 5 },
        },
    },
});
