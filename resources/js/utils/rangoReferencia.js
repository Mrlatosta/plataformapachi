// Espejo en JS de App\Support\RangoReferencia (PHP). Cualquier cambio aquí debe
// hacerse también allá: ambos se prueban con tests/fixtures/rangos.json.
//
// Determina si un resultado quedó por arriba ('alto') o por debajo ('bajo') del
// intervalo de referencia que le toca al paciente (sexo, edad, nivel "Normal").

const EDAD_ADULTO = 18;
const NUMERO = '(-?\\d+(?:\\.\\d+)?)';

// Límite superior al inicio: "< 200", "menor a 200", "menos de 17", "hasta 200"
const PATRON_MAXIMO = new RegExp(`^(?:<=?|≤|menor(?:\\s+(?:a|de|que))?|menos\\s+de|inferior(?:\\s+a)?|hasta|maximo(?:\\s+de)?)\\s*${NUMERO}`);
// Límite inferior al inicio: "> 40", "mayor a 40", "mínimo 40"
const PATRON_MINIMO = new RegExp(`^(?:>=?|≥|mayor(?:\\s+(?:a|de|que))?|mas\\s+de|superior(?:\\s+a)?|minimo(?:\\s+de)?)\\s*${NUMERO}`);
// Rango en cualquier parte: "70 - 110", "0.5 a 1.2", "70 hasta 110"
const PATRON_RANGO = new RegExp(`${NUMERO}\\s*(?:-|a|hasta)\\s*${NUMERO}`);

const PATRON_SEXO = /\b(hombres?|varon(?:es)?|masculino|mujer(?:es)?|femenino)\b\s*:?\s*/g;
const PATRON_GRUPO_EDAD = /(?<![a-zñ])(ni(?:ñ|n)os?|pediatricos?|adultos?)(?![a-zñ])\s*:?\s*/g;
const PATRON_CORTE_EDAD = /(<=?|>=?|≥|≤)\s*(\d+)\s*a(?:ñ|n)os?\.?\s*/g;
const PATRON_ETIQUETA_NORMAL = /\b(?:valores?\s+)?(?:normal(?:es)?|negativo)\b\s*:?\s*(?=[<>≤≥\d-]|menor|menos|mayor|hasta|inferior|superior|maximo|minimo)/;

// Pares normal -> anormal de las referencias cualitativas (antidoping, serologías)
const CUALITATIVOS = {
    negativo: 'positivo',
    'no reactivo': 'reactivo',
    ausente: 'presente',
};

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

const sexoPaciente = (sexo) => {
    const texto = normalizar(sexo);
    if (!texto) return null;
    if (texto.startsWith('f') || texto.startsWith('muj')) return 'F';
    if (texto.startsWith('m') || texto.startsWith('h')) return 'M';
    return null;
};

// Parte el texto por etiquetas y se queda con el primer tramo de cada clave
const tramos = (texto, patron, clave) => {
    const etiquetas = [...texto.matchAll(patron)];
    if (etiquetas.length === 0) return null;

    const resultado = {};
    etiquetas.forEach((etiqueta, i) => {
        const inicio = etiqueta.index + etiqueta[0].length;
        const fin = etiquetas[i + 1]?.index ?? texto.length;
        const llave = clave(etiqueta[1]);
        if (!(llave in resultado)) resultado[llave] = texto.slice(inicio, fin).trim();
    });
    return resultado;
};

const cumpleCorte = (edad, operador, limite) => {
    if (operador === '<') return edad < limite;
    if (operador === '<=' || operador === '≤') return edad <= limite;
    if (operador === '>') return edad > limite;
    return edad >= limite;
};

const esNumero = (valor) => valor !== null && valor !== undefined && valor !== '' && !Number.isNaN(Number(valor));

/**
 * Tramo del texto de referencia que le toca al paciente. '' si depende del sexo y no se conoce.
 */
const tramoAplicable = (valorReferencia, sexo = null, edad = null) => {
    let texto = normalizar(valorReferencia);
    if (!texto) return '';

    // 1. Sexo
    let partes = tramos(texto, PATRON_SEXO, (e) => (e.startsWith('mujer') || e === 'femenino' ? 'F' : 'M'));
    if (partes) {
        const s = sexoPaciente(sexo);
        if (!s || !(s in partes)) return '';
        texto = partes[s];
    }

    // 2. Grupo de edad
    partes = tramos(texto, PATRON_GRUPO_EDAD, (e) => (e.startsWith('adult') ? 'adulto' : 'nino'));
    if (partes) {
        const grupo = esNumero(edad) && Number(edad) < EDAD_ADULTO ? 'nino' : 'adulto';
        texto = partes[grupo] ?? Object.values(partes)[0];
    }

    // 3. Cortes de edad
    const cortes = [...texto.matchAll(PATRON_CORTE_EDAD)];
    if (cortes.length > 0) {
        let elegido = null;
        for (let i = 0; i < cortes.length; i++) {
            const inicio = cortes[i].index + cortes[i][0].length;
            const fin = cortes[i + 1]?.index ?? texto.length;
            const tramo = texto.slice(inicio, fin).trim();
            if (elegido === null) elegido = tramo;
            if (esNumero(edad) && cumpleCorte(Number(edad), cortes[i][1], Number(cortes[i][2]))) {
                elegido = tramo;
                break;
            }
        }
        texto = elegido;
    }

    // 4. Nivel "Normal"
    const normal = texto.match(PATRON_ETIQUETA_NORMAL);
    if (normal) texto = texto.slice(normal.index + normal[0].length);

    return texto.trim();
};

const intervaloDeNiveles = (niveles) => {
    const normal = (niveles || []).find((nivel) => nivel.es_normal);
    if (!normal) return null;

    const min = esNumero(normal.desde) ? Number(normal.desde) : null;
    const max = esNumero(normal.hasta) ? Number(normal.hasta) : null;
    return min === null && max === null ? null : { min, max };
};

const intervaloDeTexto = (valorReferencia, sexo, edad) => {
    const texto = tramoAplicable(valorReferencia, sexo, edad);
    if (!texto) return null;

    let m = texto.match(PATRON_MAXIMO);
    if (m) return { min: null, max: parseFloat(m[1]) };

    m = texto.match(PATRON_MINIMO);
    if (m) return { min: parseFloat(m[1]), max: null };

    m = texto.match(PATRON_RANGO);
    if (m) {
        const a = parseFloat(m[1]);
        const b = parseFloat(m[2]);
        return a > b ? { min: b, max: a } : { min: a, max: b };
    }

    return null;
};

/**
 * Interpreta un valor de referencia: { tipo: 'vacio' | 'rango' | 'maximo' | 'minimo' | 'texto', min, max }.
 */
export const interpretarReferencia = (valorReferencia, sexo = null, edad = null) => {
    if (!normalizar(valorReferencia)) return { tipo: 'vacio' };

    const intervalo = intervaloDeTexto(valorReferencia, sexo, edad);
    if (!intervalo) return { tipo: 'texto' };
    if (intervalo.min !== null && intervalo.max !== null) return { tipo: 'rango', ...intervalo };
    return intervalo.max !== null ? { tipo: 'maximo', max: intervalo.max } : { tipo: 'minimo', min: intervalo.min };
};

export const direccionFueraRango = (resultado, valorReferencia, sexo = null, edad = null, niveles = null) => {
    const valor = aNumero(resultado);
    if (valor === null) return null;

    const intervalo = intervaloDeNiveles(niveles) ?? intervaloDeTexto(valorReferencia, sexo, edad);
    if (!intervalo) return null;

    if (intervalo.min !== null && valor < intervalo.min) return 'bajo';
    if (intervalo.max !== null && valor > intervalo.max) return 'alto';
    return null;
};

// 'negativo' para "Negativo", "NEGATIVO/NEGATIVE"...; null si no es cualitativa
const claveCualitativa = (valorReferencia) => {
    const referencia = normalizar(valorReferencia);
    if (!referencia || /\d/.test(referencia)) return null;

    return Object.keys(CUALITATIVOS).find(
        (normal) => referencia === normal || new RegExp(`^${normal}\\b`).test(referencia),
    ) ?? null;
};

/**
 * Para referencias como "Negativo" devuelve [normal, anormal] con la misma
 * capitalización que la referencia: ['NEGATIVO', 'POSITIVO']. Null si no aplica.
 */
export const opcionesCualitativas = (valorReferencia) => {
    const normal = claveCualitativa(valorReferencia);
    if (!normal) return null;

    let original = String(valorReferencia).trim();
    if (normalizar(original) !== normal) original = original.slice(0, normal.length);
    const anormal = CUALITATIVOS[normal];

    if (original === original.toUpperCase()) return [original, anormal.toUpperCase()];
    if (original[0] === original[0].toUpperCase()) return [original, anormal[0].toUpperCase() + anormal.slice(1)];
    return [original, anormal];
};

export const cualitativoAnormal = (resultado, valorReferencia) => {
    const normal = claveCualitativa(valorReferencia);
    const valor = normalizar(resultado);
    if (!normal || !valor) return false;

    return valor !== normal && valor !== normalizar(valorReferencia);
};

/**
 * { fuera_rango, direccion } — mismo criterio que RangoReferencia::evaluar en PHP.
 */
export const evaluarResultado = (resultado, valorReferencia, sexo = null, edad = null, niveles = null) => {
    const direccion = direccionFueraRango(resultado, valorReferencia, sexo, edad, niveles);
    return {
        fuera_rango: direccion !== null || cualitativoAnormal(resultado, valorReferencia),
        direccion,
    };
};

export const esFueraDeRango = (resultado, valorReferencia, sexo = null, edad = null, niveles = null) =>
    evaluarResultado(resultado, valorReferencia, sexo, edad, niveles).fuera_rango;

export const flechaRango = (direccion) => {
    if (direccion === 'alto') return '↑';
    if (direccion === 'bajo') return '↓';
    return '';
};
