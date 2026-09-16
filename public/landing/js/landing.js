/* BIOLAB · Landing pública. JS vanilla, sin dependencias.
   Cada bloque revisa que su sección exista: el mismo archivo sirve para
   /inicio, /novedades y los artículos. */
(() => {
  'use strict';

  // ---------- Configuración ----------
  const WA_NUMERO = '529986543958';
  const TZ = 'America/Cancun';
  // Día de la semana (0 = domingo) -> [abre, cierra] en horas. Domingo cerrado.
  const HORARIO = { 1: [8, 18], 2: [8, 18], 3: [8, 18], 4: [8, 18], 5: [8, 18], 6: [8, 14] };
  const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
  const DIAS_CUENTA_REGRESIVA = 7; // antes de esto solo se muestra la fecha límite

  const MS_HORA = 36e5;
  const MS_DIA = 864e5;
  const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
  const fechaLarga = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', timeZone: TZ });

  const waUrl = (mensaje) => `https://wa.me/${WA_NUMERO}` + (mensaje ? `?text=${encodeURIComponent(mensaje)}` : '');
  const normalizar = (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`;

  // ---------- Enlaces de WhatsApp con mensaje prellenado ----------
  const prepararEnlaces = (raiz = document) => {
    raiz.querySelectorAll('a[data-wa]').forEach((a) => { a.href = waUrl(a.dataset.wa); });
  };

  // ---------- Medición (compatible con GTM / GA4 si existe dataLayer) ----------
  const medir = (evento, datos) => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: evento, ...datos });
  };
  document.addEventListener('click', (e) => {
    const cta = e.target.closest('a[data-cta]');
    if (cta) medir('cta_whatsapp', { cta_ubicacion: cta.dataset.cta, oferta_id: cta.dataset.oferta });
  });

  // ---------- Horario de atención en vivo ----------
  const ahoraEnCancun = () => {
    const partes = Object.fromEntries(
      new Intl.DateTimeFormat('en-US', { timeZone: TZ, weekday: 'short', hour: 'numeric', minute: 'numeric', hourCycle: 'h23' })
        .formatToParts(new Date())
        .map((p) => [p.type, p.value])
    );
    const dia = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(partes.weekday);
    return { dia, hora: Number(partes.hour) + Number(partes.minute) / 60 };
  };

  const estadoHorario = () => {
    const { dia, hora } = ahoraEnCancun();
    const hoy = HORARIO[dia];
    if (hoy && hora >= hoy[0] && hora < hoy[1]) {
      return { estado: 'abierto', texto: 'Abierto ahora · respondemos en menos de 1 hora' };
    }
    for (let i = 0; i < 7; i++) {
      const d = (dia + i) % 7;
      const h = HORARIO[d];
      if (!h || (i === 0 && hora >= h[0])) continue;
      const cuando = i === 0 ? 'hoy' : i === 1 ? 'mañana' : `el ${DIAS[d]}`;
      return { estado: 'cerrado', texto: `Cerrado ahora · te respondemos ${cuando} desde las ${h[0]}:00` };
    }
    return { estado: 'cerrado', texto: 'Respondemos en menos de 1 hora en horario de atención' };
  };

  const pintarHorario = () => {
    const { estado, texto } = estadoHorario();
    document.querySelectorAll('[data-horario]').forEach((el) => { el.textContent = texto; });
    document.querySelectorAll('[data-horario-dot]').forEach((el) => { el.dataset.estado = estado; });
  };

  // ---------- Header: sombra al hacer scroll ----------
  const header = document.querySelector('.site-header');
  let pendiente = false;
  const alHacerScroll = () => {
    if (!header || pendiente) return;
    pendiente = true;
    requestAnimationFrame(() => {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
      pendiente = false;
    });
  };

  // ---------- Menú móvil ----------
  const toggle = document.querySelector('.nav-toggle');
  const menu = document.getElementById('menu');
  if (toggle && menu) {
    const abrirMenu = (abrir) => {
      toggle.setAttribute('aria-expanded', String(abrir));
      toggle.querySelector('.sr-only').textContent = abrir ? 'Cerrar menú' : 'Abrir menú';
      menu.classList.toggle('is-open', abrir);
    };
    toggle.addEventListener('click', () => abrirMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    menu.addEventListener('click', (e) => { if (e.target.closest('a')) abrirMenu(false); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        abrirMenu(false);
        toggle.focus();
      }
    });
  }

  // ---------- CTA sticky en móvil ----------
  // Aparece cuando ya pasaste el botón del hero y se oculta al llegar al formulario de cierre.
  const sticky = document.querySelector('.sticky-cta');
  const ctaHero = document.querySelector('[data-cta="hero"]');
  const cierre = document.getElementById('contacto');
  if (sticky && ctaHero && cierre) {
    let heroPasado = false;
    let cierreVisible = false;
    const observadorSticky = new IntersectionObserver((entradas) => {
      entradas.forEach((en) => {
        if (en.target === ctaHero) heroPasado = !en.isIntersecting && en.boundingClientRect.top < 0;
        if (en.target === cierre) cierreVisible = en.isIntersecting;
      });
      sticky.classList.toggle('is-visible', heroPasado && !cierreVisible);
    });
    observadorSticky.observe(ctaHero);
    observadorSticky.observe(cierre);
  }

  // ---------- Promociones (Gestión Landing) ----------
  const lista = document.getElementById('ofertas-lista');
  const anuncio = document.getElementById('ofertas-anuncio');
  const tplOferta = document.getElementById('tpl-oferta');
  let ofertas = [];
  let idsPintados = '';

  const estaActiva = (o, ahora) =>
    o.activa !== false &&
    Number(o.precioActual) > 0 &&
    (!o.vigenteDesde || Date.parse(o.vigenteDesde) <= ahora) &&
    (!o.vigenteHasta || Date.parse(o.vigenteHasta) > ahora) &&
    !(Number.isInteger(o.cuposRestantes) && o.cuposRestantes <= 0);

  const textoVigencia = (o, ahora) => {
    if (!o.vigenteHasta) return null;
    const fin = Date.parse(o.vigenteHasta);
    const restante = fin - ahora;
    const hasta = `hasta el ${fechaLarga.format(fin)}`;
    if (restante > DIAS_CUENTA_REGRESIVA * MS_DIA) return { texto: `Vigente ${hasta}`, pronto: false };
    if (restante >= 2 * MS_DIA) return { texto: `Termina en ${Math.floor(restante / MS_DIA)} días · ${hasta}`, pronto: true };
    if (restante >= MS_HORA) {
      const horas = Math.floor(restante / MS_HORA);
      return { texto: `Termina en ${horas} ${horas === 1 ? 'hora' : 'horas'}`, pronto: true };
    }
    return { texto: 'Termina en menos de 1 hora', pronto: true };
  };

  const slot = (raiz, nombre) => raiz.querySelector(`[data-slot="${nombre}"]`);

  const actualizarVigencia = (tarjeta, o, ahora) => {
    const v = textoVigencia(o, ahora);
    const el = slot(tarjeta, 'vigencia');
    el.hidden = !v;
    if (!v) return;
    slot(tarjeta, 'vigencia-texto').textContent = v.texto;
    el.classList.toggle('offer__urgency--pronto', v.pronto);
  };

  const crearTarjeta = (o, ahora) => {
    const t = tplOferta.content.firstElementChild.cloneNode(true);
    const idTitulo = `oferta-${o.id}`;
    t.setAttribute('aria-labelledby', idTitulo);
    t.dataset.id = o.id;
    if (o.destacada) {
      t.classList.add('offer--featured');
      slot(t, 'ribbon').hidden = false;
    }

    const img = slot(t, 'img');
    if (o.imagen?.src) {
      img.src = o.imagen.src;
      img.alt = o.imagen.alt || '';
      if (o.imagen.ancho) img.width = o.imagen.ancho;
      if (o.imagen.alto) img.height = o.imagen.alto;
    } else {
      img.remove();
    }

    if (o.etiqueta?.texto) {
      const et = slot(t, 'etiqueta');
      et.textContent = o.etiqueta.texto;
      et.classList.add(`badge--${o.etiqueta.tono || 'marino'}`);
      et.hidden = false;
    }

    const actual = Number(o.precioActual);
    const anterior = Number(o.precioAnterior);
    if (anterior > actual) {
      const pct = Math.round((1 - actual / anterior) * 100);
      slot(t, 'descuento-v').textContent = `-${pct}%`;
      slot(t, 'descuento-sr').textContent = `${pct}% de descuento`;
      slot(t, 'descuento').hidden = false;
      slot(t, 'anterior').textContent = moneda.format(anterior);
      slot(t, 'anterior-wrap').hidden = false;
      slot(t, 'ahorro').textContent = `Ahorras ${moneda.format(anterior - actual)}`;
      slot(t, 'ahorro').hidden = false;
    }
    slot(t, 'actual').textContent = moneda.format(actual);

    const titulo = slot(t, 'titulo');
    titulo.id = idTitulo;
    titulo.textContent = o.titulo;
    const descripcion = slot(t, 'descripcion');
    if (o.descripcion) descripcion.textContent = o.descripcion;
    else descripcion.remove();

    const ul = slot(t, 'incluye');
    const tplItem = ul.firstElementChild;
    tplItem.remove();
    if (Array.isArray(o.incluye) && o.incluye.length) {
      o.incluye.forEach((texto) => {
        const li = tplItem.cloneNode(true);
        li.querySelector('span').textContent = texto;
        ul.append(li);
      });
    } else {
      slot(t, 'incluye-wrap').remove();
    }

    if (Number.isInteger(o.cuposRestantes) && o.cuposRestantes > 0) {
      slot(t, 'cupos-texto').textContent =
        o.cuposRestantes === 1 ? 'Queda 1 lugar' : `Quedan ${o.cuposRestantes} lugares`;
      slot(t, 'cupos').hidden = false;
    }
    actualizarVigencia(t, o, ahora);

    const cta = slot(t, 'cta');
    cta.dataset.oferta = o.id;
    cta.href = waUrl(o.mensajeWhatsApp || `Hola, me interesa la promoción ${o.titulo} de ${moneda.format(actual)}.`);
    slot(t, 'cta-sr').textContent = `: ${o.titulo} (abre WhatsApp)`;
    return t;
  };

  const pintarDesdePlantilla = (idPlantilla) => {
    const nodo = document.getElementById(idPlantilla).content.cloneNode(true);
    prepararEnlaces(nodo);
    lista.replaceChildren(nodo);
  };

  const pintarOfertas = () => {
    const ahora = Date.now();
    const activas = ofertas.filter((o) => estaActiva(o, ahora));
    const ids = activas.map((o) => o.id).join('|');

    if (ids === idsPintados && lista.dataset.estado === 'listo') {
      // Mismo conjunto: solo refresca textos de vigencia (sin perder el foco del usuario)
      activas.forEach((o) => {
        const tarjeta = lista.querySelector(`[data-id="${CSS.escape(o.id)}"]`);
        if (tarjeta) actualizarVigencia(tarjeta, o, ahora);
      });
      return;
    }

    idsPintados = ids;
    lista.dataset.estado = 'listo';
    lista.setAttribute('aria-busy', 'false');

    if (!activas.length) {
      pintarDesdePlantilla('tpl-ofertas-vacio');
      anuncio.textContent = 'No hay promociones activas en este momento.';
      return;
    }
    lista.replaceChildren(...activas.map((o) => crearTarjeta(o, ahora)));
    anuncio.textContent = activas.length === 1 ? '1 promoción activa.' : `${activas.length} promociones activas.`;
  };

  // Los datos vienen incrustados en la página; la API solo se usa para reintentar
  const obtenerOfertas = async (desdeApi) => {
    const incrustadas = document.getElementById('datos-promociones');
    if (incrustadas && !desdeApi) return JSON.parse(incrustadas.textContent);

    const res = await fetch(lista.dataset.fuente, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const datos = await res.json();
    return Array.isArray(datos) ? datos : datos.ofertas;
  };

  const cargarOfertas = async (desdeApi = false) => {
    lista.setAttribute('aria-busy', 'true');
    lista.dataset.estado = 'cargando';
    idsPintados = '';
    try {
      const datos = await obtenerOfertas(desdeApi);
      ofertas = Array.isArray(datos) ? datos : [];
      pintarOfertas();
    } catch (err) {
      console.error('No se pudieron cargar las promociones:', err);
      lista.dataset.estado = 'error';
      lista.setAttribute('aria-busy', 'false');
      pintarDesdePlantilla('tpl-ofertas-error');
      anuncio.textContent = 'No pudimos cargar las promociones.';
    }
  };

  const hayOfertas = lista && tplOferta && lista.dataset.estado !== 'vacio';
  if (hayOfertas) {
    lista.addEventListener('click', (e) => {
      if (e.target.closest('[data-action="reintentar"]')) {
        pintarDesdePlantilla('tpl-ofertas-skeleton');
        cargarOfertas(true);
      }
    });
  }

  // ---------- Buscador de estudios ----------
  const listaEstudios = document.getElementById('lista-estudios');
  if (listaEstudios) {
    const items = [...listaEstudios.children];
    const iniciales = Number(listaEstudios.dataset.iniciales) || items.length;
    const buscador = document.getElementById('buscar-estudio');
    const estado = document.getElementById('buscar-estudio-estado');
    const sinResultados = document.getElementById('estudios-sin-resultados');
    const botonTodos = document.getElementById('ver-todos-estudios');
    let expandido = false;
    let temporizadorEstado;

    const aplicarFiltro = () => {
      const consulta = buscador ? buscador.value.trim() : '';
      const terminos = normalizar(consulta).split(/\s+/).filter(Boolean);
      let coincidencias = 0;

      items.forEach((li, i) => {
        const coincide = terminos.every((t) => li.dataset.buscar.includes(t));
        if (coincide) coincidencias++;
        const dentroDelLimite = terminos.length > 0 || expandido || i < iniciales;
        li.hidden = !(coincide && dentroDelLimite);
      });

      if (botonTodos) botonTodos.parentElement.hidden = terminos.length > 0 || expandido;

      if (sinResultados) {
        sinResultados.hidden = coincidencias > 0;
        if (!coincidencias) {
          sinResultados.querySelector('[data-busqueda]').textContent = consulta;
          sinResultados.querySelector('[data-wa-busqueda]').href =
            waUrl(`Hola, ¿tienen el estudio "${consulta}"? ¿Cuál es su precio?`);
        }
      }

      // El anuncio para lectores de pantalla espera a que dejes de escribir
      clearTimeout(temporizadorEstado);
      temporizadorEstado = setTimeout(() => {
        if (!estado) return;
        estado.textContent = !terminos.length
          ? plural(items.length, 'estudio disponible', 'estudios disponibles')
          : coincidencias
            ? `${plural(coincidencias, 'estudio coincide', 'estudios coinciden')} con tu búsqueda`
            : 'Sin coincidencias';
      }, 400);
    };

    buscador?.addEventListener('input', aplicarFiltro);
    botonTodos?.addEventListener('click', () => {
      expandido = true;
      botonTodos.setAttribute('aria-expanded', 'true');
      aplicarFiltro();
      items[iniciales]?.querySelector('a')?.focus();
    });
    aplicarFiltro();
  }

  // ---------- Visor de imágenes (galería) ----------
  const visor = document.getElementById('lightbox');
  if (visor && typeof visor.showModal === 'function') {
    const imagen = visor.querySelector('.lightbox__img');
    const titulo = visor.querySelector('.lightbox__title');
    document.addEventListener('click', (e) => {
      const enlace = e.target.closest('a[data-lightbox]');
      if (!enlace) return;
      e.preventDefault();
      imagen.src = enlace.href;
      imagen.alt = enlace.dataset.lightboxAlt || '';
      titulo.textContent = enlace.dataset.lightboxTitulo || '';
      visor.showModal();
    });
    // Clic fuera de la imagen cierra el visor
    visor.addEventListener('click', (e) => { if (e.target === visor) visor.close(); });
  }

  // ---------- Formulario → WhatsApp ----------
  const form = document.getElementById('form-wa');
  if (form) {
    const nombre = document.getElementById('f-nombre');
    const errorNombre = document.getElementById('f-nombre-error');
    const estudio = document.getElementById('f-estudio');
    const estadoForm = document.getElementById('form-estado');
    const botonForm = form.querySelector('button[type="submit"]');
    const textoBoton = botonForm.querySelector('[data-label]');
    let nombreTocado = false;

    const validarNombre = () => {
      const v = nombre.value.trim();
      let msg = '';
      if (!v) msg = 'Escribe tu nombre para saber cómo dirigirnos a ti.';
      else if (v.length < 2) msg = 'Tu nombre necesita al menos 2 letras.';
      else if (!/^[\p{L}][\p{L}\s.'-]*$/u.test(v)) msg = 'Usa solo letras y espacios, sin números ni símbolos.';

      nombre.setAttribute('aria-invalid', String(Boolean(msg)));
      nombre.classList.toggle('is-valid', !msg);
      errorNombre.hidden = !msg;
      errorNombre.querySelector('span').textContent = msg;
      return !msg;
    };

    nombre.addEventListener('blur', () => { if (nombre.value.trim()) nombreTocado = true; if (nombreTocado) validarNombre(); });
    nombre.addEventListener('input', () => { if (nombreTocado) validarNombre(); });

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      nombreTocado = true;
      if (!validarNombre()) {
        nombre.focus();
        return;
      }

      const lugar = form.elements.lugar.value;
      const que = estudio.value.trim();
      const mensaje =
        `Hola, soy ${nombre.value.trim()}. Me gustaría agendar ${que ? `el estudio: ${que}` : 'un estudio'}` +
        ` ${lugar === 'domicilio' ? 'con toma de muestra a domicilio' : 'en el laboratorio'}. ¿Me dan precio y disponibilidad?`;
      const url = waUrl(mensaje);

      botonForm.setAttribute('aria-busy', 'true');
      textoBoton.textContent = 'Abriendo WhatsApp…';
      medir('cta_whatsapp', { cta_ubicacion: 'formulario', lugar });

      const ventana = window.open(url, '_blank');
      if (ventana) ventana.opener = null;
      else window.location.href = url;

      estadoForm.replaceChildren(
        'Listo, abrimos WhatsApp con tu mensaje. ¿No se abrió? ',
        Object.assign(document.createElement('a'), { href: url, target: '_blank', rel: 'noopener', textContent: 'Ábrelo aquí' })
      );
      setTimeout(() => {
        botonForm.removeAttribute('aria-busy');
        textoBoton.textContent = 'Quiero agendar por WhatsApp';
      }, 1500);
    });
  }

  // ---------- Arranque ----------
  prepararEnlaces();
  pintarHorario();
  alHacerScroll();
  window.addEventListener('scroll', alHacerScroll, { passive: true });
  document.querySelectorAll('[data-anio]').forEach((el) => { el.textContent = new Date().getFullYear(); });
  if (hayOfertas) cargarOfertas();

  // Refresca horario y vigencias cada minuto (sin reiniciar contadores: todo sale de fechas fijas)
  setInterval(() => {
    pintarHorario();
    if (hayOfertas && lista.dataset.estado === 'listo') pintarOfertas();
  }, 60_000);
})();
