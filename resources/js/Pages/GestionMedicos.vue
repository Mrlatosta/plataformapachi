<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const medicos = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editando = ref(false);
const errorMsg = ref('');

const formInicial = {
    nombre: '',
    especialidad: '',
    usuario: '',
    password: '',
    activo: true,
};

const form = ref({ ...formInicial });
const medicoEditId = ref(null);

const cargarMedicos = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/medicos');
        medicos.value = res.data;
    } finally {
        loading.value = false;
    }
};

onMounted(cargarMedicos);

const abrirCrear = () => {
    editando.value = false;
    medicoEditId.value = null;
    form.value = { ...formInicial };
    errorMsg.value = '';
    showModal.value = true;
};

const abrirEditar = (medico) => {
    editando.value = true;
    medicoEditId.value = medico.id;
    form.value = {
        nombre: medico.nombre,
        especialidad: medico.especialidad ?? '',
        usuario: medico.usuario,
        password: '',
        activo: medico.activo,
    };
    errorMsg.value = '';
    showModal.value = true;
};

const guardar = async () => {
    errorMsg.value = '';
    try {
        if (editando.value) {
            await axios.put(`/api/medicos/${medicoEditId.value}`, form.value);
        } else {
            await axios.post('/api/medicos', form.value);
        }
        showModal.value = false;
        await cargarMedicos();
    } catch (err) {
        const errors = err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat()
            : [err.response?.data?.message ?? 'Error al guardar'];
        errorMsg.value = errors.join(' ');
    }
};

const eliminar = async (id) => {
    if (!confirm('¿Eliminar este médico? Sus reportes asignados quedarán sin médico.')) return;
    await axios.delete(`/api/medicos/${id}`);
    await cargarMedicos();
};
</script>

<template>
    <Head title="Gestión de Médicos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestión de Médicos
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Médicos Registrados</h3>
                                <p class="text-sm text-gray-500">Administra los médicos del sistema</p>
                            </div>
                        </div>
                        <button
                            @click="abrirCrear"
                            class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all shadow hover:shadow-md"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Agregar Médico</span>
                        </button>
                    </div>

                    <!-- Tabla -->
                    <div class="overflow-x-auto">
                        <div v-if="loading" class="flex items-center justify-center py-16 text-gray-500">
                            <svg class="animate-spin w-8 h-8 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Cargando médicos...
                        </div>

                        <table v-else class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                                <tr>
                                    <th class="px-6 py-3 text-left">Nombre</th>
                                    <th class="px-6 py-3 text-left">Especialidad</th>
                                    <th class="px-6 py-3 text-left">Usuario</th>
                                    <th class="px-6 py-3 text-center">Estado</th>
                                    <th class="px-6 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="medicos.length === 0">
                                    <td colspan="5" class="text-center py-12 text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        No hay médicos registrados
                                    </td>
                                </tr>
                                <tr v-for="medico in medicos" :key="medico.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ medico.nombre }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ medico.especialidad || '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600 font-mono text-xs">{{ medico.usuario }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            medico.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ medico.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button
                                                @click="abrirEditar(medico)"
                                                class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="Editar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="eliminar(medico.id)"
                                                class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors"
                                                title="Eliminar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800">
                                {{ editando ? 'Editar Médico' : 'Nuevo Médico' }}
                            </h3>
                            <button @click="showModal = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Error -->
                            <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                {{ errorMsg }}
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                                <input
                                    v-model="form.nombre"
                                    type="text"
                                    placeholder="Dr. Nombre Apellido"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                                <input
                                    v-model="form.especialidad"
                                    type="text"
                                    placeholder="Ej: Medicina General, Cardiología..."
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario *</label>
                                <input
                                    v-model="form.usuario"
                                    type="text"
                                    placeholder="usuario_medico"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña {{ editando ? '(dejar en blanco para no cambiar)' : '*' }}
                                </label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Mínimo 6 caracteres"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                />
                            </div>

                            <div class="flex items-center space-x-3">
                                <button
                                    type="button"
                                    @click="form.activo = !form.activo"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                                        form.activo ? 'bg-green-500' : 'bg-gray-300'
                                    ]"
                                >
                                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', form.activo ? 'translate-x-6' : 'translate-x-1']" />
                                </button>
                                <span class="text-sm text-gray-700">{{ form.activo ? 'Médico activo' : 'Médico inactivo' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                            <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button
                                @click="guardar"
                                class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all shadow"
                            >
                                {{ editando ? 'Guardar cambios' : 'Crear médico' }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
