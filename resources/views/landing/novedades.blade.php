@extends('landing.layout')

@section('titulo', 'Novedades y galería · BIOLAB Cancún')
@section('descripcion', 'Consejos de salud, noticias y fotos del laboratorio BIOLAB en Cancún.')

@section('contenido')
    <section class="section page-head" aria-labelledby="novedades-titulo">
        <div class="container">
            <nav class="breadcrumb" aria-label="Ruta">
                <a href="{{ route('inicio') }}">Inicio</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Novedades</span>
            </nav>
            <div class="section__head">
                <p class="eyebrow">Blog y galería</p>
                <h1 class="h2" id="novedades-titulo">Consejos de salud y lo último de BIOLAB</h1>
                <p class="lead">Artículos para prepararte mejor para tus estudios y fotos de nuestro laboratorio.</p>
            </div>

            @if ($publicaciones->isEmpty())
                <div class="offers-empty">
                    <span class="icon-tile tone-navy"><svg class="icon" aria-hidden="true"><use href="#i-doc"/></svg></span>
                    <h2 class="h3">Pronto publicaremos novedades</h2>
                    <p>Mientras tanto, si tienes una duda sobre tus estudios, escríbenos.</p>
                    <a class="btn btn--primary" href="https://wa.me/{{ config('landing.whatsapp') }}" data-wa="Hola, tengo una duda sobre un estudio." data-cta="novedades-vacio" target="_blank" rel="noopener">
                        <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                        Quiero resolver mi duda
                    </a>
                </div>
            @else
                <div class="post-grid">
                    @foreach ($publicaciones as $publicacion)
                        @include('landing.partials.publicacion-card', ['publicacion' => $publicacion])
                    @endforeach
                </div>

                @if ($publicaciones->hasPages())
                    <nav class="pager" aria-label="Paginación">
                        @if ($publicaciones->onFirstPage())
                            <span></span>
                        @else
                            <a class="btn btn--secondary" href="{{ $publicaciones->previousPageUrl() }}" rel="prev"><svg class="icon" aria-hidden="true"><use href="#i-arrow-left"/></svg> Más recientes</a>
                        @endif
                        <p class="pager__status">Página {{ $publicaciones->currentPage() }} de {{ $publicaciones->lastPage() }}</p>
                        @if ($publicaciones->hasMorePages())
                            <a class="btn btn--secondary" href="{{ $publicaciones->nextPageUrl() }}" rel="next">Anteriores <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                        @else
                            <span></span>
                        @endif
                    </nav>
                @endif
            @endif
        </div>
    </section>
@endsection
