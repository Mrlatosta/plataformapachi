{{-- Tarjeta de blog/galería. Espera $publicacion (LandingPublicacion con imagen ligera). --}}
@php
    $imagen = $publicacion->imagen;
    $esArticulo = $publicacion->esArticulo();
@endphp
<article class="post-card {{ $esArticulo ? '' : 'post-card--foto' }}">
    @if ($imagen)
        <div class="post-card__media">
            <img src="{{ $imagen->url() }}" alt="{{ $imagen->alt ?? '' }}" width="{{ $imagen->ancho }}" height="{{ $imagen->alto }}" loading="lazy" decoding="async">
            @unless ($esArticulo)
                <span class="post-card__zoom" aria-hidden="true"><svg class="icon"><use href="#i-expand"/></svg></span>
            @endunless
        </div>
    @else
        {{-- Portada de respaldo para que la cuadrícula no quede dispareja --}}
        <div class="post-card__media post-card__media--vacia" aria-hidden="true">
            <svg class="icon"><use href="#i-doc"/></svg>
        </div>
    @endif
    <div class="post-card__body">
        <p class="post-card__meta">
            <span class="post-card__tipo">{{ $esArticulo ? 'Artículo' : 'Galería' }}</span>
            <time datetime="{{ $publicacion->fecha_publicacion->toDateString() }}">{{ $publicacion->fechaLegible() }}</time>
        </p>
        <h3 class="post-card__title">
            @if ($esArticulo)
                <a class="post-card__link" href="{{ route('novedades.show', $publicacion->slug) }}">{{ $publicacion->titulo }}</a>
            @elseif ($imagen)
                {{-- Sin JS abre la imagen; con JS se muestra en el visor --}}
                <a class="post-card__link" href="{{ $imagen->url() }}" data-lightbox data-lightbox-titulo="{{ $publicacion->titulo }}" data-lightbox-alt="{{ $imagen->alt ?? $publicacion->titulo }}">{{ $publicacion->titulo }}<span class="sr-only"> (ver imagen ampliada)</span></a>
            @else
                {{ $publicacion->titulo }}
            @endif
        </h3>
        @if ($publicacion->resumen)
            <p class="post-card__excerpt">{{ $publicacion->resumen }}</p>
        @endif
        @if ($esArticulo)
            <span class="post-card__more" aria-hidden="true">Leer artículo <svg class="icon"><use href="#i-arrow"/></svg></span>
        @endif
    </div>
</article>
