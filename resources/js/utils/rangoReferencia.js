// Espejo en JS de App\Support\RangoReferencia (PHP).
// Determina si un resultado quedo por arriba ('alto') o por debajo ('bajo')
// del valor de referencia. Devuelve null cuando no se puede determinar.

const normalizar = (valor) => {
    if (valor === null || valor === undefined) return '';

    let texto = String(valor).trim().toLowerCase();

    texto = texto.replace(/[–—−]/g, '-');
    texto = texto.replace(/[áéíóú]/g, (letra) => ({ á: 'a', é: 'e', í: 'i', ó: 'o', ú: 'u' })[letra]);

    // 5,000 -> 5000 (miles) y 1,5 -> 1.5 (decimal)
    texto = texto.replace(/(\d),(\d{3})(?!\d)/g, '$1$2');
    texto = texto.replace(/(\d),(\d+)/g, '$1.$2');

    return texto.replace(/\s+/g, ' ');
};

const aNumero = (valor) => {
    const texto = normalizar(valor);
    if (!texto) return null;

    const match = texto.match(/-?\d+(?:\.\d+)?/);
    return match ? parseFloat(match[0]) : null;
};

// Rango: "70 - 110", "0.5 a 1.2", "70 hasta 110"
const PATRON_RANGO = /(-?\d+(?:\.\d+)?)\s*(?:-|a|hasta)\s*(-?\d+(?:\.\d+)?)/;
// Limite superior: "< 200", "menor a 200", "hasta 200"
const PATRON_MAXIMO = /^(?:<=?|menor(?:\s+(?:a|de|que))?|inferior(?:\s+a)?|hasta|maximo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/;
// Limite inferior: "> 40", "mayor a 40"
const PATRON_MINIMO = /^(?:>=?|mayor(?:\s+(?:a|de|que))?|superior(?:\s+a)?|minimo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/;

/**
 * Interpreta un valor de referencia: { tipo: 'vacio' | 'rango' | 'maximo' | 'minimo' | 'texto', min, max }.
 */
export const interpretarReferencia = (valorReferencia) => {
    const referencia = normalizar(valorReferencia);
    if (!referencia) return { tipo: 'vacio' };

    const rango = referencia.match(PATRON_RANGO);
    if (rango) {
        let min = parseFloat(rango[1]);
        let max = parseFloat(rango[2]);
        if (min > max) [min, max] = [max, min];
        return { tipo: 'rango', min, max };
    }

    const superior = referencia.match(PATRON_MAXIMO);
    if (superior) return { tipo: 'maximo', max: parseFloat(superior[1]) };

    const inferior = referencia.match(PATRON_MINIMO);
    if (inferior) return { tipo: 'minimo', min: parseFloat(inferior[1]) };

    return { tipo: 'texto' };
};

export const direccionFueraRango = (resultado, valorReferencia) => {
    const valor = aNumero(resultado);
    if (valor === null) return null;

    const referencia = interpretarReferencia(valorReferencia);

    if (referencia.tipo === 'rango') {
        if (valor < referencia.min) return 'bajo';
        if (valor > referencia.max) return 'alto';
        return null;
    }
    if (referencia.tipo === 'maximo') {
        return valor > referencia.max ? 'alto' : null;
    }
    if (referencia.tipo === 'minimo') {
        return valor < referencia.min ? 'bajo' : null;
    }

    return null;
};

// Pares normal -> anormal de las referencias cualitativas (antidoping, serologías)
const CUALITATIVOS = {
    negativo: 'positivo',
    'no reactivo': 'reactivo',
    ausente: 'presente',
};

/**
 * Para referencias como "Negativo" devuelve [normal, anormal] con la misma
 * capitalización que la referencia: ['NEGATIVO', 'POSITIVO']. Null si no aplica.
 */
export const opcionesCualitativas = (valorReferencia) => {
    const anormal = CUALITATIVOS[normalizar(valorReferencia)];
    if (!anormal) return null;

    const original = String(valorReferencia).trim();
    if (original === original.toUpperCase()) return [original, anormal.toUpperCase()];
    if (original[0] === original[0].toUpperCase()) return [original, anormal[0].toUpperCase() + anormal.slice(1)];
    return [original, anormal];
};

// Mismo criterio que RangoReferencia::fueraDeRango en PHP
export const esFueraDeRango = (resultado, valorReferencia) => {
    const valor = normalizar(resultado);
    if (!valor) return false;

    if (opcionesCualitativas(valorReferencia)) {
        return valor !== normalizar(valorReferencia);
    }

    return direccionFueraRango(resultado, valorReferencia) !== null;
};

export const flechaRango = (direccion) => {
    if (direccion === 'alto') return '↑';
    if (direccion === 'bajo') return '↓';
    return '';
};
