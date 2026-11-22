<script setup>
// T12: comisiones por médico que refiere. Se arman solas con cada orden que tiene
// médico (monto cobrado × % del médico). Solo el administrador entra aquí.
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    medicos: { type: Array, default: () => [] },
    base: { type: String, default: 'sin_iva' },
});

const iso = (d) => {
    const z = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return z.toISOString().slice(0, 10);
};
const hoy = new Date();

const filtros = reactive({
    desde: iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)),
    hasta: iso(hoy),
    medico_id: '',
    estado: '',
});

const PERIODOS = {
    semana: () => {
        const lunes = new Date(hoy);
        lunes.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
        return [lunes, hoy];
    },
    quincena: () => (hoy.getDate() <= 15
        ? [new Date(hoy.getFullYear(), hoy.getMonth(), 1), new Date(hoy.getFullYear(), hoy.getMonth(), 15)]
        : [new Date(hoy.getFullYear(), hoy.getMonth(), 16), new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)]),
    mes: () => [new Date(hoy.getFullYear(), hoy.getMonth(), 1), new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)],
    mesPasado: () => [new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1), new Date(hoy.getFullYear(), hoy.getMonth(), 0)],
};

const usarPeriodo = (clave) => {
    const [desde, hasta] = PERIODOS[clave]();
    filtros.desde = iso(desde);
    filtros.hasta = iso(hasta);
    cargar();
};

const datos = ref({ filas: [], por_medico: [], total: { ordenes: 0, monto: 0, comision: 0, pendiente: 0, pagada: 0 } });
const cargando = ref(false);
const procesando = ref(false);
const aviso = reactive({ texto: '', tipo: 'ok' });

const parametros = (extra = {}) => Object.fromEntries(
    Object.entries({ ...filtros, ...extra }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
);

const cargar = async () => {
    cargando.value = true;
    try {
        const { data } = await axios.get('/api/comisiones', { params: parametros() });
        datos.value = data;
    } catch (e) {
        avisar(e.response?.data?.message || 'No se pudieron cargar las comisiones', 'error');
    } finally {
        cargando.value = false;
    }
};

onMounted(cargar);

const avisar = (texto, tipo = 'ok') => {
    aviso.texto = texto;
    aviso.tipo = tipo;
    setTimeout(() => { if (aviso.texto === texto) aviso.texto = ''; }, 6000);
};

const dinero = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0);
const fecha = (v) => (v ? new Date(`${v}T00:00:00`).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '');
const porcentaje = (v) => `${Number(v).toLocaleString('es-MX', { maximumFractionDigits: 2 })}%`;

const filasDe = (medicoId) => datos.value.filas.filter((f) => f.medico_id === medicoId);
const sinPorcentaje = computed(() => props.medicos.filter((m) => m.activo && Number(m.porcentaje_comision) === 0));

const pagar = async (medico = null) => {
    const quien = medico ? medico.medico : 'todos los médicos';
    const monto = medico ? medico.pendiente : datos.value.total.pendiente;
    if (!monto) return;
    if (!window.confirm(`¿Marcar como pagadas las comisiones pendientes de ${quien} del ${fecha(filtros.desde)} al ${fecha(filtros.hasta)}? Total: ${dinero(monto)}`)) return;

    procesando.value = true;
    try {
        const { data } = await axios.post('/api/comisiones/pagar', parametros(medico ? { medico_id: medico.medico_id, estado: '' } : { estado: '' }));
        avisar(`${data.marcadas} comisión(es) marcadas como pagadas.`);
        await cargar();
    } catch (e) {
        avisar(e.response?.data?.message || 'No se pudo marcar el pago', 'error');
    } finally {
        procesando.value = false;
    }
};

const revertir = async (fila) => {
    if (!window.confirm(`¿Regresar a pendiente la comisión del folio ${fila.folio}?`)) return;
    try {
        await axios.post(`/api/comisiones/${fila.id}/revertir`);
        await cargar();
    } catch (e) {
        avisar(e.response?.data?.message || 'No se pudo revertir', 'error');
    }
};

const urlExportar = (tipo) => route(tipo === 'excel' ? 'comisiones.excel' : 'comisiones.pdf', parametros());
</script>

<template>
    <Head title="Comisiones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Comisiones por médico</h2>
                <div class="flex gap-2">
                    <a :href="urlExportar('excel')" class="rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Exportar Excel</a>
                    <a :href="urlExportar('pdf')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Exportar PDF</a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="aviso.texto" role="status" class="rounded-lg border px-4 py-3 text-sm"
                    :class="aviso.tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                    {{ aviso.texto }}
                </div>

                <p v-if="sinPorcentaje.length" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ sinPorcentaje.length }} médico(s) todavía no tienen % de comisión
                    ({{ sinPorcentaje.slice(0, 4).map((m) => m.nombre).join(', ') }}{{ sinPorcentaje.length > 4 ? '…' : '' }}).
                    Sus comisiones salen en $0 hasta que se lo pongas en
                    <Link :href="route('gestion.medicos')" class="font-semibold underline">Médicos</Link>.
                </p>

                <!-- Filtros -->
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label for="c-desde" class="block text-sm font-medium text-gray-700">Desde</label>
                            <input id="c-desde" v-model="filtros.desde" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="cargar" />
                        </div>
                        <div>
                            <label for="c-hasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                            <input id="c-hasta" v-model="filtros.hasta" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="cargar" />
                        </div>
                        <div>
                            <label for="c-medico" class="block text-sm font-medium text-gray-700">Médico</label>
                            <select id="c-medico" v-model="filtros.medico_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="cargar">
                                <option value="">Todos</option>
                                <option v-for="m in medicos" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="c-estado" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select id="c-estado" v-model="filtros.estado" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="cargar">
                                <option value="">Todas</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="pagada">Pagadas</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-end gap-1.5">
                            <button v-for="[clave, texto] in [['semana', 'Semana'], ['quincena', 'Quincena'], ['mes', 'Este mes'], ['mesPasado', 'Mes pasado']]"
                                :key="clave" type="button" @click="usarPeriodo(clave)"
                                class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50">{{ texto }}</button>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        Fecha de la orden = toma de muestra. Comisión = monto cobrado × % del médico; monto cobrado:
                        {{ base === 'con_iva' ? 'total con IVA' : 'después de descuento y sin IVA' }}.
                        Las órdenes «A quien corresponda» no generan comisión.
                    </p>
                </section>

                <!-- Totales -->
                <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Cobrado ({{ datos.total.ordenes }} órdenes)</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ dinero(datos.total.monto) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Comisiones</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ dinero(datos.total.comision) }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-emerald-800">Pagado</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-800">{{ dinero(datos.total.pagada) }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-amber-800">Pendiente</p>
                        <p class="mt-1 text-2xl font-bold text-amber-800">{{ dinero(datos.total.pendiente) }}</p>
                        <button v-if="datos.total.pendiente > 0" type="button" @click="pagar()" :disabled="procesando"
                            class="mt-2 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 disabled:opacity-50">
                            Marcar todo el periodo como pagado
                        </button>
                    </div>
                </section>

                <p v-if="cargando" class="text-sm text-gray-500">Cargando…</p>
                <p v-else-if="!datos.filas.length" class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    No hay comisiones en este periodo.
                </p>

                <!-- Por médico -->
                <section v-for="medico in datos.por_medico" :key="medico.medico_id" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ medico.medico }}</h3>
                            <p class="text-xs text-gray-600">
                                {{ medico.ordenes }} {{ medico.ordenes === 1 ? 'orden' : 'órdenes' }} · cobrado {{ dinero(medico.monto) }} ·
                                comisión <strong>{{ dinero(medico.comision) }}</strong>
                                <span v-if="medico.pendiente > 0" class="text-amber-700"> · pendiente {{ dinero(medico.pendiente) }}</span>
                            </p>
                        </div>
                        <button v-if="medico.pendiente > 0" type="button" @click="pagar(medico)" :disabled="procesando"
                            class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:opacity-50">
                            Marcar pagadas ({{ dinero(medico.pendiente) }})
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-white text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-left">Folio</th>
                                    <th scope="col" class="px-4 py-2 text-left">Fecha</th>
                                    <th scope="col" class="px-4 py-2 text-left">Paciente</th>
                                    <th scope="col" class="px-4 py-2 text-left">Estudios</th>
                                    <th scope="col" class="px-4 py-2 text-right">Cobrado</th>
                                    <th scope="col" class="px-4 py-2 text-right">%</th>
                                    <th scope="col" class="px-4 py-2 text-right">Comisión</th>
                                    <th scope="col" class="px-4 py-2 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="fila in filasDe(medico.medico_id)" :key="fila.id">
                                    <td class="px-4 py-2 font-mono text-xs">
                                        <Link :href="route('reportes.show', fila.reporte_id)" class="text-blue-700 hover:underline">{{ fila.folio }}</Link>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">{{ fecha(fila.fecha) }}</td>
                                    <td class="px-4 py-2">{{ fila.paciente }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-600">{{ fila.estudios }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">{{ dinero(fila.monto) }}</td>
                                    <td class="px-4 py-2 text-right">{{ porcentaje(fila.porcentaje) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold whitespace-nowrap">{{ dinero(fila.comision) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span v-if="fila.estado === 'pagada'" class="text-xs text-emerald-700">
                                            Pagada {{ fecha(fila.pagada_at) }}
                                            <button type="button" @click="revertir(fila)" class="ml-1 text-gray-500 underline hover:text-gray-700">deshacer</button>
                                        </span>
                                        <span v-else class="text-xs font-medium text-amber-700">Pendiente</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
