<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
  <Head title="Inicio" />

  <div class="container mt-5">
    <!-- Encabezado -->
    <div class="border p-5 rounded shadow-sm bg-light text-center">

          <!-- Imagen centrada -->
      <div class="d-flex justify-content-center mb-4">
        <img
          src="/img/imgbiolabtrans.png"
          alt="Logo de la Plataforma Pachi"
          class="img-fluid"
          style="max-width: 200px; height: auto;"
        />
      </div>

      <h1 class="text-center">Bienvenido a la Plataforma Pachi</h1>
      <p class="text-center">Esta es una plataforma para el registro y la impresión de resultados.</p>


       <!-- Navegación según autenticación -->
    <div class="mt-4 text-center">
      <nav v-if="canLogin">
        <Link
          v-if="$page.props.auth?.user"
          :href="route('dashboard')"
          class="btn btn-success mx-2"
        >
          Ir al Panel
        </Link>

        <template v-else>
          <Link :href="route('login')" class="btn btn-primary mx-2">
            Iniciar Sesión
          </Link>

          <Link
            v-if="canRegister"
            :href="route('register')"
            class="btn btn-outline-primary mx-2"
          >
            Crear Cuenta
          </Link>
        </template>
      </nav>
    </div>

    </div>

    

   

    <!-- Formulario de login opcional -->
    <!-- <div v-if="!$page.props.auth?.user" class="mt-5">
      <h2 class="text-center">Iniciar Sesión</h2>
      <form method="POST" action="/login" class="w-50 mx-auto">
        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
        <div class="mb-3">
          <label for="email" class="form-label">Correo Electrónico</label>
          <input type="email" class="form-control" id="email" name="email" required />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Contraseña</label>
          <input type="password" class="form-control" id="password" name="password" required />
        </div>
        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
      </form>
    </div> -->
  </div>
</template>
