<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

const sidebarOpen = ref(true);
const mobileSidebarOpen = ref(false);
const showUserMenu = ref(false);

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const toggleMobileSidebar = () => {
    mobileSidebarOpen.value = !mobileSidebarOpen.value;
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const navigation = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>`
    },
    {
        name: 'Gestión de Estudios',
        route: 'gestion.estudios',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>`
    },
    {
        name: 'Captura de Resultados',
        route: 'captura.resultados',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>`
    },
    {
        name: 'Cotizaciones',
        route: 'cotizaciones',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>`
    },
    // 🔹 NUEVA OPCIÓN
    {
        name: 'Lista de Reportes',
        route: 'reportes.index',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>`
    },
    {
        name: 'Reimpresión de Resultados',
        route: 'reimpresion.resultados',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>`
    }
];

</script>

<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden">
        
        <!-- Sidebar Desktop -->
        <aside 
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out transform bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 shadow-2xl',
                sidebarOpen ? 'w-64' : 'w-20',
                'hidden lg:flex'
            ]"
        >
            <!-- Logo Section -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-700">
                <Link :href="route('dashboard')" class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-blue-500 shadow-lg">
                        <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                    </div>
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 transform scale-95"
                        enter-to-class="opacity-100 transform scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 transform scale-100"
                        leave-to-class="opacity-0 transform scale-95"
                    >
                        <span v-if="sidebarOpen" class="text-xl font-bold text-white">
                            BIOLAB
                        </span>
                    </transition>
                </Link>
                
                <!-- Toggle Button -->
                <button
                    @click="toggleSidebar"
                    class="p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200 text-gray-400 hover:text-white"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="sidebarOpen ? 'M11 19l-7-7 7-7m8 14l-7-7 7-7' : 'M13 5l7 7-7 7M5 5l7 7-7 7'"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex items-center px-3 py-3 rounded-xl transition-all duration-200 group relative',
                        route().current(item.route)
                            ? 'bg-gradient-to-r from-green-500 to-blue-600 text-white shadow-lg shadow-green-500/50'
                            : 'text-gray-300 hover:bg-gray-700/50 hover:text-white'
                    ]"
                >
                    <!-- Icon -->
                    <span 
                        v-html="item.icon"
                        :class="[
                            'flex-shrink-0 transition-transform duration-200',
                            route().current(item.route) ? 'scale-110' : 'group-hover:scale-110'
                        ]"
                    ></span>
                    
                    <!-- Text -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 transform -translate-x-2"
                        enter-to-class="opacity-100 transform translate-x-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="sidebarOpen" class="ml-3" style="font-size: 0.8rem;">
                            {{ item.name }}
                        </span>
                    </transition>

                    <!-- Tooltip for collapsed state -->
                    <div 
                        v-if="!sidebarOpen"
                        class="absolute left-full ml-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap shadow-xl border border-gray-700 z-50"
                    >
                        {{ item.name }}
                        <div class="absolute right-full top-1/2 -translate-y-1/2 border-8 border-transparent border-r-gray-900"></div>
                    </div>
                </Link>
            </nav>

            <!-- User Section -->
            <div class="border-t border-gray-700 p-4 relative">
                <button
                    @click="toggleUserMenu"
                    :class="[
                        'flex items-center w-full p-3 rounded-xl transition-all duration-200 hover:bg-gray-700/50 group',
                        sidebarOpen ? 'justify-start' : 'justify-center'
                    ]"
                >
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 text-white font-semibold shadow-lg flex-shrink-0">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 transform -translate-x-2"
                        enter-to-class="opacity-100 transform translate-x-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="sidebarOpen" class="ml-3 text-left flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                    </transition>
                    <svg v-if="sidebarOpen" class="w-5 h-5 text-gray-400 group-hover:text-white transition-colors ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown Menu con Teleport -->
                <Teleport to="body">
                    <!-- Overlay para cerrar al hacer click fuera -->
                    <transition
                        enter-active-class="transition-opacity ease-out duration-100"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity ease-in duration-75"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div 
                            v-if="showUserMenu"
                            @click="showUserMenu = false"
                            class="fixed inset-0 z-[60]"
                        ></div>
                    </transition>

                    <!-- Dropdown Content -->
                    <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div 
                            v-if="showUserMenu"
                            :class="[
                                'fixed bottom-4 bg-white rounded-lg shadow-xl border border-gray-200 py-1 min-w-[200px] z-[70]',
                                sidebarOpen ? 'left-4' : 'left-24'
                            ]"
                            :style="{
                                transition: 'left 300ms ease-in-out'
                            }"
                        >
                            <Link 
                                :href="route('profile.edit')"
                                @click="showUserMenu = false"
                                class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Perfil
                            </Link>
                            <Link 
                                :href="route('logout')" 
                                method="post" 
                                as="button"
                                @click="showUserMenu = false"
                                class="flex items-center w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Cerrar Sesión
                            </Link>
                        </div>
                    </transition>
                </Teleport>
            </div>
        </aside>

        <!-- Mobile Sidebar -->
        <transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileSidebarOpen"
                class="fixed inset-0 z-40 bg-gray-900 bg-opacity-75 lg:hidden"
                @click="toggleMobileSidebar"
            ></div>
        </transition>

        <transition
            enter-active-class="transition ease-in-out duration-300 transform"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition ease-in-out duration-300 transform"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside
                v-if="mobileSidebarOpen"
                class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 shadow-2xl lg:hidden"
            >
                <!-- Mobile Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-700">
                    <Link :href="route('dashboard')" class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-blue-500 shadow-lg">
                            <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                        </div>
                        <span class="text-xl font-bold text-white">BIOLAB</span>
                    </Link>
                    <button
                        @click="toggleMobileSidebar"
                        class="p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200 text-gray-400 hover:text-white"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mobile Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="toggleMobileSidebar"
                        :class="[
                            'flex items-center px-3 py-3 rounded-xl transition-all duration-200',
                            route().current(item.route)
                                ? 'bg-gradient-to-r from-green-500 to-blue-600 text-white shadow-lg'
                                : 'text-gray-300 hover:bg-gray-700/50 hover:text-white'
                        ]"
                    >
                        <span v-html="item.icon"></span>
                        <span class="ml-3 font-medium">{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- Mobile User Section -->
                <div class="border-t border-gray-700 p-4">
                    <div class="flex items-center px-3 py-3 mb-2">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 text-white font-semibold shadow-lg">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $page.props.auth.user.email }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('profile.edit')"
                        @click="toggleMobileSidebar"
                        class="flex items-center px-3 py-2 text-gray-300 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Perfil
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        @click="toggleMobileSidebar"
                        class="flex items-center w-full px-3 py-2 text-red-400 hover:bg-red-900/20 hover:text-red-300 rounded-lg transition-colors mt-2"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </Link>
                </div>
            </aside>
        </transition>

        <!-- Main Content -->
        <div 
            :class="[
                'flex-1 flex flex-col transition-all duration-300 ease-in-out',
                sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'
            ]"
        >
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Mobile Menu Button -->
                    <button
                        @click="toggleMobileSidebar"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors lg:hidden"
                    >
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <div v-if="$slots.header" class="flex-1">
                        <slot name="header" />
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors relative">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar personalizado */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.5);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.7);
}
</style>
