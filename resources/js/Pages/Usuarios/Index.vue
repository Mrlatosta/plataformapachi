<script setup>
// T6: el administrador da de alta accesos (p. ej. Recepción), cambia roles,
// desactiva usuarios y restablece contraseñas.
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    usuarios: Array,
    roles: Array,
});

const page = usePage();
const yo = computed(() => page.props.auth.user?.id);
const nombreRol = (valor) => props.roles.find((r) => r.valor === valor)?.nombre ?? valor;

const PERMISOS_RECEPCION = [
    'Hacer cotizaciones y agendar citas',
    'Dar de alta pacientes',
    'Capturar resultados (quedan pendientes de validar)',
    'Descargar y enviar resultados ya validados',
];
const BLOQUEOS_RECEPCION = [
    'Validar o firmar resultados',
    'Borrar órdenes o pacientes',
    'Cambiar precios, descuentos, catálogo o valores de referencia',
    'Ver el dashboard de ventas ni administrar usuarios',
];

// Alta / edición en un mismo panel
const editando = ref(null); // usuario o null (nuevo)
const panelAbierto = ref(false);
const form = useForm({ name: '', email: '', rol: 'recepcion', activo: true, password: '' });

const abrirNuevo = () => {
    editando.value = null;
    form.reset();
    form.clearErrors();
    form.rol = 'recepcion';
    form.activo = true;
    panelAbierto.value = true;
};

const abrirEdicion = (usuario) => {
    editando.value = usuario;
    form.clearErrors();
    form.name = usuario.name;
    form.email = usuario.email;
    form.rol = usuario.rol;
    form.activo = Boolean(usuario.activo);
    form.password = '';
    panelAbierto.value = true;
};

const cerrar = () => { panelAbierto.value = false; };

const guardar = () => {
    const opciones = { preserveScroll: true, onSuccess: () => { panelAbierto.value = false; form.reset('password'); } };
    if (editando.value) form.put(route('usuarios.update', editando.value.id), opciones);
    else form.post(route('usuarios.store'), opciones);
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Usuarios y permisos</h2>
                <button type="button" @click="abrirNuevo"
                    class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">
                    Nuevo usuario
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <section class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <h3 class="text-base font-semibold text-emerald-900">Recepción puede</h3>
                        <ul class="mt-2 space-y-1 text-sm text-emerald-900">
                            <li v-for="p in PERMISOS_RECEPCION" :key="p">✓ {{ p }}</li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h3 class="text-base font-semibold text-gray-800">Recepción no puede</h3>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600">
                            <li v-for="p in BLOQUEOS_RECEPCION" :key="p">✕ {{ p }}</li>
                        </ul>
                    </div>
                </section>

                <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="usuario in usuarios" :key="usuario.id" :class="{ 'opacity-60': !usuario.activo }">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ usuario.name }} <span v-if="usuario.id === yo" class="text-xs text-gray-500">(tú)</span></p>
                                    <p class="text-sm text-gray-500">{{ usuario.email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="usuario.rol === 'admin' ? 'bg-indigo-50 text-indigo-800' : 'bg-emerald-50 text-emerald-800'">
                                        {{ nombreRol(usuario.rol) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-sm" :class="usuario.activo ? 'text-gray-700' : 'text-gray-500'">
                                    {{ usuario.activo ? 'Activo' : 'Desactivado' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="abrirEdicion(usuario)" class="text-sm font-medium text-blue-700 hover:underline">
                                        Editar<span class="sr-only"> a {{ usuario.name }}</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>

        <!-- Panel de alta / edición -->
        <div v-if="panelAbierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="cerrar" @keydown.esc="cerrar"
            role="dialog" aria-modal="true" aria-labelledby="titulo-usuario">
            <form @submit.prevent="guardar" class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl space-y-4">
                <h3 id="titulo-usuario" class="text-lg font-semibold text-gray-900">{{ editando ? 'Editar usuario' : 'Nuevo usuario' }}</h3>

                <div>
                    <label for="u-nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input id="u-nombre" v-model="form.name" type="text" required autofocus class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label for="u-email" class="block text-sm font-medium text-gray-700">Correo (para iniciar sesión)</label>
                    <input id="u-email" v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">Rol</legend>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <label v-for="rol in roles" :key="rol.valor"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                            :class="form.rol === rol.valor ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300'">
                            <input type="radio" :value="rol.valor" v-model="form.rol" class="text-emerald-600 focus:ring-emerald-500" />
                            {{ rol.nombre }}
                        </label>
                    </div>
                    <p v-if="form.errors.rol" class="mt-1 text-sm text-red-600">{{ form.errors.rol }}</p>
                </fieldset>
                <div>
                    <label for="u-pass" class="block text-sm font-medium text-gray-700">
                        {{ editando ? 'Nueva contraseña' : 'Contraseña' }}
                        <span v-if="editando" class="text-xs font-normal text-gray-500">(déjala vacía para no cambiarla)</span>
                    </label>
                    <input id="u-pass" v-model="form.password" type="password" :required="!editando" minlength="8" autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres.</p>
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>
                <label v-if="editando" class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.activo" :disabled="editando.id === yo" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                    Usuario activo (si lo desactivas, ya no puede iniciar sesión)
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="cerrar" class="px-4 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-100">Cancelar</button>
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ form.processing ? 'Guardando…' : 'Guardar' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
