// Utilidades de la captura rápida por jornadas (antidoping en empresas, campañas).

// Mismo responsable que usa Captura de Resultados por defecto
export const RESPONSABLE_PREDETERMINADO = 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS';

export const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

const SEXOS = {
    Masculino: ['m', 'h', 'masc', 'masculino', 'hombre', 'male'],
    Femenino: ['f', 'fem', 'femenino', 'mujer', 'female'],
};

const PALABRAS_SEXO = new Set(Object.values(SEXOS).flat());
const limpiarSexo = (valor) => normalizar(valor).replace(/\.$/, '');

/**
 * "M" es ambigua: Masculino en listas M/F, pero Mujer en listas H/M.
 * Con mEsMujer = true se toma como Femenino.
 */
export const interpretarSexo = (valor, mEsMujer = false) => {
    const texto = limpiarSexo(valor);
    if (texto === 'm') return mEsMujer ? 'Femenino' : 'Masculino';
    return Object.keys(SEXOS).find((sexo) => SEXOS[sexo].includes(texto)) ?? null;
};

// "35", "35 años", "35a" -> 35
const interpretarEdad = (valor) => {
    const partes = normalizar(valor).match(/^(\d{1,3})(?:\s*(?:anos|ano|a)\.?)?$/);
    return partes && +partes[1] <= 120 ? +partes[1] : null;
};

const armarFecha = (anio, mes, dia) => {
    const fecha = new Date(Date.UTC(anio, mes - 1, dia));
    const valida = fecha.getUTCFullYear() === anio && fecha.getUTCMonth() === mes - 1 && fecha.getUTCDate() === dia;
    return valida && fecha <= new Date() ? fecha.toISOString().slice(0, 10) : null;
};

/**
 * "15/03/1990", "15-03-90" o "1990-03-15" -> "1990-03-15". Null si no es una fecha válida.
 */
export const interpretarFecha = (valor) => {
    const texto = String(valor ?? '').trim();

    let partes = texto.match(/^(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})$/);
    if (partes) return armarFecha(+partes[1], +partes[2], +partes[3]);

    partes = texto.match(/^(\d{1,2})[/.-](\d{1,2})[/.-](\d{2}|\d{4})$/);
    if (!partes) return null;

    let anio = +partes[3];
    if (partes[3].length === 2) {
        anio += anio > new Date().getFullYear() % 100 ? 1900 : 2000;
    }
    return armarFecha(anio, +partes[2], +partes[1]);
};

export const calcularEdad = (fechaIso, referencia = new Date()) => {
    if (!fechaIso) return null;
    const [anio, mes, dia] = fechaIso.split('-').map(Number);
    let edad = referencia.getFullYear() - anio;
    if (referencia.getMonth() + 1 < mes || (referencia.getMonth() + 1 === mes && referencia.getDate() < dia)) edad--;
    return edad >= 0 ? edad : null;
};

/**
 * Interpreta una lista copiada de Excel (o escrita a mano), un colaborador por renglón.
 * Las columnas pueden venir en cualquier orden: lo que parece sexo, fecha de
 * nacimiento o edad se toma como tal y el resto del texto forma el nombre
 * (así "Pérez | López | Juan" queda como "Pérez López Juan").
 */
const separarCeldas = (renglon) => {
    const separador = renglon.includes('\t') ? '\t' : (renglon.includes(';') ? ';' : ',');
    const celdas = renglon.split(separador).map((c) => c.trim()).filter(Boolean);
    if (celdas.length > 1) return celdas;

    // Escrito a mano con espacios ("Juan Pérez M 35"): sexo, fecha y edad
    // se toman de las últimas palabras y el resto es el nombre
    const palabras = renglon.trim().split(/\s+/);
    const finales = [];
    while (palabras.length > 1) {
        const ultima = palabras[palabras.length - 1];
        const siguiente = palabras[palabras.length - 2];
        // "35 años" llega como dos palabras
        if (/^(anos|ano)$/.test(normalizar(ultima)) && /^\d{1,3}$/.test(siguiente)) {
            finales.unshift(palabras.splice(-2).join(' '));
        } else if (PALABRAS_SEXO.has(limpiarSexo(ultima)) || interpretarFecha(ultima) || interpretarEdad(ultima) !== null) {
            finales.unshift(palabras.pop());
        } else {
            break;
        }
    }
    return [palabras.join(' '), ...finales];
};

/**
 * Interpreta una lista copiada de Excel (o escrita a mano), un colaborador por renglón.
 * Las columnas pueden venir en cualquier orden: lo que parece sexo, fecha de
 * nacimiento o edad se toma como tal y el resto del texto forma el nombre
 * (así "Pérez | López | Juan" queda como "Pérez López Juan").
 */
export const interpretarLista = (texto) => {
    const renglones = String(texto ?? '').split(/\r?\n/).filter((r) => r.trim()).map(separarCeldas);

    // Si la lista usa H para hombre y no trae F, la M significa Mujer
    const valores = renglones.flat().map(limpiarSexo);
    const mEsMujer = valores.some((v) => v === 'h' || v === 'hombre') && !valores.some((v) => ['f', 'fem', 'femenino'].includes(v));

    return renglones
        .map((celdas) => {
            const persona = { nombre: '', sexo: null, fecha_nacimiento: null, edad: null };
            const nombre = [];

            for (const celda of celdas) {
                const sexo = !persona.sexo && interpretarSexo(celda, mEsMujer);
                const fecha = !persona.fecha_nacimiento && interpretarFecha(celda);
                const edad = persona.edad === null ? interpretarEdad(celda) : null;

                if (sexo) persona.sexo = sexo;
                else if (fecha) persona.fecha_nacimiento = fecha;
                else if (edad !== null) persona.edad = edad;
                else if (/[a-zñ]/i.test(normalizar(celda))) nombre.push(celda);
            }

            persona.nombre = nombre.join(' ').replace(/\s+/g, ' ');
            if (persona.fecha_nacimiento) persona.edad = calcularEdad(persona.fecha_nacimiento);

            return persona;
        })
        // Encabezados como "Nombre | Sexo | Edad"
        .filter((p) => p.nombre && !/^(nombre|nombres|apellido|apellidos|colaborador|empleado|no\.?|num)\b/.test(normalizar(p.nombre)));
};
