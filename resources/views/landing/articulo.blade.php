@extends('landing.layout')

@php
    $imagen = $publicacion->imagen;
    $waUrl = 'https://wa.me/' . config('landing.whatsapp');
    // @json separa argumentos por comas: el arreglo debe ir en una variable
    $esquema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $publicacion->titulo,
        'datePublished' => $publicacion->fecha_publicacion->toDateString(),
        'dateModified' => $publicacion->updated_at?->toIso8601String(),
        'image' => $imagen?->url(),
        'publisher' => ['@type' => 'Organization', 'name' => 'BIOLAB Laboratorio de Análisis Clínicos'],
    ];
@endphp

@section('titulo', $publicacion->titulo . ' · BIOLAB Cancún')
@section('descripcion', $publicacion->resumen ?: \Illuminate\Support\Str::limit(strip_tags((string) $publicacion->contenido), 155))
@section('og_tipo', 'article')
@if ($imagen)
    @section('og_imagen', $imagen->url())
@endif

@push('head')
    <script type="application/ld+json">@json($esquema)</script>
@endpush

@section('contenido')
    <article class="section article" aria-labelledby="articulo-titulo">
        <div class="container article__container">
            <nav class="breadcrumb" aria-label="Ruta">
                <a href="{{ route('inicio') }}">Inicio</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('novedades.index') }}">Novedades</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $publicacion->titulo }}</span>
            </nav>

            <header class="article__head">
                <p class="eyebrow">Artículo</p>
                <h1 class="article__title" id="articulo-titulo">{{ $publicacion->titulo }}</h1>
                @if ($publicacion->resumen)
                    <p class="lead">{{ $publicacion->resumen }}</p>
                @endif
                <p class="article__meta">Publicado el <time datetime="{{ $publicacion->fecha_publicacion->toDateString() }}">{{ $publicacion->fechaLegible() }}</time></p>
            </header>

            @if ($imagen)
                <figure class="article__cover">
                    <img src="{{ $imagen->url() }}" alt="{{ $imagen->alt ?? '' }}" width="{{ $imagen->ancho }}" height="{{ $imagen->alto }}" fetchpriority="high" decoding="async">
                </figure>
            @endif

            <div class="prose">
                @foreach ($publicacion->parrafos() as $parrafo)
                    <p>{!! nl2br(e($parrafo)) !!}</p>
                @endforeach
            </div>

            <aside class="article__cta" aria-label="Agenda tu estudio">
                <h2 class="h3">¿Te quedó alguna duda?</h2>
                <p>Escríbenos y te respondemos con precio y preparación en menos de 1 hora.</p>
                <a class="btn btn--primary btn--lg" href="{{ $waUrl }}" data-wa="Hola, leí el artículo “{{ $publicacion->titulo }}” y tengo una duda." data-cta="articulo" target="_blank" rel="noopener">
                    <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                    Quiero agendar por WhatsApp
                </a>
            </aside>
        </div>
    </article>

    @if ($relacionadas->isNotEmpty())
        <section class="section section--alt" aria-labelledby="relacionadas-titulo">
            <div class="container">
                <div class="section__head">
                    <h2 class="h2" id="relacionadas-titulo">Más novedades</h2>
                </div>
                <div class="post-grid">
                    @foreach ($relacionadas as $relacionada)
                        @include('landing.partials.publicacion-card', ['publicacion' => $relacionada])
                    @endforeach
                </div>
                <div class="section-cta">
                    <a class="link" href="{{ route('novedades.index') }}">Ver todas las novedades <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                </div>
            </div>
        </section>
    @endif
@endsection
