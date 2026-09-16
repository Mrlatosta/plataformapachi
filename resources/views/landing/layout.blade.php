@php
    $waUrl = 'https://wa.me/' . config('landing.whatsapp');
    // En /inicio las anclas son locales; en el blog apuntan a /inicio#seccion
    $ancla = fn (string $id) => (request()->routeIs('inicio') ? '' : route('inicio')) . '#' . $id;
    $version = fn (string $ruta) => asset($ruta) . '?v=' . @filemtime(public_path($ruta));
    $tituloDefault = 'BIOLAB · Análisis clínicos en Cancún, también a domicilio';
    $descripcionDefault = 'Laboratorio de análisis clínicos en Cancún. Agenda por WhatsApp, toma de muestra a domicilio y resultados con código QR verificable. Respondemos en menos de 1 hora.';
@endphp
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', $tituloDefault)</title>
    <meta name="description" content="@yield('descripcion', $descripcionDefault)">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#1f2a55">
    <meta property="og:type" content="@yield('og_tipo', 'website')">
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="BIOLAB">
    <meta property="og:title" content="@yield('titulo', $tituloDefault)">
    <meta property="og:description" content="@yield('descripcion', $descripcionDefault)">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_imagen', asset('img/imgbiolabtrans.png'))">
    <link rel="icon" type="image/png" href="{{ asset('landing/img/logo-80x96.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ $version('landing/css/landing.css') }}">
    <script>document.documentElement.classList.add('js');</script>
    <script src="{{ $version('landing/js/landing.js') }}" defer></script>
    @stack('head')
</head>
<body>
    @include('landing.partials.sprite')

    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="{{ $ancla('inicio') }}">
                <picture>
                    <source type="image/webp" srcset="{{ asset('landing/img/logo-80x96.webp') }}">
                    <img src="{{ asset('landing/img/logo-80x96.png') }}" width="40" height="48" alt="">
                </picture>
                BIOLAB
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu">
                <svg class="icon icon--menu" aria-hidden="true"><use href="#i-menu"/></svg>
                <svg class="icon icon--close" aria-hidden="true"><use href="#i-close"/></svg>
                <span class="sr-only">Abrir menú</span>
            </button>

            <nav class="nav" id="menu" aria-label="Principal">
                <ul class="nav__list" role="list">
                    <li><a href="{{ $ancla('ofertas') }}">Promociones</a></li>
                    <li><a href="{{ $ancla('precios') }}">Precios</a></li>
                    <li><a href="{{ route('novedades.index') }}" @if(request()->routeIs('novedades.*')) aria-current="page" @endif>Novedades</a></li>
                    <li><a href="{{ $ancla('preguntas') }}">Preguntas</a></li>
                    <li><a href="{{ $ancla('contacto') }}">Contacto</a></li>
                </ul>
                <a class="btn btn--secondary" href="{{ $waUrl }}" data-wa="Hola, tengo una duda sobre un estudio." data-cta="header" target="_blank" rel="noopener">
                    <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                    Escríbenos<span class="sr-only"> por WhatsApp</span>
                </a>
            </nav>
        </div>
    </header>

    <main id="contenido">
        @yield('contenido')
    </main>

    <footer class="site-footer">
        <div class="container site-footer__grid">
            <div>
                <p class="site-footer__brand">BIOLAB</p>
                <p>Laboratorio de Análisis Clínicos · Resultados que dan confianza</p>
                <p>{{ config('landing.direccion') }}, {{ config('landing.ciudad') }} · Tel. {{ config('landing.telefono') }}</p>
                <p>© <span data-anio>{{ now()->year }}</span> BIOLAB. Responsable sanitario: Q.F.B. Ángel Augusto Pérez Arias, Céd. Prof. 14083392.</p>
            </div>
            <nav class="site-footer__links" aria-label="Enlaces secundarios">
                <a href="{{ route('novedades.index') }}">Novedades</a>
                <a href="{{ url('/medico/login') }}">Acceso para médicos</a>
                <a href="{{ $ancla('preguntas') }}">Empresas y consultorios</a>
            </nav>
        </div>
    </footer>

    @yield('despues_footer')

    {{-- Visor de imágenes de la galería --}}
    <dialog class="lightbox" id="lightbox" aria-labelledby="lightbox-titulo">
        <form method="dialog" class="lightbox__bar">
            <p class="lightbox__title" id="lightbox-titulo"></p>
            <button class="lightbox__close" type="submit">
                <svg class="icon" aria-hidden="true"><use href="#i-close"/></svg>
                <span class="sr-only">Cerrar imagen</span>
            </button>
        </form>
        <img class="lightbox__img" src="data:," alt="">
    </dialog>

    @stack('plantillas')
</body>
</html>
