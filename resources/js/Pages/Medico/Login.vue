<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const form = useForm({
    usuario: '',
    password: '',
});

const submit = () => {
    form.post(route('medico.login.post'));
};
</script>

<template>
    <Head title="Portal Médicos - Iniciar Sesión" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-4">
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="flex flex-col items-center mb-8">
                <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-green-400 to-blue-500 shadow-xl mb-4">
                    <ApplicationLogo class="block h-12 w-auto fill-current text-gray-800" />
                </div>
                <h1 class="text-2xl font-bold text-white">BIOLAB</h1>
                <p class="text-gray-400 text-sm mt-1">Portal de Médicos</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Iniciar Sesión</h2>
                    <p class="text-blue-100 text-sm">Ingresa tus credenciales para ver tus reportes asignados</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

                    <!-- Error global -->
                    <div v-if="form.errors.usuario" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-center space-x-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ form.errors.usuario }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Usuario</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <input
                                v-model="form.usuario"
                                type="text"
                                autocomplete="username"
                                placeholder="Tu usuario"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Tu contraseña"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center space-x-2 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span>{{ form.processing ? 'Verificando...' : 'Ingresar' }}</span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</template>
