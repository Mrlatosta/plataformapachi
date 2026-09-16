// Utilidades de la agenda. Las fechas "YYYY-MM-DD" se tratan como días de
// calendario (en UTC) para que la zona horaria del navegador no las mueva.

export const ESTILOS_ESTADO = {
    programada: { chip: 'bg-sky-100 text-sky-800', borde: 'border-l-sky-500', punto: 'bg-sky-500' },
    confirmada: { chip: 'bg-emerald-100 text-emerald-800', borde: 'border-l-emerald-500', punto: 'bg-emerald-500' },
    atendida: { chip: 'bg-violet-100 text-violet-800', borde: 'border-l-violet-500', punto: 'bg-violet-500' },
    no_asistio: { chip: 'bg-amber-100 text-amber-800', borde: 'border-l-amber-500', punto: 'bg-amber-500' },
    cancelada: { chip: 'bg-gray-100 text-gray-600', borde: 'border-l-gray-300', punto: 'bg-gray-400' },
};

// Estados que ya no ocupan lugar ni suman al ingreso esperado
export const ESTADOS_INACTIVOS = ['cancelada', 'no_asistio'];

export const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });

export const aFecha = (iso) => new Date(`${iso}T00:00:00Z`);
export const aIso = (fecha) => fecha.toISOString().slice(0, 10);
export const diaSemana = (iso) => aFecha(iso).getUTCDay();

export const sumarDias = (iso, dias) => {
    const fecha = aFecha(iso);
    fecha.setUTCDate(fecha.getUTCDate() + dias);
    return aIso(fecha);
};

// Lunes de la semana que contiene la fecha
export const inicioSemana = (iso) => sumarDias(iso, -((diaSemana(iso) + 6) % 7));

export const formatoFecha = (iso, opciones = {}) =>
    aFecha(iso).toLocaleDateString('es-MX', { timeZone: 'UTC', ...opciones });

export const aMinutos = (hhmm) => {
    const [h, m] = String(hhmm).split(':').map(Number);
    return h * 60 + m;
};

export const deMinutos = (minutos) =>
    `${String(Math.floor(minutos / 60)).padStart(2, '0')}:${String(minutos % 60).padStart(2, '0')}`;

export const hoyEn = (zona) => new Intl.DateTimeFormat('en-CA', { timeZone: zona }).format(new Date());

export const horaActualEn = (zona) =>
    new Intl.DateTimeFormat('en-GB', { timeZone: zona, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date());

/** Número listo para wa.me (México: agrega 52 a números de 10 dígitos). */
export function telefonoWhatsApp(telefono) {
    const digitos = String(telefono || '').replace(/\D/g, '');
    if (digitos.length === 10) return `52${digitos}`;
    if (digitos.length === 13 && digitos.startsWith('521')) return `52${digitos.slice(3)}`;
    return digitos.length >= 11 ? digitos : null;
}

export function mensajeWhatsApp(cita, tipo, direccionLaboratorio) {
    const fecha = formatoFecha(cita.fecha, { weekday: 'long', day: 'numeric', month: 'long' });
    const nombre = cita.nombre.trim().split(/\s+/)[0];
    const lugar = cita.modalidad === 'domicilio'
        ? `En tu domicilio: ${cita.direccion}`
        : `En BIOLAB: ${direccionLaboratorio}`;

    const lineas = [
        tipo === 'recordar'
            ? `Hola ${nombre}, te recordamos tu cita en BIOLAB.`
            : `Hola ${nombre}, te confirmamos tu cita en BIOLAB.`,
        `Fecha: ${fecha} a las ${cita.hora_inicio}`,
        lugar,
    ];
    if (cita.estudios?.length) lineas.push(`Estudios: ${cita.estudios.map((e) => e.nombre).join(', ')}`);
    if (cita.indicaciones) lineas.push(`Indicaciones: ${cita.indicaciones}`);
    lineas.push(tipo === 'recordar'
        ? '¿Nos confirmas tu asistencia?'
        : 'Si necesitas cambiar la hora, responde a este mensaje.');

    return lineas.join('\n');
}

/** Avisos que no bloquean: horario, fechas pasadas y empalmes. */
export function avisosCita(datos, { horario, zona, citasDelDia = [], idActual = null }) {
    const avisos = [];
    if (!datos.fecha || !datos.hora_inicio) return avisos;

    const inicio = aMinutos(datos.hora_inicio);
    const fin = inicio + Number(datos.duracion_minutos || 0);
    const horarioDia = horario[diaSemana(datos.fecha)];

    if (!horarioDia) {
        avisos.push('Ese día el laboratorio está cerrado.');
    } else if (inicio < aMinutos(horarioDia[0]) || fin > aMinutos(horarioDia[1])) {
        avisos.push(`La cita queda fuera del horario de atención de ese día (${horarioDia[0]} a ${horarioDia[1]}).`);
    }

    const hoy = hoyEn(zona);
    if (datos.fecha < hoy || (datos.fecha === hoy && datos.hora_inicio < horaActualEn(zona))) {
        avisos.push('La fecha y hora ya pasaron; úsalo solo para registrar una visita que ya ocurrió.');
    }

    const empalmes = citasDelDia.filter((c) =>
        c.id !== idActual
        && !ESTADOS_INACTIVOS.includes(c.estado)
        && aMinutos(c.hora_inicio) < fin
        && aMinutos(c.hora_fin) > inicio);

    if (empalmes.length) {
        avisos.push(`Se empalma con ${empalmes.length === 1 ? 'otra cita' : `${empalmes.length} citas`}: `
            + empalmes.map((c) => `${c.hora_inicio} ${c.nombre}`).join(', ') + '.');
        const aDomicilio = empalmes.filter((c) => c.modalidad === 'domicilio').length;
        if (datos.modalidad === 'domicilio' && aDomicilio) {
            avisos.push(`Ya hay ${aDomicilio === 1 ? 'una toma' : `${aDomicilio} tomas`} a domicilio a esa hora; revisa que alguien pueda cubrirla.`);
        }
    }

    return avisos;
}

/** Preparación sugerida según los estudios (mismas recomendaciones que la cotización). */
export function indicacionesSugeridas(estudios) {
    const nombres = estudios.map((e) => e.nombre.normalize('NFD').replace(/[̀-ͯ]/g, '').toUpperCase()).join(' | ');
    const partes = [];
    if (/QUIMICA|GLUCOSA|LIPID|COLESTEROL|TRIGLIC|HEPATIC|INSULINA|\bQS\d/.test(nombres)) {
        partes.push('Acudir con 8 a 12 horas de ayuno (puede tomar agua natural).');
    }
    if (/ORINA|\bEGO\b|UROCULTIVO/.test(nombres)) {
        partes.push('Traer la primera orina de la mañana en frasco estéril, previo aseo.');
    }
    if (/COPRO|HECES|FECAL/.test(nombres)) {
        partes.push('Traer la muestra de heces del mismo día en frasco limpio.');
    }
    if (/CULTIVO|EXUDADO/.test(nombres)) {
        partes.push('No iniciar antibióticos antes de la toma de muestra.');
    }
    return partes.join(' ');
}
