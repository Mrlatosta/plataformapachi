<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="flex min-h-screen flex-col justify-center items-center bg-gray-100">
    <!-- Logo centrado -->
    <div class="mb-6">
      <Link href="/">
        <ApplicationLogo style="width: 300px;"  class=" fill-current text-gray-500" />
      </Link>
    </div>

    <!-- Contenido -->
    <div
      class="w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
    >
      <slot />
    </div>
  </div>
</template>
