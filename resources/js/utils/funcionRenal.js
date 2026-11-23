// T10: TFGe (CKD-EPI 2021 por creatinina, 2012 por cistatina C, 2021 combinada)
// y relación albúmina/creatinina en orina (RAC). Se calculan solos a partir de
// lo capturado y de la edad y el sexo del paciente; no se guardan como analitos.
//
// Misma lógica en PHP: app/Support/FuncionRenal.php (para el PDF). Si cambias
// algo aquí, cámbialo allá; tests/js/funcionRenal.test.mjs y
// tests/Unit/FuncionRenalTest.php prueban ambos con los casos del ticket.

export const UNIDAD_TFGE = 'mL/min/1.73 m²';
export const UNIDAD_RAC = 'mg/g';

export const CATEGORIAS_G = [
    { codigo: 'G1', desde: 90, hasta: null, rango: '≥ 90', nombre: 'Normal' },
    { codigo: 'G2', desde: 60, hasta: 89, rango: '60 – 89', nombre: 'Levemente disminuida' },
    { codigo: 'G3a', desde: 45, hasta: 59, rango: '45 – 59', nombre: 'Leve a moderadamente disminuida' },
    { codigo: 'G3b', desde: 30, hasta: 44, rango: '30 – 44', nombre: 'Moderada a gravemente disminuida' },
    { codigo: 'G4', desde: 15, hasta: 29, rango: '15 – 29', nombre: 'Gravemente disminuida' },
    { codigo: 'G5', desde: null, hasta: 14, rango: '< 15', nombre: 'Falla renal' },
];

export const CATEGORIAS_A = [
    { codigo: 'A1', rango: '< 30', nombre: 'Normal o levemente aumentada' },
    { codigo: 'A2', rango: '30 – 300', nombre: 'Moderadamente aumentada' },
    { codigo: 'A3', rango: '> 300', nombre: 'Gravemente aumentada' },
];

export const NOTA_TFGE = 'La TFG estimada no debe interpretarse sola para concluir daño renal; considere otros marcadores como la relación albúmina/creatinina. Se calcula con la ecuación CKD-EPI 2021, sin coeficiente racial. En pacientes con TFGe por creatinina de 45–59 mL/min/1.73 m² y relación albúmina/creatinina menor de 30 mg/g, las guías sugieren medir cistatina C para confirmar. No es válida en embarazo, insuficiencia renal aguda, amputaciones o masa muscular muy alta o muy baja.';

export const OBSERVACIONES_PERFIL = [
    'Ecuaciones validadas para adultos (≥ 18 años). La TFGe no es válida con función renal inestable o lesión renal aguda.',
    'La creatinina varía con la masa muscular y la dieta; la cistatina C puede alterarse con disfunción tiroidea y corticoides.',
    'Un ACR elevado debe confirmarse en 2 de 3 muestras en 3 a 6 meses; el ejercicio intenso, la fiebre, la infección urinaria y la menstruación lo elevan de forma transitoria.',
];

/** Observaciones de la hoja de perfil renal si el estudio de Cistatina C no tiene leyenda propia. */
export const OBSERVACIONES_PERFIL_PREDETERMINADAS = [NOTA_TFGE, ...OBSERVACIONES_PERFIL].join(' ');

/** 'F' o 'M' a partir de lo que guarde el sistema ('Femenino', 'masculino', 'F'...). */
export const sexoCorto = (sexo) => {
    const s = String(sexo ?? '').trim().toLowerCase();
    if (/^(f|mujer)/.test(s)) return 'F';
    if (/^(m|hombre|var)/.test(s)) return 'M';
    return null;
};

const adulto = (edad) => Number.isFinite(Number(edad)) && edad !== '' && edad !== null && Number(edad) >= 18;

// ---------- Fórmulas (tal cual el ticket) ----------

export const tfgeCreatinina = (creatinina, edad, sexo) => {
    const s = sexoCorto(sexo);
    if (!(creatinina > 0) || !adulto(edad) || !s) return null;
    const k = s === 'F' ? 0.7 : 0.9;
    const a = s === 'F' ? -0.241 : -0.302;
    const r = creatinina / k;
    let tfg = 142 * Math.pow(Math.min(r, 1), a) * Math.pow(Math.max(r, 1), -1.2) * Math.pow(0.9938, Number(edad));
    if (s === 'F') tfg *= 1.012;
    return Math.round(tfg);
};

export const tfgeCistatina = (cistatina, edad, sexo) => {
    const s = sexoCorto(sexo);
    if (!(cistatina > 0) || !adulto(edad) || !s) return null;
    const r = cistatina / 0.8;
    let tfg = 133 * Math.pow(Math.min(r, 1), -0.499) * Math.pow(Math.max(r, 1), -1.328) * Math.pow(0.996, Number(edad));
    if (s === 'F') tfg *= 0.932;
    return Math.round(tfg);
};

export const tfgeCombinada = (creatinina, cistatina, edad, sexo) => {
    const s = sexoCorto(sexo);
    if (!(creatinina > 0) || !(cistatina > 0) || !adulto(edad) || !s) return null;
    const k = s === 'F' ? 0.7 : 0.9;
    const a = s === 'F' ? -0.219 : -0.144;
    const rc = creatinina / k;
    const rs = cistatina / 0.8;
    let tfg = 135 * Math.pow(Math.min(rc, 1), a) * Math.pow(Math.max(rc, 1), -0.544)
        * Math.pow(Math.min(rs, 1), -0.323) * Math.pow(Math.max(rs, 1), -0.778)
        * Math.pow(0.9961, Number(edad));
    if (s === 'F') tfg *= 0.963;
    return Math.round(tfg);
};

/** RAC en mg/g con un decimal. Albúmina en mg/L (×100) o mg/dL (×1000). */
export const relacionAlbuminaCreatinina = (albumina, unidadAlbumina, creatininaOrina) => {
    if (!(albumina >= 0) || !(creatininaOrina > 0) || albumina === null) return null;
    const factor = /mg\s*\/\s*dl/i.test(String(unidadAlbumina ?? '')) ? 1000 : 100;
    return Math.round((albumina / creatininaOrina) * factor * 10) / 10;
};

export const categoriaG = (tfge) => {
    if (tfge === null || tfge === undefined) return null;
    if (tfge >= 90) return CATEGORIAS_G[0];
    if (tfge >= 60) return CATEGORIAS_G[1];
    if (tfge >= 45) return CATEGORIAS_G[2];
    if (tfge >= 30) return CATEGORIAS_G[3];
    if (tfge >= 15) return CATEGORIAS_G[4];
    return CATEGORIAS_G[5];
};

export const categoriaA = (rac) => {
    if (rac === null || rac === undefined) return null;
    if (rac < 30) return CATEGORIAS_A[0];
    if (rac <= 300) return CATEGORIAS_A[1];
    return CATEGORIAS_A[2];
};

// ---------- Reconocer los analitos de la orden ----------

const normalizar = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();

const aNumero = (valor) => {
    const texto = String(valor ?? '').trim().replace(',', '.');
    return /^\d+(\.\d+)?$/.test(texto) ? parseFloat(texto) : null;
};

/**
 * 'creatinina' (suero), 'cistatina', 'albumina_orina', 'creatinina_orina' o null.
 * La muestra del estudio distingue la creatinina en orina de la sérica.
 */
// El estudio es de orina si su muestra o su nombre lo dicen ("Relación albúmina
// creatinina en orina al azar", "Microalbuminuria"...), aunque el analito se llame
// solo "Creatinina" o "Albúmina".
const esDeOrina = (muestraEstudio, nombreEstudio) => {
    const m = normalizar(muestraEstudio);
    const e = normalizar(nombreEstudio);
    return /orina|urin/.test(m) || /orina|urin|microalbumin/.test(e) || (/albumin/.test(e) && /creatinin/.test(e));
};

export const claveRenal = (nombre, unidad, muestraEstudio = '', nombreEstudio = '') => {
    const n = normalizar(nombre);
    const u = normalizar(unidad);
    // "Creatinina sérica" dentro de una depuración de 24 h sigue siendo de sangre
    const sangre = /seric|suero|sangre|plasma/.test(n);
    const orina = !sangre && (/orina|urin/.test(n) || esDeOrina(muestraEstudio, nombreEstudio));
    // Orina de 24 h (depuración, microalbúmina en mg/24 h) no entra en la relación al azar
    const de24h = /24\s*h/.test([n, u, normalizar(nombreEstudio), normalizar(muestraEstudio)].join(' '));

    // El propio renglón de la relación albúmina/creatinina: se llena solo
    if ((/albumin/.test(n) && /creatinin/.test(n)) || /\b(acr|rac)\b/.test(n)) return (orina || /albumin/.test(n)) && !de24h ? 'rac' : null;
    if (/relacion|indice|depuracion|aclaramiento|\/|24 ?h/.test(n)) return null;
    if (/cistatina/.test(n)) return 'cistatina';
    if (/microalbumin/.test(n) || (/^albumina/.test(n) && orina)) return de24h ? null : 'albumina_orina';
    if (/^creatinina\b/.test(n) && (/mg\s*\/\s*dl/.test(u) || (orina && u === ''))) {
        if (!orina) return 'creatinina';
        return de24h ? null : 'creatinina_orina';
    }
    return null;
};

export const FORMULA_RAC = 'albúmina (mg/L) ÷ creatinina (mg/dL) × 100';

/**
 * Si el estudio trae su propio renglón "Relación albúmina/creatinina", lo llena
 * (solo lectura, AUTO) con la albúmina y la creatinina en orina de la orden.
 * Modifica los exámenes en su lugar y devuelve los que cambiaron.
 */
export const aplicarRelacionAlbuminaCreatinina = (estudios) => {
    const ubicados = [];
    (estudios || []).forEach((estudio) => {
        (estudio.examenes || []).forEach((examen) => {
            const clave = claveRenal(examen.nombre_examen, examen.unidad, estudio.tipo_muestra, estudio.nombre);
            if (clave) ubicados.push({ clave, examen, valor: aNumero(examen.resultado) });
        });
    });

    const alb = ubicados.find((u) => u.clave === 'albumina_orina' && u.valor !== null);
    const cr = ubicados.find((u) => u.clave === 'creatinina_orina' && u.valor !== null);
    const rac = alb && cr ? relacionAlbuminaCreatinina(alb.valor, alb.examen.unidad, cr.valor) : null;

    const cambiados = [];
    ubicados.filter((u) => u.clave === 'rac').forEach(({ examen }) => {
        examen.formula = FORMULA_RAC;
        if (rac === null) {
            if (examen.calculado) {
                examen.calculado = false;
                if (examen.resultado !== '') { examen.resultado = ''; cambiados.push(examen); }
            }
            return;
        }
        examen.calculado = true;
        const texto = rac.toFixed(1);
        if (examen.resultado !== texto) { examen.resultado = texto; cambiados.push(examen); }
    });
    return cambiados;
};

/**
 * Valores calculados de una orden.
 * `estudios`: [{ tipo_muestra, examenes: [{ nombre_examen, unidad, resultado }] }]
 * `paciente`: { edad, sexo }
 *
 * Devuelve:
 *  - filas: { 'iEstudio:iExamen': [fila, ...] } renglones AUTO que van debajo de ese examen
 *  - valores: creatinina, cistatina, tfge_cr, tfge_cis, tfge_comb, rac, G y A (KDIGO)
 */
export const analizarFuncionRenal = (estudios, paciente) => {
    const ubicados = [];
    (estudios || []).forEach((estudio, i) => {
        (estudio.examenes || []).forEach((examen, j) => {
            const clave = claveRenal(examen.nombre_examen, examen.unidad, estudio.tipo_muestra, estudio.nombre);
            if (clave) ubicados.push({ clave, i, j, examen, valor: aNumero(examen.resultado) });
        });
    });

    const primero = (clave) => ubicados.find((u) => u.clave === clave && u.valor !== null) ?? null;
    const cr = primero('creatinina');
    const cis = primero('cistatina');
    const alb = primero('albumina_orina');
    const crOrina = primero('creatinina_orina');

    const { edad, sexo } = paciente || {};
    const valores = {
        creatinina: cr?.valor ?? null,
        cistatina: cis?.valor ?? null,
        tfge_cr: cr ? tfgeCreatinina(cr.valor, edad, sexo) : null,
        tfge_cis: cis ? tfgeCistatina(cis.valor, edad, sexo) : null,
        tfge_comb: cr && cis ? tfgeCombinada(cr.valor, cis.valor, edad, sexo) : null,
        rac: alb && crOrina ? relacionAlbuminaCreatinina(alb.valor, alb.examen.unidad, crOrina.valor) : null,
    };
    // KDIGO: G de la combinada; sin cistatina, de la de creatinina (o la de cistatina sola)
    valores.tfge_kdigo = valores.tfge_comb ?? valores.tfge_cr ?? valores.tfge_cis;
    valores.G = categoriaG(valores.tfge_kdigo);
    valores.A = categoriaA(valores.rac);

    const filaTfge = (clave, nombre, valor, formula) => ({
        clave, nombre, valor, formula,
        texto: String(valor),
        unidad: UNIDAD_TFGE,
        referencia: '≥ 90',
        bajo: valor < 60,
        alto: false,
        categoria: categoriaG(valor),
    });

    const filas = {};
    const agregar = (ubicado, fila) => {
        if (!ubicado || fila.valor === null) return;
        (filas[`${ubicado.i}:${ubicado.j}`] ??= []).push(fila);
    };

    // Debajo de cada creatinina sérica capturada, su TFGe
    ubicados.filter((u) => u.clave === 'creatinina' && u.valor !== null).forEach((u) => {
        agregar(u, filaTfge('tfge_cr', 'Tasa de filtración glomerular estimada (TFGe)', tfgeCreatinina(u.valor, edad, sexo), 'CKD-EPI 2021'));
    });

    // Debajo de la cistatina: TFGe por cistatina y, si hay creatinina, la combinada
    if (cis) {
        agregar(cis, filaTfge('tfge_cis', 'TFGe por cistatina C', valores.tfge_cis, 'CKD-EPI 2012'));
        agregar(cis, filaTfge('tfge_comb', 'TFGe combinada creatinina + cistatina C', valores.tfge_comb, 'CKD-EPI 2021'));
    }

    // RAC debajo del último de los dos que aparezca (albúmina o creatinina en orina),
    // salvo que el estudio ya tenga su renglón de la relación (ese se llena solo)
    if (valores.rac !== null && !ubicados.some((u) => u.clave === 'rac')) {
        const ultimo = [alb, crOrina].sort((a, b) => a.i - b.i || a.j - b.j)[1];
        agregar(ultimo, {
            clave: 'rac',
            nombre: 'Relación albúmina/creatinina (ACR)',
            valor: valores.rac,
            texto: valores.rac.toFixed(1),
            unidad: UNIDAD_RAC,
            referencia: '< 30',
            formula: FORMULA_RAC,
            bajo: false,
            alto: valores.rac >= 30,
            categoria: valores.A,
        });
    }

    return { filas, valores };
};
