// Utilidades compartidas por Captura de resultados y Editar reporte:
// estados de los estudios (T2), marcado automático (T4), cálculos (T9)
// y resultado anterior del paciente (T1).
import { computed, ref } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { evaluarResultado } from '@/utils/rangoReferencia';
import { aplicarCalculos } from '@/utils/calculosClinicos';

export const ESTADOS_ESTUDIO = {
    pendiente: { etiqueta: 'Pendiente', clase: 'bg-gray-100 text-gray-700 ring-gray-300', ayuda: 'Sin resultados capturados' },
    capturado: { etiqueta: 'Por validar', clase: 'bg-amber-50 text-amber-800 ring-amber-300', ayuda: 'Capturado; falta que el Q.F.B. lo valide' },
    validado: { etiqueta: 'Validado', clase: 'bg-emerald-50 text-emerald-800 ring-emerald-300', ayuda: 'Listo para entregar' },
    entregado: { etiqueta: 'Entregado', clase: 'bg-blue-50 text-blue-800 ring-blue-300', ayuda: 'Ya se descargó o envió al paciente' },
};

export const ESTADOS_ORDEN = {
    en_proceso: { etiqueta: 'En proceso', clase: 'bg-gray-100 text-gray-700 ring-gray-300' },
    por_validar: { etiqueta: 'Por validar', clase: 'bg-amber-50 text-amber-800 ring-amber-300' },
    listo: { etiqueta: 'Listo', clase: 'bg-emerald-50 text-emerald-800 ring-emerald-300' },
    entregado: { etiqueta: 'Entregado', clase: 'bg-blue-50 text-blue-800 ring-blue-300' },
    sin_estudios: { etiqueta: 'Sin estudios en PDF', clase: 'bg-gray-100 text-gray-500 ring-gray-200' },
};

/** Permisos del usuario en sesión (T6). */
export const usePermisos = () => {
    const page = usePage();
    return computed(() => page.props.auth?.permisos ?? {});
};

export const formatoFechaCorta = (fecha) => {
    if (!fecha) return '';
    const d = new Date(String(fecha).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

export const formatoFechaHora = (fecha) => {
    if (!fecha) return '';
    const d = new Date(String(fecha).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: 'numeric', minute: '2-digit' });
};

/**
 * T4: marca fuera de rango en cuanto se escribe el valor, con el intervalo que le
 * toca al paciente. El laboratorio puede corregir la marca después a mano.
 */
export const marcarRango = (examen, paciente) => {
    const { fuera_rango, direccion } = evaluarResultado(
        examen.resultado,
        examen.valor_referencia,
        paciente?.sexo,
        paciente?.edad,
        examen.niveles,
    );
    examen.fuera_rango = fuera_rango;
    examen.direccion_rango = direccion;
};

/**
 * Corrección manual de la marca desde el selector de la celda:
 * '' normal, 'alto' / 'bajo' con flecha, 'texto' anormal sin flecha (*).
 */
export const aplicarMarcaManual = (examen, valor) => {
    examen.marca_manual = true;
    examen.fuera_rango = valor !== '';
    examen.direccion_rango = valor === 'alto' || valor === 'bajo' ? valor : null;
};

/**
 * T9 + T4: recalcula los valores derivados de toda la orden y vuelve a marcar
 * el rango de los que cambiaron. `listas` es un arreglo con los exámenes de cada estudio.
 */
export const recalcularOrden = (listas, paciente) => {
    aplicarCalculos(listas).forEach((examen) => marcarRango(examen, paciente));
};

const normalizarNombre = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();

/**
 * T1: último resultado de cada analito del paciente. Busca por examen y, si el
 * analito venía en otro estudio (p. ej. glucosa de la Química de 6), por nombre.
 */
export const useResultadosAnteriores = () => {
    const porExamen = ref({});
    const porNombre = ref({});
    const cargando = ref(false);

    const cargar = async (pacienteId, excluirReporte = null) => {
        porExamen.value = {};
        porNombre.value = {};
        if (!pacienteId) return;

        cargando.value = true;
        try {
            const { data } = await axios.get(`/api/pacientes/${pacienteId}/resultados-anteriores`, {
                params: excluirReporte ? { excluir_reporte: excluirReporte } : {},
            });
            const examenes = {};
            const nombres = {};
            data.forEach((fila) => {
                examenes[fila.examen_id] ??= fila;
                nombres[normalizarNombre(fila.nombre_examen)] ??= fila;
            });
            porExamen.value = examenes;
            porNombre.value = nombres;
        } catch (e) {
            console.error('No se pudo cargar el historial del paciente', e);
        } finally {
            cargando.value = false;
        }
    };

    const anteriorDe = (examenId, nombre) =>
        porExamen.value[examenId] ?? porNombre.value[normalizarNombre(nombre)] ?? null;

    const hayHistorial = computed(() => Object.keys(porExamen.value).length > 0);

    return { cargar, anteriorDe, hayHistorial, cargando };
};

/**
 * T13: tipos de muestra que se ofrecen en todos los estudios, además de los que el
 * administrador configure para cada uno.
 */
export const TIPOS_MUESTRA_COMUNES = ['SUERO', 'PLASMA', 'SANGRE TOTAL', 'ORINA', 'ORINA DE 24 HORAS', 'HECES', 'EXUDADO'];
