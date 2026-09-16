<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CampoImagen from '@/Components/Landing/CampoImagen.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    estudiosVisibles: { type: Number, default: 0 },
    estudiosTotal: { type: Number, default: 0 },
});

const ZONA = 'America/Cancun';
const hoy = () => new Intl.DateTimeFormat('en-CA', { timeZone: ZONA }).format(new Date());
const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 2 });
const fechaCorta = (iso) => iso
    ? new Date(`${iso}T00:00:00Z`).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })
    : '';

const pestanas = [
    { valor: 'promociones', etiqueta: 'Promociones' },
    { valor: 'publicaciones', etiqueta: 'Blog y galería' },
];
const pestana = ref('promociones');

// ---------- Avisos ----------
const aviso = ref(null);
let temporizadorAviso;
const mostrarAviso = (texto, tipo = 'ok') => {
    aviso.value = { texto, tipo };
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => { aviso.value = null; }, 4000);
};

const mensajeDeError = (err, porDefecto) => err.response?.status === 422
    ? 'Revisa los campos marcados en rojo.'
    : (err.response?.data?.message || porDefecto);

// Convierte el formulario a FormData (los booleanos viajan como 1/0)
const aFormData = (datos, archivo) => {
    const fd = new FormData();
    Object.entries(datos).forEach(([clave, valor]) => {
        if (typeof valor === 'boolean') fd.append(clave, valor ? '1' : '0');
        else fd.append(clave, valor ?? '');
    });
    if (archivo) fd.append('imagen', archivo);
    return fd;
};

// ======================================================
// Promociones
// ======================================================
const promociones = ref([]);
const cargandoPromociones = ref(true);

const promocionVacia = () => ({
    titulo: '',
    descripcion: '',
    incluye: '',
    precio_actual: '',
    precio_anterior: '',
    etiqueta: '',
    etiqueta_tono: 'verde',
    destacada: false,
    activa: true,
    vigente_desde: '',
    vigente_hasta: '',
    cupos_restantes: '',
    mensaje_whatsapp: '',
    orden: 0,
    imagen_alt: '',
    quitar_imagen: false,
});

const modalPromocion = ref(false);
const promocionId = ref(null);
const formPromocion = ref(promocionVacia());
const archivoPromocion = ref(null);
const imagenActualPromocion = ref(null);
const erroresPromocion = ref({});
const errorGeneralPromocion = ref('');
const guardandoPromocion = ref(false);

const tonos = [
    { valor: 'verde', etiqueta: 'Verde', clase: 'bg-green-700' },
    { valor: 'rosa', etiqueta: 'Rosa', clase: 'bg-pink-700' },
    { valor: 'marino', etiqueta: 'Marino', clase: 'bg-indigo-900' },
];

const cargarPromociones = async () => {
    cargandoPromociones.value = true;
    try {
        promociones.value = (await axios.get('/api/landing/promociones')).data;
    } catch (err) {
        mostrarAviso(mensajeDeError(err, 'No pudimos cargar las promociones.'), 'error');
    } finally {
        cargandoPromociones.value = false;
    }
};

const estadoPromocion = (p) => {
    const dia = hoy();
    if (!p.activa) return { texto: 'Pausada', clase: 'bg-gray-100 text-gray-700' };
    if (p.vigente_hasta && p.vigente_hasta < dia) return { texto: 'Vencida', clase: 'bg-red-100 text-red-700' };
    if (p.cupos_restantes === 0) return { texto: 'Sin lugares', clase: 'bg-red-100 text-red-700' };
    if (p.vigente_desde && p.vigente_desde > dia) return { texto: `Inicia ${fechaCorta(p.vigente_desde)}`, clase: 'bg-amber-100 text-amber-800' };
    return { texto: 'Visible en /inicio', clase: 'bg-emerald-100 text-emerald-800' };
};

const descuentoFormulario = computed(() => {
    const actual = Number(formPromocion.value.precio_actual);
    const anterior = Number(formPromocion.value.precio_anterior);
    if (!actual || !anterior) return '';
    if (anterior <= actual) return 'El precio normal debe ser mayor al de promoción.';
    return `Se mostrará -${Math.round((1 - actual / anterior) * 100)}% · ahorro de ${moneda.format(anterior - actual)}`;
});

const abrirPromocion = (p = null) => {
    promocionId.value = p?.id ?? null;
    formPromocion.value = p
        ? {
            ...promocionVacia(),
            titulo: p.titulo,
            descripcion: p.descripcion ?? '',
            incluye: p.incluye_texto ?? '',
            precio_actual: p.precio_actual,
            precio_anterior: p.precio_anterior ?? '',
            etiqueta: p.etiqueta ?? '',
            etiqueta_tono: p.etiqueta_tono,
            destacada: p.destacada,
            activa: p.activa,
            vigente_desde: p.vigente_desde ?? '',
            vigente_hasta: p.vigente_hasta ?? '',
            cupos_restantes: p.cupos_restantes ?? '',
            mensaje_whatsapp: p.mensaje_whatsapp ?? '',
            orden: p.orden,
            imagen_alt: p.imagen_alt ?? '',
        }
        : promocionVacia();
    imagenActualPromocion.value = p?.imagen_url ?? null;
    archivoPromocion.value = null;
    erroresPromocion.value = {};
    errorGeneralPromocion.value = '';
    modalPromocion.value = true;
};

const guardarPromocion = async () => {
    guardandoPromocion.value = true;
    erroresPromocion.value = {};
    errorGeneralPromocion.value = '';
    try {
        const url = promocionId.value ? `/api/landing/promociones/${promocionId.value}` : '/api/landing/promociones';
        await axios.post(url, aFormData(formPromocion.value, archivoPromocion.value));
        modalPromocion.value = false;
        mostrarAviso(promocionId.value ? 'Promoción actualizada.' : 'Promoción creada.');
        await cargarPromociones();
    } catch (err) {
        erroresPromocion.value = err.response?.data?.errors ?? {};
        errorGeneralPromocion.value = mensajeDeError(err, 'No se pudo guardar la promoción.');
    } finally {
        guardandoPromocion.value = false;
    }
};

const cambiarActiva = async (p) => {
    const nuevo = !p.activa;
    p.activa = nuevo;
    try {
        await axios.patch(`/api/landing/promociones/${p.id}/estado`, { activa: nuevo });
        mostrarAviso(nuevo ? 'Promoción activada.' : 'Promoción pausada: ya no se muestra en /inicio.');
    } catch (err) {
        p.activa = !nuevo;
        mostrarAviso(mensajeDeError(err, 'No se pudo cambiar el estado.'), 'error');
    }
};

const eliminarPromocion = async (p) => {
    if (!confirm(`¿Eliminar la promoción "${p.titulo}"? Esta acción no se puede deshacer.`)) return;
    try {
        await axios.delete(`/api/landing/promociones/${p.id}`);
        mostrarAviso('Promoción eliminada.');
        await cargarPromociones();
    } catch (err) {
        mostrarAviso(mensajeDeError(err, 'No se pudo eliminar.'), 'error');
    }
};

// ======================================================
// Blog y galería
// ======================================================
const publicaciones = ref([]);
const cargandoPublicaciones = ref(true);

const publicacionVacia = () => ({
    tipo: 'articulo',
    titulo: '',
    resumen: '',
    contenido: '',
    publicada: true,
    fecha_publicacion: hoy(),
    imagen_alt: '',
    quitar_imagen: false,
});

const modalPublicacion = ref(false);
const publicacionId = ref(null);
const formPublicacion = ref(publicacionVacia());
const archivoPublicacion = ref(null);
const imagenActualPublicacion = ref(null);
const erroresPublicacion = ref({});
const errorGeneralPublicacion = ref('');
const guardandoPublicacion = ref(false);

const esArticulo = computed(() => formPublicacion.value.tipo === 'articulo');

const cargarPublicaciones = async () => {
    cargandoPublicaciones.value = true;
    try {
        publicaciones.value = (await axios.get('/api/landing/publicaciones')).data;
    } catch (err) {
        mostrarAviso(mensajeDeError(err, 'No pudimos cargar las publicaciones.'), 'error');
    } finally {
        cargandoPublicaciones.value = false;
    }
};

const estadoPublicacion = (p) => {
    if (!p.publicada) return { texto: 'Borrador', clase: 'bg-gray-100 text-gray-700' };
    if (p.fecha_publicacion > hoy()) return { texto: `Se publica ${fechaCorta(p.fecha_publicacion)}`, clase: 'bg-amber-100 text-amber-800' };
    return { texto: 'Publicada', clase: 'bg-emerald-100 text-emerald-800' };
};

const abrirPublicacion = (p = null) => {
    publicacionId.value = p?.id ?? null;
    formPublicacion.value = p
        ? {
            ...publicacionVacia(),
            tipo: p.tipo,
            titulo: p.titulo,
            resumen: p.resumen ?? '',
            contenido: p.contenido ?? '',
            publicada: p.publicada,
            fecha_publicacion: p.fecha_publicacion,
            imagen_alt: p.imagen_alt ?? '',
        }
        : publicacionVacia();
    imagenActualPublicacion.value = p?.imagen_url ?? null;
    archivoPublicacion.value = null;
    erroresPublicacion.value = {};
    errorGeneralPublicacion.value = '';
    modalPublicacion.value = true;
};

const guardarPublicacion = async () => {
    guardandoPublicacion.value = true;
    erroresPublicacion.value = {};
    errorGeneralPublicacion.value = '';
    try {
        const url = publicacionId.value ? `/api/landing/publicaciones/${publicacionId.value}` : '/api/landing/publicaciones';
        await axios.post(url, aFormData(formPublicacion.value, archivoPublicacion.value));
        modalPublicacion.value = false;
        mostrarAviso(publicacionId.value ? 'Publicación actualizada.' : 'Publicación creada.');
        await cargarPublicaciones();
    } catch (err) {
        erroresPublicacion.value = err.response?.data?.errors ?? {};
        errorGeneralPublicacion.value = mensajeDeError(err, 'No se pudo guardar la publicación.');
    } finally {
        guardandoPublicacion.value = false;
    }
};

const cambiarPublicada = async (p) => {
    const nuevo = !p.publicada;
    p.publicada = nuevo;
    try {
        await axios.patch(`/api/landing/publicaciones/${p.id}/estado`, { publicada: nuevo });
        mostrarAviso(nuevo ? 'Publicación visible.' : 'Publicación guardada como borrador.');
    } catch (err) {
        p.publicada = !nuevo;
        mostrarAviso(mensajeDeError(err, 'No se pudo cambiar el estado.'), 'error');
    }
};

const eliminarPublicacion = async (p) => {
    if (!confirm(`¿Eliminar "${p.titulo}"? Esta acción no se puede deshacer.`)) return;
    try {
        await axios.delete(`/api/landing/publicaciones/${p.id}`);
        mostrarAviso('Publicación eliminada.');
        await cargarPublicaciones();
    } catch (err) {
        mostrarAviso(mensajeDeError(err, 'No se pudo eliminar.'), 'error');
    }
};

// ---------- Resumen ----------
const promocionesVisibles = computed(() => promociones.value.filter((p) => estadoPromocion(p).texto === 'Visible en /inicio').length);
const publicacionesVisibles = computed(() => publicaciones.value.filter((p) => estadoPublicacion(p).texto === 'Publicada').length);

const err = (errores, campo) => errores[campo]?.[0] ?? '';

onMounted(() => {
    cargarPromociones();
    cargarPublicaciones();
});

const claseCampo = 'w-full px-3 py-2 text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500';
const claseCampoError = 'border-red-400 bg-red-50';
</script>

<template>
    <Head title="Gestión Landing" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestión Landing</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Encabezado -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Página pública /inicio</h1>
                        <p class="text-sm text-gray-500">Administra las promociones y el blog que ven los pacientes.</p>
                    </div>
                    <a
                        href="/inicio"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-gray-50"
                    >
                        Ver la página
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span class="sr-only">(abre en otra pestaña)</span>
                    </a>
                </div>

                <!-- Resumen -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Promociones visibles</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ promocionesVisibles }} <span class="text-sm font-medium text-gray-500">de {{ promociones.length }}</span></p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Publicaciones visibles</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ publicacionesVisibles }} <span class="text-sm font-medium text-gray-500">de {{ publicaciones.length }}</span></p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Estudios con precio visible</p>
                        <p class="mt-1 text-2xl font-bold" :class="props.estudiosVisibles ? 'text-emerald-600' : 'text-amber-600'">
                            {{ props.estudiosVisibles }} <span class="text-sm font-medium text-gray-500">de {{ props.estudiosTotal }}</span>
                        </p>
                        <Link :href="route('gestion.estudios')" class="mt-1 inline-block text-sm font-medium text-blue-700 hover:underline">
                            Elegir en Gestión de Estudios →
                        </Link>
                    </div>
                </div>

                <!-- Aviso -->
                <div
                    v-if="aviso"
                    role="status"
                    class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border text-sm"
                    :class="aviso.tipo === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800'"
                >
                    <span>{{ aviso.texto }}</span>
                    <button type="button" class="p-1 rounded hover:bg-black/5" @click="aviso = null">
                        <span class="sr-only">Cerrar aviso</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Pestañas -->
                <div class="border-b border-gray-200 flex gap-2" role="tablist" aria-label="Contenido de la landing">
                    <button
                        v-for="t in pestanas"
                        :id="`tab-${t.valor}`"
                        :key="t.valor"
                        type="button"
                        role="tab"
                        :aria-selected="pestana === t.valor"
                        :aria-controls="`panel-${t.valor}`"
                        class="px-4 py-2.5 -mb-px text-sm font-semibold border-b-2 transition-colors"
                        :class="pestana === t.valor ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-800'"
                        @click="pestana = t.valor"
                    >
                        {{ t.etiqueta }}
                    </button>
                </div>

                <!-- ===== Panel promociones ===== -->
                <section v-show="pestana === 'promociones'" id="panel-promociones" role="tabpanel" aria-labelledby="tab-promociones" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-gray-600">Se muestran en /inicio las promociones activas y dentro de su vigencia. Solo una puede ser destacada.</p>
                        <button type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-700" @click="abrirPromocion()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Nueva promoción
                        </button>
                    </div>

                    <div v-if="cargandoPromociones" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="n in 3" :key="n" class="h-72 bg-white rounded-xl border border-gray-200 animate-pulse"></div>
                    </div>

                    <div v-else-if="!promociones.length" class="bg-white rounded-xl border border-dashed border-gray-300 py-14 px-6 text-center">
                        <p class="font-semibold text-gray-800">Aún no hay promociones</p>
                        <p class="text-sm text-gray-500 mt-1">Mientras no haya ninguna, la sección de /inicio invita a los pacientes a escribir por WhatsApp.</p>
                        <button type="button" class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700" @click="abrirPromocion()">Crear la primera</button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <article
                            v-for="p in promociones"
                            :key="p.id"
                            class="bg-white rounded-xl border shadow-sm overflow-hidden flex flex-col"
                            :class="p.destacada ? 'border-emerald-400 ring-1 ring-emerald-400' : 'border-gray-200'"
                        >
                            <div class="relative aspect-[16/10] bg-gray-100">
                                <img :src="p.imagen_url || '/landing/img/ofertas/checkup.svg'" alt="" class="w-full h-full object-cover" loading="lazy" />
                                <div class="absolute top-2 left-2 right-2 flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="estadoPromocion(p).clase">{{ estadoPromocion(p).texto }}</span>
                                    <span v-if="p.destacada" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-600 text-white">Destacada</span>
                                </div>
                            </div>
                            <div class="p-4 flex-1 space-y-2">
                                <h3 class="font-semibold text-gray-900 leading-snug">{{ p.titulo }}</h3>
                                <p class="flex items-baseline gap-2">
                                    <span class="text-xl font-bold text-gray-900">{{ moneda.format(p.precio_actual) }}</span>
                                    <del v-if="p.precio_anterior" class="text-sm text-gray-500">{{ moneda.format(p.precio_anterior) }}</del>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <template v-if="p.vigente_desde || p.vigente_hasta">
                                        Vigencia: {{ p.vigente_desde ? fechaCorta(p.vigente_desde) : 'desde hoy' }} – {{ p.vigente_hasta ? fechaCorta(p.vigente_hasta) : 'sin fecha límite' }}
                                    </template>
                                    <template v-else>Sin fecha límite</template>
                                    <template v-if="p.cupos_restantes !== null"> · {{ p.cupos_restantes }} lugares</template>
                                    · Orden {{ p.orden }}
                                </p>
                            </div>
                            <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="p.activa"
                                        class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                                        :class="p.activa ? 'bg-emerald-500' : 'bg-gray-300'"
                                        @click="cambiarActiva(p)"
                                    >
                                        <span class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transform transition-transform" :class="p.activa ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                    </button>
                                    Activa
                                </label>
                                <button type="button" class="ml-auto px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600" @click="abrirPromocion(p)">
                                    Editar<span class="sr-only"> {{ p.titulo }}</span>
                                </button>
                                <button type="button" class="p-2 rounded-lg text-red-600 hover:bg-red-50" title="Eliminar" @click="eliminarPromocion(p)">
                                    <span class="sr-only">Eliminar {{ p.titulo }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- ===== Panel blog y galería ===== -->
                <section v-show="pestana === 'publicaciones'" id="panel-publicaciones" role="tabpanel" aria-labelledby="tab-publicaciones" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-gray-600">Los artículos tienen página propia en /novedades; las fotos se abren ampliadas. En /inicio se ven las 6 más recientes.</p>
                        <button type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-700" @click="abrirPublicacion()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Nueva publicación
                        </button>
                    </div>

                    <div v-if="cargandoPublicaciones" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="n in 3" :key="n" class="h-64 bg-white rounded-xl border border-gray-200 animate-pulse"></div>
                    </div>

                    <div v-else-if="!publicaciones.length" class="bg-white rounded-xl border border-dashed border-gray-300 py-14 px-6 text-center">
                        <p class="font-semibold text-gray-800">Aún no hay publicaciones</p>
                        <p class="text-sm text-gray-500 mt-1">Comparte consejos de preparación para estudios o fotos del laboratorio. La sección aparece en /inicio en cuanto publiques la primera.</p>
                        <button type="button" class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700" @click="abrirPublicacion()">Crear la primera</button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <article v-for="p in publicaciones" :key="p.id" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="relative aspect-[16/10] bg-gray-100">
                                <img v-if="p.imagen_url" :src="p.imagen_url" alt="" class="w-full h-full object-cover" loading="lazy" />
                                <div v-else class="w-full h-full grid place-items-center text-sm text-gray-400">Sin imagen</div>
                                <div class="absolute top-2 left-2 right-2 flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="estadoPublicacion(p).clase">{{ estadoPublicacion(p).texto }}</span>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-white/90 text-gray-700">{{ p.tipo === 'articulo' ? 'Artículo' : 'Foto' }}</span>
                                </div>
                            </div>
                            <div class="p-4 flex-1 space-y-1">
                                <h3 class="font-semibold text-gray-900 leading-snug">{{ p.titulo }}</h3>
                                <p class="text-xs text-gray-500">{{ fechaCorta(p.fecha_publicacion) }}</p>
                                <p v-if="p.resumen" class="text-sm text-gray-600 line-clamp-2">{{ p.resumen }}</p>
                            </div>
                            <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="p.publicada"
                                        class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                                        :class="p.publicada ? 'bg-emerald-500' : 'bg-gray-300'"
                                        @click="cambiarPublicada(p)"
                                    >
                                        <span class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transform transition-transform" :class="p.publicada ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                    </button>
                                    Publicada
                                </label>
                                <a v-if="p.url_publica && estadoPublicacion(p).texto === 'Publicada'" :href="p.url_publica" target="_blank" rel="noopener" class="text-sm font-medium text-blue-700 hover:underline">
                                    Ver<span class="sr-only"> {{ p.titulo }} (abre en otra pestaña)</span>
                                </a>
                                <button type="button" class="ml-auto px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600" @click="abrirPublicacion(p)">
                                    Editar<span class="sr-only"> {{ p.titulo }}</span>
                                </button>
                                <button type="button" class="p-2 rounded-lg text-red-600 hover:bg-red-50" title="Eliminar" @click="eliminarPublicacion(p)">
                                    <span class="sr-only">Eliminar {{ p.titulo }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>

        <!-- ===== Modal promoción ===== -->
        <Teleport to="body">
            <div v-if="modalPromocion" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.esc="modalPromocion = false">
                <form
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="titulo-modal-promocion"
                    novalidate
                    @submit.prevent="guardarPromocion"
                >
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 id="titulo-modal-promocion" class="text-lg font-bold text-gray-800">{{ promocionId ? 'Editar promoción' : 'Nueva promoción' }}</h3>
                        <button type="button" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" @click="modalPromocion = false">
                            <span class="sr-only">Cerrar</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 overflow-y-auto">
                        <div v-if="errorGeneralPromocion" role="alert" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ errorGeneralPromocion }}</div>

                        <div>
                            <label for="p-titulo" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input id="p-titulo" v-model="formPromocion.titulo" type="text" maxlength="120" placeholder="Ej. Marcadores tumorales para mujer" :class="[claseCampo, err(erroresPromocion, 'titulo') && claseCampoError]" :aria-invalid="!!err(erroresPromocion, 'titulo')" aria-describedby="p-titulo-error" autofocus />
                            <p id="p-titulo-error" class="mt-1 text-sm text-red-600">{{ err(erroresPromocion, 'titulo') }}</p>
                        </div>

                        <div>
                            <label for="p-descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción corta</label>
                            <textarea id="p-descripcion" v-model="formPromocion.descripcion" rows="2" maxlength="400" placeholder="Para quién es y qué beneficio tiene, en una o dos frases." :class="[claseCampo, err(erroresPromocion, 'descripcion') && claseCampoError]"></textarea>
                            <p class="mt-1 flex justify-between text-xs text-gray-500">
                                <span class="text-sm text-red-600">{{ err(erroresPromocion, 'descripcion') }}</span>
                                <span>{{ formPromocion.descripcion.length }}/400</span>
                            </p>
                        </div>

                        <div>
                            <label for="p-incluye" class="block text-sm font-medium text-gray-700 mb-1">¿Qué incluye?</label>
                            <textarea id="p-incluye" v-model="formPromocion.incluye" rows="4" placeholder="Un estudio por línea&#10;Biometría hemática completa&#10;Química sanguínea de 6 elementos" :class="[claseCampo, err(erroresPromocion, 'incluye') && claseCampoError]" aria-describedby="p-incluye-ayuda"></textarea>
                            <p id="p-incluye-ayuda" class="mt-1 text-xs text-gray-500">Escribe un estudio por línea (máximo 12). Se muestran con una palomita.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="p-precio" class="block text-sm font-medium text-gray-700 mb-1">Precio de promoción *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm" aria-hidden="true">$</span>
                                    <input id="p-precio" v-model="formPromocion.precio_actual" type="number" min="1" step="0.01" inputmode="decimal" placeholder="449" :class="[claseCampo, 'pl-7', err(erroresPromocion, 'precio_actual') && claseCampoError]" :aria-invalid="!!err(erroresPromocion, 'precio_actual')" aria-describedby="p-precio-error" />
                                </div>
                                <p id="p-precio-error" class="mt-1 text-sm text-red-600">{{ err(erroresPromocion, 'precio_actual') }}</p>
                            </div>
                            <div>
                                <label for="p-precio-anterior" class="block text-sm font-medium text-gray-700 mb-1">Precio normal <span class="font-normal text-gray-500">(opcional)</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm" aria-hidden="true">$</span>
                                    <input id="p-precio-anterior" v-model="formPromocion.precio_anterior" type="number" min="1" step="0.01" inputmode="decimal" placeholder="610" :class="[claseCampo, 'pl-7', err(erroresPromocion, 'precio_anterior') && claseCampoError]" :aria-invalid="!!err(erroresPromocion, 'precio_anterior')" aria-describedby="p-precio-anterior-ayuda" />
                                </div>
                                <p id="p-precio-anterior-ayuda" class="mt-1 text-xs" :class="err(erroresPromocion, 'precio_anterior') || descuentoFormulario.startsWith('El precio') ? 'text-red-600 text-sm' : 'text-gray-500'">
                                    {{ err(erroresPromocion, 'precio_anterior') || descuentoFormulario || 'Se muestra tachado y se calcula el % de descuento.' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="p-etiqueta" class="block text-sm font-medium text-gray-700 mb-1">Etiqueta <span class="font-normal text-gray-500">(opcional)</span></label>
                                <input id="p-etiqueta" v-model="formPromocion.etiqueta" type="text" maxlength="30" placeholder="Ej. Octubre Rosa" :class="claseCampo" />
                                <p class="mt-1 text-sm text-red-600">{{ err(erroresPromocion, 'etiqueta') }}</p>
                            </div>
                            <fieldset>
                                <legend class="block text-sm font-medium text-gray-700 mb-1">Color de la etiqueta</legend>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="t in tonos" :key="t.valor" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500">
                                        <input v-model="formPromocion.etiqueta_tono" type="radio" name="p-tono" :value="t.valor" class="sr-only" />
                                        <span class="w-4 h-4 rounded-full" :class="t.clase" aria-hidden="true"></span>
                                        {{ t.etiqueta }}
                                    </label>
                                </div>
                            </fieldset>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="p-desde" class="block text-sm font-medium text-gray-700 mb-1">Inicia <span class="font-normal text-gray-500">(opcional)</span></label>
                                <input id="p-desde" v-model="formPromocion.vigente_desde" type="date" :class="[claseCampo, err(erroresPromocion, 'vigente_desde') && claseCampoError]" />
                                <p class="mt-1 text-sm text-red-600">{{ err(erroresPromocion, 'vigente_desde') }}</p>
                            </div>
                            <div>
                                <label for="p-hasta" class="block text-sm font-medium text-gray-700 mb-1">Termina <span class="font-normal text-gray-500">(opcional)</span></label>
                                <input id="p-hasta" v-model="formPromocion.vigente_hasta" type="date" :min="formPromocion.vigente_desde || undefined" :class="[claseCampo, err(erroresPromocion, 'vigente_hasta') && claseCampoError]" :aria-invalid="!!err(erroresPromocion, 'vigente_hasta')" aria-describedby="p-hasta-ayuda" />
                                <p id="p-hasta-ayuda" class="mt-1 text-xs" :class="err(erroresPromocion, 'vigente_hasta') ? 'text-sm text-red-600' : 'text-gray-500'">
                                    {{ err(erroresPromocion, 'vigente_hasta') || 'Se oculta sola al terminar el día. Vacío = sin fecha límite.' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="p-cupos" class="block text-sm font-medium text-gray-700 mb-1">Lugares disponibles <span class="font-normal text-gray-500">(opcional)</span></label>
                                <input id="p-cupos" v-model="formPromocion.cupos_restantes" type="number" min="0" step="1" inputmode="numeric" :class="[claseCampo, err(erroresPromocion, 'cupos_restantes') && claseCampoError]" aria-describedby="p-cupos-ayuda" />
                                <p id="p-cupos-ayuda" class="mt-1 text-xs" :class="err(erroresPromocion, 'cupos_restantes') ? 'text-sm text-red-600' : 'text-gray-500'">
                                    {{ err(erroresPromocion, 'cupos_restantes') || 'Úsalo solo si el límite es real y actualízalo. En 0 la promoción se oculta.' }}
                                </p>
                            </div>
                            <div>
                                <label for="p-orden" class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <input id="p-orden" v-model="formPromocion.orden" type="number" min="0" step="1" inputmode="numeric" :class="claseCampo" aria-describedby="p-orden-ayuda" />
                                <p id="p-orden-ayuda" class="mt-1 text-xs text-gray-500">Número menor aparece primero. La destacada conviene en medio (ej. 1 de 0-2).</p>
                            </div>
                        </div>

                        <div>
                            <label for="p-mensaje" class="block text-sm font-medium text-gray-700 mb-1">Mensaje de WhatsApp <span class="font-normal text-gray-500">(opcional)</span></label>
                            <textarea id="p-mensaje" v-model="formPromocion.mensaje_whatsapp" rows="2" maxlength="300" :placeholder="`Hola, me interesa la promoción ${formPromocion.titulo || '...'}.`" :class="claseCampo" aria-describedby="p-mensaje-ayuda"></textarea>
                            <p id="p-mensaje-ayuda" class="mt-1 text-xs text-gray-500">Es el texto que ya viene escrito cuando el paciente toca "Quiero este paquete".</p>
                        </div>

                        <CampoImagen
                            id="p-imagen"
                            etiqueta="Imagen"
                            recomendacion="Horizontal, idealmente 1200 × 750 px. Si no subes una, se usa una ilustración."
                            :url-actual="imagenActualPromocion"
                            v-model:archivo="archivoPromocion"
                            v-model:quitar="formPromocion.quitar_imagen"
                            v-model:alt="formPromocion.imagen_alt"
                            :error="err(erroresPromocion, 'imagen')"
                            :error-alt="err(erroresPromocion, 'imagen_alt')"
                        />

                        <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input v-model="formPromocion.activa" type="checkbox" class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                <span class="text-sm"><span class="font-medium text-gray-800">Activa</span><br><span class="text-gray-500">Si la desactivas se guarda pero no se muestra.</span></span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input v-model="formPromocion.destacada" type="checkbox" class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                <span class="text-sm"><span class="font-medium text-gray-800">Destacada</span><br><span class="text-gray-500">Se ve más grande y con borde verde. Solo puede haber una: al marcarla se quita de las demás.</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="modalPromocion = false">Cancelar</button>
                        <button type="submit" :disabled="guardandoPromocion" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
                            <svg v-if="guardandoPromocion" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" /></svg>
                            {{ guardandoPromocion ? 'Guardando…' : (promocionId ? 'Guardar cambios' : 'Crear promoción') }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <!-- ===== Modal publicación ===== -->
        <Teleport to="body">
            <div v-if="modalPublicacion" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.esc="modalPublicacion = false">
                <form
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="titulo-modal-publicacion"
                    novalidate
                    @submit.prevent="guardarPublicacion"
                >
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 id="titulo-modal-publicacion" class="text-lg font-bold text-gray-800">{{ publicacionId ? 'Editar publicación' : 'Nueva publicación' }}</h3>
                        <button type="button" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" @click="modalPublicacion = false">
                            <span class="sr-only">Cerrar</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 overflow-y-auto">
                        <div v-if="errorGeneralPublicacion" role="alert" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ errorGeneralPublicacion }}</div>

                        <fieldset>
                            <legend class="block text-sm font-medium text-gray-700 mb-2">Tipo de publicación</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex gap-3 p-3 rounded-lg border cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500">
                                    <input v-model="formPublicacion.tipo" type="radio" name="pub-tipo" value="articulo" class="mt-1 text-blue-600 focus:ring-blue-500" />
                                    <span class="text-sm"><span class="font-semibold text-gray-800">Artículo</span><br><span class="text-gray-500">Texto con página propia (consejos, noticias).</span></span>
                                </label>
                                <label class="flex gap-3 p-3 rounded-lg border cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500">
                                    <input v-model="formPublicacion.tipo" type="radio" name="pub-tipo" value="foto" class="mt-1 text-blue-600 focus:ring-blue-500" />
                                    <span class="text-sm"><span class="font-semibold text-gray-800">Foto de galería</span><br><span class="text-gray-500">Imagen con una descripción corta.</span></span>
                                </label>
                            </div>
                        </fieldset>

                        <div>
                            <label for="pub-titulo" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input id="pub-titulo" v-model="formPublicacion.titulo" type="text" maxlength="120" :placeholder="esArticulo ? 'Ej. Cómo prepararte para tu química sanguínea' : 'Ej. Nuestra nueva área de toma de muestras'" :class="[claseCampo, err(erroresPublicacion, 'titulo') && claseCampoError]" :aria-invalid="!!err(erroresPublicacion, 'titulo')" aria-describedby="pub-titulo-error" />
                            <p id="pub-titulo-error" class="mt-1 text-sm text-red-600">{{ err(erroresPublicacion, 'titulo') }}</p>
                        </div>

                        <div>
                            <label for="pub-resumen" class="block text-sm font-medium text-gray-700 mb-1">{{ esArticulo ? 'Resumen' : 'Descripción corta' }} <span class="font-normal text-gray-500">(opcional)</span></label>
                            <textarea id="pub-resumen" v-model="formPublicacion.resumen" rows="2" maxlength="300" :class="[claseCampo, err(erroresPublicacion, 'resumen') && claseCampoError]" aria-describedby="pub-resumen-ayuda"></textarea>
                            <p id="pub-resumen-ayuda" class="mt-1 flex justify-between gap-3 text-xs text-gray-500">
                                <span :class="err(erroresPublicacion, 'resumen') && 'text-sm text-red-600'">{{ err(erroresPublicacion, 'resumen') || (esArticulo ? 'Se ve en la tarjeta y en Google.' : 'Se ve debajo de la foto.') }}</span>
                                <span>{{ formPublicacion.resumen.length }}/300</span>
                            </p>
                        </div>

                        <div v-if="esArticulo">
                            <label for="pub-contenido" class="block text-sm font-medium text-gray-700 mb-1">Texto del artículo *</label>
                            <textarea id="pub-contenido" v-model="formPublicacion.contenido" rows="10" :class="[claseCampo, err(erroresPublicacion, 'contenido') && claseCampoError]" :aria-invalid="!!err(erroresPublicacion, 'contenido')" aria-describedby="pub-contenido-ayuda"></textarea>
                            <p id="pub-contenido-ayuda" class="mt-1 text-xs" :class="err(erroresPublicacion, 'contenido') ? 'text-sm text-red-600' : 'text-gray-500'">
                                {{ err(erroresPublicacion, 'contenido') || 'Deja una línea en blanco entre párrafos.' }}
                            </p>
                        </div>

                        <CampoImagen
                            id="pub-imagen"
                            :etiqueta="esArticulo ? 'Imagen de portada' : 'Foto'"
                            :requerida="!esArticulo"
                            recomendacion="Horizontal, idealmente 1200 × 750 px."
                            :url-actual="imagenActualPublicacion"
                            v-model:archivo="archivoPublicacion"
                            v-model:quitar="formPublicacion.quitar_imagen"
                            v-model:alt="formPublicacion.imagen_alt"
                            :error="err(erroresPublicacion, 'imagen')"
                            :error-alt="err(erroresPublicacion, 'imagen_alt')"
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="pub-fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha de publicación</label>
                                <input id="pub-fecha" v-model="formPublicacion.fecha_publicacion" type="date" :class="[claseCampo, err(erroresPublicacion, 'fecha_publicacion') && claseCampoError]" aria-describedby="pub-fecha-ayuda" />
                                <p id="pub-fecha-ayuda" class="mt-1 text-xs text-gray-500">Si eliges una fecha futura, aparece ese día.</p>
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer sm:pt-7">
                                <input v-model="formPublicacion.publicada" type="checkbox" class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                <span class="text-sm"><span class="font-medium text-gray-800">Publicada</span><br><span class="text-gray-500">Desmárcala para guardarla como borrador.</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="modalPublicacion = false">Cancelar</button>
                        <button type="submit" :disabled="guardandoPublicacion" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
                            <svg v-if="guardandoPublicacion" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" /></svg>
                            {{ guardandoPublicacion ? 'Guardando…' : (publicacionId ? 'Guardar cambios' : 'Crear publicación') }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
