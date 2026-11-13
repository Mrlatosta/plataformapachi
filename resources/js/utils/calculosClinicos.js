// T9: valores que se calculan solos a partir de otros analitos de la misma orden
// (VLDL, LDL, índices, lípidos totales, BUN, HOMA-IR, globulina, A/G, bilirrubina indirecta).
//
// Los analitos se reconocen por su nombre en el catálogo, así que funcionan en
// cualquier estudio (Química de 6, de 24, Perfil de lípidos...). Si un dato de
// origen falta, el campo se queda editable para capturarlo a mano.

export const NO_CALCULABLE_TG = 'No calculable (TG > 400)';

const normalizar = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();

/**
 * Clave interna del analito según su nombre (y unidad, para no confundir la
 * glucosa en orina del EGO con la de sangre). Null si no participa en cálculos.
 */
export const claveAnalito = (nombre, unidad = '') => {
    const n = normalizar(nombre);
    const u = normalizar(unidad);

    if (/homa/.test(n)) return 'homa_ir';
    if (/aterog/.test(n)) return 'indice_aterogenico';
    if (/ldl\s*\/\s*c?\s*hdl|indice\s+ldl/.test(n)) return 'indice_ldl_hdl';
    if (/lipidos totales/.test(n)) return 'lipidos_totales';
    if (/vldl|muy baja densidad/.test(n)) return 'vldl';
    if (/\bhdl\b|alta densidad/.test(n)) return 'hdl';
    if (/\bldl\b|baja densidad/.test(n)) return 'ldl';
    if (/^colesterol( total)?$/.test(n)) return 'ct';
    if (/^triglic/.test(n)) return 'tg';
    if (/nitrogeno ureico|\bbun\b/.test(n)) return 'bun';
    if (/^urea\b/.test(n)) return 'urea';
    if (/^glucosa\b/.test(n) && /mg\s*\/\s*dl/.test(u)) return 'glucosa';
    if (/^insulina\b/.test(n)) return 'insulina';
    if (/^proteinas totales/.test(n)) return 'proteinas_totales';
    if (/^albumina$/.test(n)) return 'albumina';
    if (/^globulina/.test(n)) return 'globulina';
    if (/relacion a\s*\/\s*g|^a\s*\/\s*g$/.test(n)) return 'relacion_ag';
    if (/^bilirrubina total/.test(n)) return 'bilirrubina_total';
    if (/^bilirrubina directa/.test(n)) return 'bilirrubina_directa';
    if (/^bilirrubina indirecta/.test(n)) return 'bilirrubina_indirecta';
    return null;
};

const sinTgAlto = (calculo) => (v) => (v.tg >= 400 ? NO_CALCULABLE_TG : calculo(v));
const siPositivo = (clave, calculo) => (v) => (v[clave] > 0 ? calculo(v) : null);

// En orden: los de arriba alimentan a los de abajo (VLDL -> LDL -> índice LDL/HDL)
const CALCULOS = {
    vldl: { requiere: ['tg'], formula: 'TG ÷ 5', decimales: 1, calcular: sinTgAlto((v) => v.tg / 5) },
    ldl: { requiere: ['ct', 'hdl', 'tg'], formula: 'CT − HDL − VLDL (Friedewald)', decimales: 1, calcular: sinTgAlto((v) => v.ct - v.hdl - v.tg / 5) },
    indice_aterogenico: { requiere: ['ct', 'hdl'], formula: 'CT ÷ HDL', decimales: 2, calcular: siPositivo('hdl', (v) => v.ct / v.hdl) },
    indice_ldl_hdl: { requiere: ['ldl', 'hdl'], formula: 'LDL ÷ HDL', decimales: 2, calcular: siPositivo('hdl', (v) => v.ldl / v.hdl) },
    lipidos_totales: { requiere: ['ct', 'tg'], formula: '2.27 × CT + TG + 62.3', decimales: 1, calcular: (v) => 2.27 * v.ct + v.tg + 62.3 },
    bun: { requiere: ['urea'], formula: 'Urea ÷ 2.14', decimales: 1, calcular: (v) => v.urea / 2.14 },
    homa_ir: { requiere: ['glucosa', 'insulina'], formula: 'Glucosa × Insulina ÷ 405 (en ayuno)', decimales: 2, calcular: (v) => (v.glucosa * v.insulina) / 405 },
    globulina: { requiere: ['proteinas_totales', 'albumina'], formula: 'Proteínas totales − Albúmina', decimales: 1, calcular: (v) => v.proteinas_totales - v.albumina },
    relacion_ag: { requiere: ['albumina', 'globulina'], formula: 'Albúmina ÷ Globulina', decimales: 2, calcular: siPositivo('globulina', (v) => v.albumina / v.globulina) },
    // 2 decimales: sus valores de referencia van de 0.01 a 0.70 mg/dL
    bilirrubina_indirecta: { requiere: ['bilirrubina_total', 'bilirrubina_directa'], formula: 'Bilirrubina total − Bilirrubina directa', decimales: 2, calcular: (v) => v.bilirrubina_total - v.bilirrubina_directa },
};

const aNumero = (valor) => {
    const texto = String(valor ?? '').trim().replace(',', '.');
    return /^-?\d+(\.\d+)?$/.test(texto) ? parseFloat(texto) : null;
};

/**
 * Llena los valores calculados de una orden. Recibe la lista de exámenes de cada
 * estudio (cada examen con nombre_examen, unidad y resultado) y los modifica en
 * su lugar: resultado, calculado (true = solo lectura) y formula.
 * Devuelve los exámenes cuyo resultado cambió, para volver a evaluar su rango.
 */
export const aplicarCalculos = (examenesPorEstudio) => {
    const entradas = examenesPorEstudio.flatMap((examenes, estudio) =>
        (examenes || []).map((examen) => ({ examen, estudio, clave: claveAnalito(examen.nombre_examen, examen.unidad) })),
    ).filter((entrada) => entrada.clave);

    const cambiados = [];

    // Valor de un analito: primero del mismo estudio, si no de cualquier otro de la orden
    const valorDe = (clave, estudio) => {
        const candidatos = entradas.filter((e) => e.clave === clave);
        const propio = candidatos.find((e) => e.estudio === estudio && aNumero(e.examen.resultado) !== null);
        const otro = candidatos.find((e) => aNumero(e.examen.resultado) !== null);
        return aNumero((propio ?? otro)?.examen.resultado);
    };

    for (const [clave, calculo] of Object.entries(CALCULOS)) {
        for (const entrada of entradas.filter((e) => e.clave === clave)) {
            const valores = {};
            const completos = calculo.requiere.every((origen) => {
                valores[origen] = valorDe(origen, entrada.estudio);
                return valores[origen] !== null;
            });

            const { examen } = entrada;
            examen.formula = calculo.formula;

            if (!completos) {
                // Si antes se calculó y ya falta un dato, se limpia para no dejar un valor viejo
                if (examen.calculado) {
                    examen.calculado = false;
                    if (examen.resultado !== '') {
                        examen.resultado = '';
                        cambiados.push(examen);
                    }
                }
                continue;
            }

            const valor = calculo.calcular(valores);
            const texto = valor === null ? '' : (typeof valor === 'string' ? valor : valor.toFixed(calculo.decimales));

            examen.calculado = true;
            if (examen.resultado !== texto) {
                examen.resultado = texto;
                cambiados.push(examen);
            }
        }
    }

    return cambiados;
};
