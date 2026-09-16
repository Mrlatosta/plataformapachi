@extends('landing.layout')

@php
    $waUrl = 'https://wa.me/' . config('landing.whatsapp');
    $telefonoLink = 'tel:+' . config('landing.whatsapp');
    $direccionCompleta = config('landing.direccion') . ', ' . config('landing.ciudad');
    $mapa = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(config('landing.direccion') . ' Cancún Quintana Roo');
    $iniciales = (int) config('landing.estudios_iniciales');

    $esquema = [
        '@context' => 'https://schema.org',
        '@type' => 'DiagnosticLab',
        'name' => 'BIOLAB Laboratorio de Análisis Clínicos',
        'slogan' => 'Resultados que dan confianza',
        'url' => route('inicio'),
        'image' => asset('img/imgbiolabtrans.png'),
        'telephone' => '+52 ' . config('landing.telefono'),
        'email' => config('landing.correo'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => config('landing.direccion'),
            'addressLocality' => 'Cancún',
            'addressRegion' => 'Quintana Roo',
            'addressCountry' => 'MX',
        ],
        'openingHoursSpecification' => [
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:00', 'closes' => '18:00'],
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '08:00', 'closes' => '14:00'],
        ],
        'paymentAccepted' => 'Efectivo, transferencia',
    ];
@endphp

@push('head')
    <script type="application/ld+json">@json($esquema)</script>
@endpush

@section('contenido')
    {{-- 1. HERO --}}
    <section class="hero" id="inicio" aria-labelledby="hero-titulo">
        <div class="container hero__grid">
            <div>
                <p class="eyebrow">Laboratorio de análisis clínicos · Cancún</p>
                <h1 id="hero-titulo">Tus análisis clínicos, <span class="accent">en el laboratorio o en tu casa</span></h1>
                <p class="hero__lead">Escríbenos por WhatsApp: te decimos el precio y la preparación, y agendamos cuando te acomode. Cada resultado lleva un código QR para comprobar que es auténtico.</p>

                <div class="cta-block">
                    <a class="btn btn--primary btn--lg" href="{{ $waUrl }}" data-wa="Hola, quiero agendar un estudio. ¿Me pueden ayudar?" data-cta="hero" target="_blank" rel="noopener">
                        <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                        Quiero agendar por WhatsApp
                    </a>
                    <p class="cta-micro">
                        <span class="status-dot" data-horario-dot aria-hidden="true"></span>
                        <span data-horario>Respondemos en menos de 1 hora en horario de atención</span>
                        <span aria-hidden="true">·</span> Sin registro
                    </p>
                    <a class="link hero__secondary" href="#ofertas">Ver promociones del mes <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                </div>

                <ul class="trust" role="list">
                    <li><svg class="icon" aria-hidden="true"><use href="#i-shield"/></svg> Responsable sanitario con cédula</li>
                    <li><svg class="icon" aria-hidden="true"><use href="#i-doc"/></svg> Procesos conforme a NOM</li>
                    <li><svg class="icon" aria-hidden="true"><use href="#i-qr"/></svg> Resultados con QR verificable</li>
                </ul>
            </div>

            <figure class="hero__visual">
                <div class="chat" role="img" aria-label="Ejemplo de conversación: preguntas por WhatsApp cuánto cuesta un estudio y te respondemos con precio, preparación y horario.">
                    <div class="chat__header" aria-hidden="true">
                        <span class="chat__avatar">
                            <picture>
                                <source type="image/webp" srcset="{{ asset('landing/img/logo-80x96.webp') }}">
                                <img src="{{ asset('landing/img/logo-80x96.png') }}" width="32" height="38" alt="">
                            </picture>
                        </span>
                        <span>
                            <span class="chat__name">BIOLAB Laboratorio</span><br>
                            <span class="chat__meta">Responde en menos de 1 hora</span>
                        </span>
                    </div>
                    <div class="chat__body" aria-hidden="true">
                        <p class="bubble bubble--out">Hola 👋 ¿cuánto cuesta la química sanguínea?<span class="bubble__time">9:02</span></p>
                        <p class="bubble bubble--in">¡Hola! Te paso el precio y la preparación: necesitas 8 horas de ayuno.<span class="bubble__time">9:10</span></p>
                        <p class="bubble bubble--in">¿Te agendamos mañana a las 8:00? Puede ser aquí o en tu casa.<span class="bubble__time">9:10</span></p>
                        <p class="bubble bubble--out">¡En mi casa, porfa! 🙌<span class="bubble__time">9:11</span></p>
                    </div>
                </div>
                <figcaption class="chat__caption">Ejemplo de conversación. Así de rápido es cotizar.</figcaption>
            </figure>
        </div>
    </section>

    {{-- 2. BENEFICIOS --}}
    <section class="section section--alt" id="beneficios" aria-labelledby="beneficios-titulo">
        <div class="container">
            <div class="section__head section__head--center">
                <p class="eyebrow">Por qué BIOLAB</p>
                <h2 class="h2" id="beneficios-titulo">Hacerte estudios no tiene por qué ser complicado</h2>
            </div>

            <ul class="benefits" role="list">
                <li class="benefit">
                    <span class="icon-tile tone-green"><svg class="icon" aria-hidden="true"><use href="#i-home"/></svg></span>
                    <h3>Te atendemos donde te acomode</h3>
                    <p>Ven a nuestro laboratorio en la Av. 103 o pide la toma de muestra a domicilio en Cancún.</p>
                </li>
                <li class="benefit">
                    <span class="icon-tile tone-teal"><svg class="icon" aria-hidden="true"><use href="#i-tag"/></svg></span>
                    <h3>Sabes cuánto pagas antes de venir</h3>
                    <p>Pregunta por WhatsApp y en menos de 1 hora te respondemos con precio, preparación y tiempo de entrega.</p>
                </li>
                <li class="benefit">
                    <span class="icon-tile tone-navy"><svg class="icon" aria-hidden="true"><use href="#i-qr"/></svg></span>
                    <h3>Resultados que puedes comprobar</h3>
                    <p>Tu reporte lleva un código QR. Escanéalo y confirma que es auténtico y que nadie lo modificó.</p>
                </li>
            </ul>

            <div class="section-cta">
                <a class="btn btn--primary btn--lg" href="{{ $waUrl }}" data-wa="Hola, quiero agendar un estudio. ¿Me pueden ayudar?" data-cta="beneficios" target="_blank" rel="noopener">
                    <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                    Quiero agendar por WhatsApp
                </a>
                <p class="cta-micro">Pago en efectivo o transferencia · Sin registro</p>
            </div>
        </div>
    </section>

    {{-- 3. PROMOCIONES (se administran en Gestión Landing) --}}
    <section class="section" id="ofertas" aria-labelledby="ofertas-titulo">
        <div class="container">
            <div class="section__head section__head--center">
                <p class="eyebrow">Promociones del mes</p>
                <h2 class="h2" id="ofertas-titulo">Paquetes para cuidarte pagando menos</h2>
                <p class="lead">Estudios agrupados para que resuelvas todo en una sola toma de muestra.</p>
            </div>

            <p class="sr-only" id="ofertas-anuncio" role="status" aria-live="polite"></p>

            @if ($promociones->isEmpty())
                <div class="offers-grid" id="ofertas-lista" data-estado="vacio">
                    @include('landing.partials.ofertas-vacio')
                </div>
            @else
                <div class="offers-grid" id="ofertas-lista" aria-busy="true" data-estado="cargando" data-fuente="{{ url('/api/publico/promociones') }}">
                    @foreach ($promociones->take(3) as $_)
                        <div class="offer" data-skeleton aria-hidden="true"><div class="sk sk--media"></div><div class="offer__body"><div class="sk sk--title"></div><div class="sk sk--line"></div><div class="sk sk--line sk--short"></div><div class="sk sk--price"></div><div class="sk sk--btn"></div></div></div>
                    @endforeach
                    <noscript>
                        <div class="offers-empty">
                            <h3>Tenemos {{ $promociones->count() }} {{ $promociones->count() === 1 ? 'promoción activa' : 'promociones activas' }}</h3>
                            <p>Para verlas activa JavaScript o pregúntanos directamente.</p>
                            <a class="btn btn--primary" href="{{ $waUrl }}" target="_blank" rel="noopener">Quiero conocer las promociones</a>
                        </div>
                    </noscript>
                </div>
                <script type="application/json" id="datos-promociones">@json($promociones)</script>
            @endif

            <p class="offers-note">Precios en pesos mexicanos. Los marcadores tumorales apoyan el diagnóstico; interpreta tus resultados con tu médico.</p>
        </div>
    </section>

    {{-- 4. PRECIOS (estudios marcados como "Visible" en Gestión de Estudios) --}}
    <section class="section section--alt" id="precios" aria-labelledby="precios-titulo">
        <div class="container">
            <div class="section__head section__head--center">
                <p class="eyebrow">Precios claros</p>
                <h2 class="h2" id="precios-titulo">Encuentra tu estudio y su precio</h2>
                <p class="lead">Precios al público. ¿No ves el tuyo? Pregúntanos: toca cualquier estudio para escribirnos.</p>
            </div>

            @if ($estudios->isEmpty())
                <div class="offers-empty">
                    <span class="icon-tile tone-teal"><svg class="icon" aria-hidden="true"><use href="#i-tag"/></svg></span>
                    <h3>Estamos actualizando nuestra lista de precios</h3>
                    <p>Mientras tanto, escríbenos y te cotizamos cualquier estudio en menos de 1 hora.</p>
                </div>
            @else
                <div class="price-search" data-requiere-js>
                    <label class="price-search__label" for="buscar-estudio">Busca tu estudio</label>
                    <div class="price-search__field">
                        <svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>
                        <input class="input" id="buscar-estudio" type="search" placeholder="Ej. biometría, glucosa, tiroides" autocomplete="off" enterkeyhint="search" aria-describedby="buscar-estudio-estado">
                    </div>
                    <p class="field__hint" id="buscar-estudio-estado" role="status" aria-live="polite">{{ $estudios->count() }} {{ $estudios->count() === 1 ? 'estudio disponible' : 'estudios disponibles' }}</p>
                </div>

                <ul class="price-list" role="list" id="lista-estudios" data-iniciales="{{ $iniciales }}">
                    @foreach ($estudios as $estudio)
                        <li data-buscar="{{ $estudio['buscar'] }}">
                            <a class="price-item" href="{{ $waUrl }}" data-wa="Hola, quiero información del estudio: {{ $estudio['nombre'] }}." data-cta="precio" target="_blank" rel="noopener">
                                <span class="price-item__name">{{ $estudio['nombre'] }}</span>
                                @if ($estudio['precio'])
                                    <span class="price-item__price">{{ $estudio['precio'] }}</span>
                                @else
                                    <span class="price-item__price price-item__price--cotizar">Cotizar</span>
                                @endif
                                @if ($estudio['muestra'] || $estudio['entrega'])
                                    <span class="price-item__desc">
                                        @if ($estudio['muestra'])Muestra: {{ $estudio['muestra'] }}@endif
                                        @if ($estudio['muestra'] && $estudio['entrega']) · @endif
                                        @if ($estudio['entrega'])Entrega: {{ $estudio['entrega'] }}@endif
                                    </span>
                                @endif
                                <span class="price-item__ask">Preguntar por WhatsApp <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="offers-empty" id="estudios-sin-resultados" hidden>
                    <span class="icon-tile tone-teal"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg></span>
                    <h3>No encontramos “<span data-busqueda></span>”</h3>
                    <p>Puede que lo tengamos con otro nombre. Pregúntanos y te decimos el precio en menos de 1 hora.</p>
                    <a class="btn btn--primary" href="{{ $waUrl }}" data-wa-busqueda data-cta="precio-sin-resultados" target="_blank" rel="noopener">
                        <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                        Preguntar por este estudio
                    </a>
                </div>

                @if ($estudios->count() > $iniciales)
                    <div class="prices-more" data-requiere-js>
                        <button class="btn btn--secondary" type="button" id="ver-todos-estudios" aria-controls="lista-estudios" aria-expanded="false">
                            Ver los {{ $estudios->count() }} estudios
                        </button>
                    </div>
                @endif

                <p class="prices-note">Precios al público en pesos mexicanos, sujetos a cambio. Te confirmamos el total al agendar.</p>
            @endif

            <div class="section-cta">
                <a class="btn btn--primary btn--lg" href="{{ $waUrl }}" data-wa="Hola, quiero cotizar un estudio." data-cta="precios" target="_blank" rel="noopener">
                    <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                    Quiero cotizar mi estudio
                </a>
                <p class="cta-micro">¿Tienes orden médica? Mándanos la foto y te cotizamos todo.</p>
            </div>
        </div>
    </section>

    {{-- 5. CÓMO FUNCIONA --}}
    <section class="section" id="como-funciona" aria-labelledby="pasos-titulo">
        <div class="container">
            <div class="section__head section__head--center">
                <p class="eyebrow">Cómo funciona</p>
                <h2 class="h2" id="pasos-titulo">Tus estudios en 3 pasos</h2>
            </div>
            <ol class="steps" role="list">
                <li class="step">
                    <span class="step__num" aria-hidden="true">1</span>
                    <h3><span class="sr-only">Paso 1: </span>Escríbenos por WhatsApp</h3>
                    <p>Dinos qué estudio necesitas o mándanos la foto de tu orden. Te respondemos con precio y preparación.</p>
                </li>
                <li class="step">
                    <span class="step__num" aria-hidden="true">2</span>
                    <h3><span class="sr-only">Paso 2: </span>Tomamos tu muestra</h3>
                    <p>En el laboratorio o en tu casa. De lunes a viernes de 8:00 a 18:00 y sábados de 8:00 a 14:00.</p>
                </li>
                <li class="step">
                    <span class="step__num" aria-hidden="true">3</span>
                    <h3><span class="sr-only">Paso 3: </span>Recibe tus resultados</h3>
                    <p>Claros, firmados por nuestro responsable sanitario y con código QR para verificar su autenticidad.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- 6. CONFIANZA --}}
    <section class="section section--dark" id="confianza" aria-labelledby="confianza-titulo">
        <div class="container">
            <div class="section__head">
                <p class="eyebrow">Respaldo</p>
                <h2 class="h2" id="confianza-titulo">Confianza que puedes comprobar</h2>
                <p class="lead">Nada de promesas vagas: estos son datos que tú mismo puedes verificar.</p>
            </div>
            <div class="trust-grid">
                <div class="person-card">
                    <p class="person-card__role">Responsable sanitario</p>
                    <p class="person-card__name">Q.F.B. Ángel Augusto Pérez Arias</p>
                    <p class="person-card__id">Cédula profesional 14083392</p>
                    <a class="link" href="https://www.cedulaprofesional.sep.gob.mx/" target="_blank" rel="noopener">Verificar en el Registro Nacional de Profesionistas<span class="sr-only"> (abre en otra pestaña)</span> <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                </div>
                <ul class="checklist" role="list">
                    <li>
                        <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-award"/></svg></span>
                        <div><strong>Normas Oficiales Mexicanas</strong><p>Procesos conforme a las NOM para laboratorios clínicos y buenas prácticas.</p></div>
                    </li>
                    <li>
                        <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-shield"/></svg></span>
                        <div><strong>Control de calidad</strong><p>Controles internos y externos para que tus resultados sean confiables.</p></div>
                    </li>
                    <li>
                        <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-qr"/></svg></span>
                        <div><strong>Resultados verificables</strong><p>Cada reporte tiene un QR único que confirma que es original.</p></div>
                    </li>
                    <li>
                        <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg></span>
                        <div><strong>Confidencialidad total</strong><p>Tus datos y resultados solo los compartimos contigo y con el médico que tú indiques.</p></div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- 7. NOVEDADES (blog y galería) --}}
    @if ($publicaciones->isNotEmpty())
        <section class="section" id="novedades" aria-labelledby="novedades-titulo">
            <div class="container">
                <div class="section__head section__head--center">
                    <p class="eyebrow">Novedades</p>
                    <h2 class="h2" id="novedades-titulo">Consejos de salud y lo último de BIOLAB</h2>
                </div>
                <div class="post-grid">
                    @foreach ($publicaciones as $publicacion)
                        @include('landing.partials.publicacion-card', ['publicacion' => $publicacion])
                    @endforeach
                </div>
                @if ($hayMasPublicaciones)
                    <div class="section-cta">
                        <a class="btn btn--secondary" href="{{ route('novedades.index') }}">Ver todas las novedades <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- 8. FAQ --}}
    <section class="section section--alt" id="preguntas" aria-labelledby="faq-titulo">
        <div class="container">
            <div class="section__head section__head--center">
                <p class="eyebrow">Preguntas frecuentes</p>
                <h2 class="h2" id="faq-titulo">Resolvemos tus dudas antes de venir</h2>
            </div>
            <div class="faq">
                <details>
                    <summary><h3 class="faq__q">¿Tengo que ir en ayunas?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Depende del estudio. La química sanguínea y el perfil de lípidos requieren de 8 a 12 horas de ayuno; la biometría hemática y el examen de orina normalmente no. Al agendar te enviamos las indicaciones exactas.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Necesito orden médica?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Para los estudios de rutina, no. Algunas pruebas especiales se realizan bajo indicación médica. Si tienes orden, mándanos una foto por WhatsApp y te cotizamos todo.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Cuánto tardan mis resultados?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Depende del estudio. Te decimos el tiempo exacto de entrega al cotizar, antes de que pagues nada.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Toman la muestra a domicilio?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Sí, en Cancún. Escríbenos tu colonia y te confirmamos disponibilidad y costo antes de agendar.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Cómo puedo pagar?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">En efectivo o por transferencia.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Cómo sé que mis resultados son auténticos?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Cada reporte trae un código QR único. Al escanearlo con tu celular abres la versión original guardada en nuestro sistema, así puedes comprobar que nadie la alteró.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Los marcadores tumorales detectan el cáncer?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Ayudan a tu médico a detectar y dar seguimiento a algunos tipos de cáncer, pero un resultado por sí solo no confirma ni descarta un diagnóstico. Lleva siempre tus resultados a consulta.</p>
                </details>
                <details>
                    <summary><h3 class="faq__q">¿Atienden a empresas y consultorios?</h3><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                    <p class="faq__answer">Sí. Ofrecemos control sanitario y asesoría para empresas e instituciones, y maquila para laboratorios y consultorios. Escríbenos y te enviamos nuestras tarifas.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- 9. CIERRE --}}
    <section class="section closing" id="contacto" aria-labelledby="cierre-titulo">
        <div class="container closing__grid">
            <div class="closing__head">
                <p class="eyebrow">Agenda hoy</p>
                <h2 class="h2" id="cierre-titulo">Tu salud no tiene que esperar</h2>
                <p class="lead">Déjanos tu nombre y abrimos WhatsApp con tu mensaje listo para enviar.</p>
            </div>

            <div class="form-card">
                <h3 class="form-card__title">Arma tu mensaje en 10 segundos</h3>
                <form id="form-wa" novalidate>
                    <div class="field">
                        <label for="f-nombre">Tu nombre</label>
                        <input class="input" id="f-nombre" name="nombre" type="text" autocomplete="given-name" required maxlength="60" aria-describedby="f-nombre-ayuda f-nombre-error">
                        <p class="field__hint" id="f-nombre-ayuda">Así sabremos cómo dirigirnos a ti.</p>
                        <p class="field__error" id="f-nombre-error" hidden><svg class="icon" aria-hidden="true"><use href="#i-alert"/></svg><span></span></p>
                    </div>

                    <div class="field">
                        <label for="f-estudio">¿Qué estudio necesitas? <span class="field__optional">(opcional)</span></label>
                        <input class="input" id="f-estudio" name="estudio" type="text" list="lista-sugerencias" maxlength="120" autocomplete="off" aria-describedby="f-estudio-ayuda">
                        <datalist id="lista-sugerencias">
                            @foreach ($promociones as $promocion)
                                <option value="{{ $promocion['titulo'] }}"></option>
                            @endforeach
                            @foreach ($estudios as $estudio)
                                <option value="{{ $estudio['nombre'] }}"></option>
                            @endforeach
                        </datalist>
                        <p class="field__hint" id="f-estudio-ayuda">Si no lo sabes o tienes orden médica, déjalo vacío y mándanos la foto en el chat.</p>
                    </div>

                    <fieldset class="field">
                        <legend>¿Dónde prefieres que te atendamos?</legend>
                        <div class="choice-group">
                            <label class="choice"><input type="radio" name="lugar" value="laboratorio" checked> En el laboratorio</label>
                            <label class="choice"><input type="radio" name="lugar" value="domicilio"> En mi casa</label>
                        </div>
                    </fieldset>

                    <button class="btn btn--primary btn--lg btn--block" type="submit">
                        <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                        <span data-label>Quiero agendar por WhatsApp</span>
                    </button>
                    <p class="cta-micro">No guardamos tus datos: solo los usamos para escribir tu mensaje.</p>
                    <p class="form__status" id="form-estado" role="status" aria-live="polite"></p>
                </form>
            </div>

            <ul class="contact-list" role="list" aria-label="Datos de contacto">
                <li>
                    <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg></span>
                    <div>
                        <p class="contact-list__label">Dirección</p>
                        <p>{{ $direccionCompleta }}</p>
                        <a class="link" href="{{ $mapa }}" target="_blank" rel="noopener">Cómo llegar<span class="sr-only"> (abre Google Maps)</span></a>
                    </div>
                </li>
                <li>
                    <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg></span>
                    <div>
                        <p class="contact-list__label">Horario</p>
                        <p>Lunes a viernes, 8:00 a 18:00<br>Sábados, 8:00 a 14:00</p>
                    </div>
                </li>
                <li>
                    <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg></span>
                    <div>
                        <p class="contact-list__label">Teléfono y WhatsApp</p>
                        <p><a href="{{ $telefonoLink }}">{{ config('landing.telefono') }}</a></p>
                    </div>
                </li>
                <li>
                    <span class="icon-tile"><svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg></span>
                    <div>
                        <p class="contact-list__label">Correo</p>
                        <p><a href="mailto:{{ config('landing.correo') }}">{{ config('landing.correo') }}</a></p>
                    </div>
                </li>
            </ul>
        </div>
    </section>
@endsection

@section('despues_footer')
    {{-- CTA fijo en móvil --}}
    <div class="sticky-cta">
        <a class="btn btn--primary btn--block" href="{{ $waUrl }}" data-wa="Hola, quiero agendar un estudio. ¿Me pueden ayudar?" data-cta="sticky" target="_blank" rel="noopener">
            <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
            Quiero agendar por WhatsApp
        </a>
        <p class="cta-micro"><span class="status-dot" data-horario-dot aria-hidden="true"></span><span data-horario>Respondemos en menos de 1 hora en horario de atención</span></p>
    </div>
@endsection

@push('plantillas')
    <template id="tpl-oferta">
        <article class="offer">
            <p class="offer__ribbon" data-slot="ribbon" hidden>Promoción destacada</p>
            <div class="offer__media">
                <img data-slot="img" src="data:," alt="" width="480" height="300" loading="lazy" decoding="async">
                <div class="offer__badges">
                    <span class="badge" data-slot="etiqueta" hidden></span>
                    <span class="badge badge--descuento" data-slot="descuento" hidden><span aria-hidden="true" data-slot="descuento-v"></span><span class="sr-only" data-slot="descuento-sr"></span></span>
                </div>
            </div>
            <div class="offer__body">
                <h3 class="offer__title" data-slot="titulo"></h3>
                <p class="offer__desc" data-slot="descripcion"></p>
                <div data-slot="incluye-wrap">
                    <p class="offer__includes-title">Incluye</p>
                    <ul class="offer__includes" role="list" data-slot="incluye">
                        <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg><span></span></li>
                    </ul>
                </div>
                <div class="offer__pricing">
                    <p class="offer__price">
                        <span class="offer__old" data-slot="anterior-wrap" hidden><span class="sr-only">Precio normal: </span><del data-slot="anterior"></del></span>
                        <span class="offer__now"><span class="sr-only">Precio de promoción: </span><span data-slot="actual"></span></span>
                        <span class="offer__currency">MXN</span>
                    </p>
                    <p class="offer__save" data-slot="ahorro" hidden></p>
                </div>
                <p class="offer__urgency" data-slot="vigencia" hidden><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg><span data-slot="vigencia-texto"></span></p>
                <p class="offer__urgency offer__urgency--pronto" data-slot="cupos" hidden><svg class="icon" aria-hidden="true"><use href="#i-users"/></svg><span data-slot="cupos-texto"></span></p>
                <a class="btn btn--primary btn--block" data-slot="cta" data-cta="oferta" href="{{ $waUrl }}" target="_blank" rel="noopener">
                    <svg class="icon" aria-hidden="true"><use href="#i-wa"/></svg>
                    Quiero este paquete<span class="sr-only" data-slot="cta-sr"></span>
                </a>
            </div>
        </article>
    </template>

    <template id="tpl-ofertas-skeleton">
        @for ($i = 0; $i < 3; $i++)
            <div class="offer" data-skeleton aria-hidden="true"><div class="sk sk--media"></div><div class="offer__body"><div class="sk sk--title"></div><div class="sk sk--line"></div><div class="sk sk--line sk--short"></div><div class="sk sk--price"></div><div class="sk sk--btn"></div></div></div>
        @endfor
    </template>

    <template id="tpl-ofertas-vacio">
        @include('landing.partials.ofertas-vacio')
    </template>

    <template id="tpl-ofertas-error">
        <div class="offers-empty" role="alert">
            <span class="icon-tile tone-navy"><svg class="icon" aria-hidden="true"><use href="#i-alert"/></svg></span>
            <h3>No pudimos cargar las promociones</h3>
            <p>Revisa tu conexión e inténtalo de nuevo, o pregúntanos directamente por WhatsApp.</p>
            <button class="btn btn--secondary" type="button" data-action="reintentar">Reintentar</button>
            <a class="link" href="{{ $waUrl }}" data-wa="Hola, ¿qué promociones tienen este mes?" data-cta="ofertas-error" target="_blank" rel="noopener">Preguntar por WhatsApp</a>
        </div>
    </template>
@endpush
