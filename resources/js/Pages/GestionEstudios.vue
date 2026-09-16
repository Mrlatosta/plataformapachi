<template>
    <Head title="Gestión de Estudios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                🔬 Gestión de Estudios
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Encabezado -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Catálogo de estudios</h1>
                        <p class="text-sm text-gray-500">Consulta los estudios disponibles, sus exámenes y precios.</p>
                    </div>
                    <button
                        type="button"
                        @click="abrirEditor()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo estudio
                    </button>
                </div>

                <!-- Resumen -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Estudios</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ resumen.total }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Exámenes</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ resumen.examenes }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Visibles en /inicio</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ resumen.visibles }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Sin exámenes</p>
                        <p class="mt-1 text-2xl font-bold" :class="resumen.sinExamenes ? 'text-amber-600' : 'text-gray-900'">
                            {{ resumen.sinExamenes }}
                        </p>
                    </div>
                </div>

                <!-- Búsqueda, filtros y orden -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="busqueda"
                                type="search"
                                placeholder="Buscar por estudio, examen, sección, muestra o método..."
                                class="w-full pl-10 pr-3 py-2.5 text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <select
                            v-model="orden"
                            class="md:w-52 py-2.5 text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="nombre">Ordenar: nombre (A-Z)</option>
                            <option value="precio_asc">Ordenar: precio menor</option>
                            <option value="precio_desc">Ordenar: precio mayor</option>
                            <option value="examenes">Ordenar: más exámenes</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="opcion in filtros"
                            :key="opcion.valor"
                            type="button"
                            @click="filtro = opcion.valor"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-full border transition-colors"
                            :class="filtro === opcion.valor
                                ? 'bg-blue-600 border-blue-600 text-white'
                                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                        >
                            {{ opcion.etiqueta }}
                            <span
                                class="px-1.5 rounded-full text-xs"
                                :class="filtro === opcion.valor ? 'bg-white/20' : 'bg-gray-100 text-gray-600'"
                            >{{ opcion.total }}</span>
                        </button>
                        <span class="ml-auto text-xs text-gray-500">
                            Mostrando {{ estudiosFiltrados.length }} de {{ resumen.total }}
                        </span>
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="cargando" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="n in 6" :key="n" class="h-48 bg-white rounded-xl border border-gray-200 animate-pulse"></div>
                </div>

                <!-- Sin resultados -->
                <div
                    v-else-if="estudiosFiltrados.length === 0"
                    class="bg-white rounded-xl border border-dashed border-gray-300 py-16 text-center"
                >
                    <p class="text-4xl mb-2">🔍</p>
                    <p class="font-medium text-gray-700">No hay estudios que coincidan</p>
                    <p class="text-sm text-gray-500">Prueba con otra búsqueda o cambia el filtro.</p>
                </div>

                <!-- Catálogo -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                    <article
                        v-for="estudio in estudiosFiltrados"
                        :key="estudio.id"
                        class="bg-white rounded-xl border shadow-sm flex flex-col transition-shadow hover:shadow-md"
                        :class="estudio.visible_publico ? 'border-emerald-300' : 'border-gray-200'"
                    >
                        <div class="p-4 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-gray-900 leading-snug">{{ estudio.nombre }}</h3>
                                <span class="shrink-0 text-lg font-bold text-blue-700">{{ formatoMoneda(estudio.precio) }}</span>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span v-if="estudio.tipo_muestra" class="px-2 py-0.5 text-xs rounded-full bg-rose-50 text-rose-700 border border-rose-100">
                                    🧪 {{ estudio.tipo_muestra }}
                                </span>
                                <span v-if="estudio.metodo" class="px-2 py-0.5 text-xs rounded-full bg-violet-50 text-violet-700 border border-violet-100">
                                    🔬 {{ estudio.metodo }}
                                </span>
                                <span v-if="estudio.tiempo_entrega" class="px-2 py-0.5 text-xs rounded-full bg-sky-50 text-sky-700 border border-sky-100">
                                    ⏱️ {{ estudio.tiempo_entrega }}
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-lg bg-gray-50 py-1.5">
                                    <dt class="text-[11px] text-gray-500">Costo</dt>
                                    <dd class="text-sm font-medium text-gray-800">{{ formatoMoneda(estudio.costo) }}</dd>
                                </div>
                                <div class="rounded-lg bg-gray-50 py-1.5">
                                    <dt class="text-[11px] text-gray-500">Ganancia</dt>
                                    <dd class="text-sm font-medium" :class="ganancia(estudio) < 0 ? 'text-red-600' : 'text-emerald-700'">
                                        {{ formatoMoneda(ganancia(estudio)) }}
                                    </dd>
                                </div>
                                <div class="rounded-lg bg-gray-50 py-1.5">
                                    <dt class="text-[11px] text-gray-500">Margen</dt>
                                    <dd class="text-sm font-medium text-gray-800">{{ margen(estudio) }}%</dd>
                                </div>
                            </dl>

                            <p
                                v-if="examenesCoincidentes(estudio).length && !expandidos.has(estudio.id)"
                                class="mt-3 text-xs text-blue-700 bg-blue-50 rounded-lg px-2 py-1.5"
                            >
                                Incluye: {{ examenesCoincidentes(estudio).map(x => x.nombre_examen).join(', ') }}
                            </p>

                            <!-- Exámenes agrupados por sección -->
                            <div v-if="expandidos.has(estudio.id)" class="mt-3 border-t border-gray-100 pt-3">
                                <p v-if="estudio.examenes.length === 0" class="text-sm text-amber-700">
                                    Este estudio aún no tiene exámenes.
                                </p>
                                <div v-for="(grupo, g) in agruparPorSeccion(estudio.examenes)" :key="g" class="mb-2 last:mb-0">
                                    <p v-if="grupo.seccion" class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                        {{ grupo.seccion }}
                                    </p>
                                    <ul class="divide-y divide-gray-100 rounded-lg border border-gray-100">
                                        <li
                                            v-for="examen in grupo.examenes"
                                            :key="examen.id"
                                            class="px-2.5 py-1.5 text-sm"
                                            :class="coincideExamen(examen) ? 'bg-yellow-50' : ''"
                                        >
                                            <div class="flex items-baseline justify-between gap-2">
                                                <span class="text-gray-800">{{ examen.nombre_examen }}</span>
                                                <span v-if="examen.unidad" class="shrink-0 text-xs text-gray-500">{{ examen.unidad }}</span>
                                            </div>
                                            <p v-if="examen.valor_referencia" class="text-xs text-gray-500 whitespace-pre-line">
                                                Ref: {{ examen.valor_referencia }}
                                            </p>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="alternarExpandido(estudio.id)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-sm rounded-lg text-gray-700 hover:bg-gray-100"
                                :class="estudio.examenes.length === 0 ? 'text-amber-700' : ''"
                            >
                                <svg class="w-4 h-4 transition-transform" :class="expandidos.has(estudio.id) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                {{ estudio.examenes.length }} {{ estudio.examenes.length === 1 ? 'examen' : 'exámenes' }}
                            </button>

                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none" :title="'Marcar para mostrarlo a los pacientes en /inicio'">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="estudio.visible_publico"
                                    :disabled="actualizandoVisibilidad.has(estudio.id)"
                                    @click="cambiarVisibilidad(estudio)"
                                    class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 disabled:opacity-50"
                                    :class="estudio.visible_publico ? 'bg-emerald-500' : 'bg-gray-300'"
                                >
                                    <span
                                        class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transform transition-transform"
                                        :class="estudio.visible_publico ? 'translate-x-4' : 'translate-x-0.5'"
                                    ></span>
                                </button>
                                Visible
                            </label>

                            <button
                                type="button"
                                @click="abrirEditor(estudio)"
                                class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 011.4 0l1.6 1.6a1 1 0 010 1.4L9.5 17.5 6 18l.5-3.5 8.2-8.2z" />
                                </svg>
                                Editar
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <!-- Panel lateral: crear / editar estudio -->
        <Teleport to="body">
            <transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150"
                leave-to-class="opacity-0"
            >
                <div v-if="editorAbierto" class="fixed inset-0 z-40 bg-black/40" @click="cerrarEditor()"></div>
            </transition>
            <transition
                enter-active-class="transition-transform duration-200 ease-out"
                enter-from-class="translate-x-full"
                leave-active-class="transition-transform duration-150 ease-in"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="editorAbierto"
                    class="fixed inset-y-0 right-0 z-50 w-full max-w-3xl bg-gray-50 shadow-2xl flex flex-col"
                    role="dialog"
                    aria-modal="true"
                >
                    <header class="flex items-center justify-between gap-3 px-5 py-4 bg-white border-b border-gray-200">
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ form.id ? 'Editar estudio' : 'Nuevo estudio' }}</p>
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ form.nombre || 'Sin nombre' }}</h2>
                        </div>
                        <button type="button" @click="cerrarEditor()" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Cerrar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-5 space-y-5">
                        <!-- Información general -->
                        <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                            <h3 class="font-semibold text-gray-800">📝 Información general</h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del estudio *</label>
                                <input
                                    v-model="form.nombre"
                                    type="text"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Ej: Biometría Hemática Completa"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">🧪 Tipo de muestra</label>
                                    <input v-model="form.tipo_muestra" type="text" class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Sangre, orina..." />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">🔬 Método</label>
                                    <input v-model="form.metodo" type="text" class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Automatizado, manual..." />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">⏱️ Tiempo de entrega</label>
                                    <input v-model="form.tiempo_entrega" type="text" class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="24 horas, mismo día..." />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">💰 Precio *</label>
                                    <input v-model.number="form.precio" type="number" step="0.01" min="0" class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">🧾 Costo *</label>
                                    <input v-model.number="form.costo" type="number" step="0.01" min="0" class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <p class="block text-sm font-medium text-gray-700 mb-1">Ganancia</p>
                                    <p class="py-2 text-sm font-semibold" :class="ganancia(form) < 0 ? 'text-red-600' : 'text-emerald-700'">
                                        {{ formatoMoneda(ganancia(form)) }} <span class="font-normal text-gray-500">({{ margen(form) }}%)</span>
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">📄 Leyenda / notas</label>
                                <textarea
                                    v-model="form.leyenda"
                                    rows="3"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Instrucciones especiales, preparación del paciente, etc."
                                ></textarea>
                            </div>

                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                :class="form.visible_publico ? 'border-emerald-300 bg-emerald-50' : 'border-gray-300 hover:bg-gray-50'"
                            >
                                <input
                                    v-model="form.visible_publico"
                                    type="checkbox"
                                    class="mt-0.5 w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Es visible</span>
                                    <span class="block text-xs text-gray-500">
                                        Marca este estudio para mostrarlo a los pacientes en la página pública (/inicio) cuando se active esa sección.
                                    </span>
                                </span>
                            </label>
                        </section>

                        <!-- Exámenes -->
                        <section class="bg-white rounded-xl border border-gray-200 p-5">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h3 class="font-semibold text-gray-800">
                                    🧬 Exámenes incluidos
                                    <span class="ml-1 text-sm font-normal text-gray-500">({{ form.examenes.length }})</span>
                                </h3>
                                <button
                                    type="button"
                                    @click="agregarExamen"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    + Agregar examen
                                </button>
                            </div>

                            <!-- Guía: qué va en cada casilla -->
                            <details class="mb-4 rounded-lg border border-blue-100 bg-blue-50 text-sm" :open="form.examenes.length === 0">
                                <summary class="cursor-pointer select-none px-3 py-2 font-medium text-blue-800">
                                    ℹ️ ¿Qué va en cada casilla?
                                </summary>
                                <div class="px-3 pb-3 space-y-3 text-blue-950">
                                    <p>Cada examen es un renglón de la tabla de resultados del reporte:</p>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs bg-white rounded border border-blue-100">
                                            <thead class="bg-blue-100/70 text-left">
                                                <tr>
                                                    <th class="px-2 py-1">Examen</th>
                                                    <th class="px-2 py-1">Resultado</th>
                                                    <th class="px-2 py-1">Unidad</th>
                                                    <th class="px-2 py-1">Valor de referencia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="px-2 py-1">Glucosa</td>
                                                    <td class="px-2 py-1 italic text-gray-400">se captura después</td>
                                                    <td class="px-2 py-1">mg/dL</td>
                                                    <td class="px-2 py-1">70 - 110</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="space-y-1.5 list-disc pl-5">
                                        <li><strong>Nombre del examen</strong> (obligatorio): lo que se mide. Ej: Glucosa, Hemoglobina.</li>
                                        <li><strong>Unidad</strong>: en qué se expresa el resultado. Ej: mg/dL, g/dL, %.</li>
                                        <li>
                                            <strong>Valor de referencia</strong>: el rango normal. Escrito como
                                            <code class="px-1 bg-white rounded">70 - 110</code>, <code class="px-1 bg-white rounded">70 a 110</code>,
                                            <code class="px-1 bg-white rounded">&lt; 200</code> o <code class="px-1 bg-white rounded">&gt; 40</code>,
                                            el sistema marca solo ↑ alto / ↓ bajo. Textos como "Negativo" se imprimen tal cual.
                                        </li>
                                        <li><strong>Sección</strong> (opcional): subtítulo para agrupar exámenes, ej. "Perfil lipídico". Solo se imprime si el estudio tiene 2 o más secciones distintas.</li>
                                        <li><strong>Orden</strong>: usa ▲ ▼ para acomodarlos como deben salir en el reporte.</li>
                                    </ul>
                                </div>
                            </details>

                            <p v-if="form.examenes.length === 0" class="py-8 text-center text-sm text-gray-500 border border-dashed border-gray-300 rounded-lg">
                                No hay exámenes. Usa "Agregar examen" para comenzar.
                            </p>

                            <div class="space-y-3">
                                <div
                                    v-for="(examen, index) in form.examenes"
                                    :key="examen.id ?? `nuevo-${examen._clave}`"
                                    class="rounded-lg border p-4"
                                    :class="tieneNombre(examen) ? 'border-gray-200 bg-gray-50' : 'border-red-300 bg-red-50/50'"
                                >
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <p class="min-w-0 flex items-center gap-2 text-sm font-semibold text-gray-800">
                                            <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs">
                                                {{ index + 1 }}
                                            </span>
                                            <span class="truncate" :class="tieneNombre(examen) ? '' : 'italic text-gray-400'">
                                                {{ tieneNombre(examen) ? examen.nombre_examen : 'Examen sin nombre' }}
                                            </span>
                                        </p>
                                        <div class="shrink-0 flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="moverExamen(index, -1)"
                                                :disabled="index === 0"
                                                class="px-2 py-1 text-xs rounded bg-white border border-gray-300 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
                                                title="Subir en el reporte"
                                                aria-label="Subir examen"
                                            >▲</button>
                                            <button
                                                type="button"
                                                @click="moverExamen(index, 1)"
                                                :disabled="index === form.examenes.length - 1"
                                                class="px-2 py-1 text-xs rounded bg-white border border-gray-300 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
                                                title="Bajar en el reporte"
                                                aria-label="Bajar examen"
                                            >▼</button>
                                            <button
                                                type="button"
                                                @click="eliminarExamen(index)"
                                                class="px-2 py-1 text-xs rounded bg-white border border-red-200 text-red-600 hover:bg-red-50"
                                            >Quitar</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-x-3 gap-y-3">
                                        <div class="sm:col-span-4">
                                            <label :for="`examen-nombre-${index}`" class="block text-xs font-semibold text-gray-700 mb-1">
                                                Nombre del examen <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                :id="`examen-nombre-${index}`"
                                                v-model="examen.nombre_examen"
                                                class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Ej: Glucosa"
                                            />
                                            <p v-if="!tieneNombre(examen)" class="mt-1 text-xs text-red-600">
                                                Obligatorio. Si queda vacío, este examen no se guarda.
                                            </p>
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label :for="`examen-unidad-${index}`" class="block text-xs font-semibold text-gray-700 mb-1">
                                                Unidad
                                            </label>
                                            <input
                                                :id="`examen-unidad-${index}`"
                                                v-model="examen.unidad"
                                                list="unidades-catalogo"
                                                class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Ej: mg/dL"
                                            />
                                        </div>

                                        <div class="sm:col-span-4">
                                            <label :for="`examen-referencia-${index}`" class="block text-xs font-semibold text-gray-700 mb-1">
                                                Valor de referencia
                                            </label>
                                            <input
                                                :id="`examen-referencia-${index}`"
                                                v-model="examen.valor_referencia"
                                                class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Ej: 70 - 110"
                                            />
                                            <p class="mt-1 text-xs" :class="avisoReferencia(examen.valor_referencia).clase">
                                                {{ avisoReferencia(examen.valor_referencia).texto }}
                                            </p>
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label :for="`examen-seccion-${index}`" class="block text-xs font-semibold text-gray-700 mb-1">
                                                Sección <span class="font-normal text-gray-400">(opcional)</span>
                                            </label>
                                            <input
                                                :id="`examen-seccion-${index}`"
                                                v-model="examen.seccion"
                                                list="secciones-estudio"
                                                class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Ej: Perfil lipídico"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button
                                v-if="form.examenes.length >= 3"
                                type="button"
                                @click="agregarExamen"
                                class="mt-3 w-full py-2 text-sm font-medium rounded-lg border-2 border-dashed border-blue-300 text-blue-700 hover:bg-blue-50"
                            >
                                + Agregar otro examen
                            </button>

                            <!-- Sugerencias para autocompletar -->
                            <datalist id="unidades-catalogo">
                                <option v-for="unidad in unidadesCatalogo" :key="unidad" :value="unidad" />
                            </datalist>
                            <datalist id="secciones-estudio">
                                <option v-for="seccion in seccionesEstudio" :key="seccion" :value="seccion" />
                            </datalist>
                        </section>
                    </div>

                    <footer class="flex flex-col-reverse sm:flex-row sm:items-center gap-2 px-5 py-4 bg-white border-t border-gray-200">
                        <button
                            v-if="form.id"
                            type="button"
                            @click="mostrarModalEliminar = true"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-red-600 hover:bg-red-50"
                        >
                            🗑️ Eliminar estudio
                        </button>
                        <div class="sm:ml-auto flex flex-col-reverse sm:flex-row gap-2">
                            <button
                                type="button"
                                @click="cerrarEditor()"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                @click="guardarEstudio"
                                :disabled="guardando"
                                class="px-5 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                {{ guardando ? 'Guardando...' : (form.id ? '💾 Actualizar estudio' : '💾 Guardar estudio') }}
                            </button>
                        </div>
                    </footer>
                </aside>
            </transition>

            <!-- Confirmación para eliminar -->
            <div
                v-if="mostrarModalEliminar"
                class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
                @click.self="mostrarModalEliminar = false"
            >
                <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full text-center">
                    <div class="text-5xl mb-3">⚠️</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">¿Eliminar estudio?</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Se eliminará <strong>"{{ form.nombre }}"</strong> con sus exámenes. Esta acción no se puede deshacer.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <button
                            type="button"
                            @click="mostrarModalEliminar = false"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="eliminarEstudio"
                            class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700"
                        >
                            Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notificación -->
            <transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0 translate-y-2"
                leave-active-class="transition duration-150"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="toast"
                    class="fixed bottom-4 right-4 z-[70] max-w-sm px-4 py-3 rounded-lg shadow-lg text-sm text-white"
                    :class="toast.tipo === 'error' ? 'bg-red-600' : 'bg-gray-900'"
                    role="status"
                >
                    {{ toast.mensaje }}
                </div>
            </transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { interpretarReferencia } from '@/utils/rangoReferencia'

const estudios = ref([])
const cargando = ref(true)
const busqueda = ref('')
const filtro = ref('todos') // todos | visibles | ocultos | sin_examenes
const orden = ref('nombre')
const expandidos = ref(new Set())
const actualizandoVisibilidad = ref(new Set())

const estudioVacio = () => ({
    id: null,
    nombre: '',
    leyenda: '',
    tipo_muestra: '',
    metodo: '',
    tiempo_entrega: '',
    precio: 0,
    costo: 0,
    visible_publico: false,
    examenes: [],
})

const editorAbierto = ref(false)
const guardando = ref(false)
const form = ref(estudioVacio())
const formOriginal = ref('')
const mostrarModalEliminar = ref(false)
let claveExamen = 0

// ===== Notificaciones =====
const toast = ref(null)
let toastTimer = null
const notificar = (mensaje, tipo = 'success') => {
    toast.value = { mensaje, tipo }
    clearTimeout(toastTimer)
    toastTimer = setTimeout(() => { toast.value = null }, 3500)
}

// ===== Formato y utilidades =====
const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' })
const formatoMoneda = (valor) => moneda.format(Number(valor) || 0)

// Minúsculas y sin acentos para que "biometria" encuentre "Biometría"
const normalizar = (texto) => String(texto ?? '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .trim()

const ganancia = (estudio) => (Number(estudio.precio) || 0) - (Number(estudio.costo) || 0)
const margen = (estudio) => {
    const precio = Number(estudio.precio) || 0
    return precio > 0 ? Math.round((ganancia(estudio) / precio) * 100) : 0
}

// ===== Catálogo =====
const cargarEstudios = async () => {
    try {
        const response = await axios.get('/api/estudios')
        estudios.value = response.data
    } catch (error) {
        console.error('Error al cargar estudios:', error)
        notificar('No se pudieron cargar los estudios', 'error')
    } finally {
        cargando.value = false
    }
}

const resumen = computed(() => {
    const total = estudios.value.length
    const visibles = estudios.value.filter(e => e.visible_publico).length
    return {
        total,
        visibles,
        examenes: estudios.value.reduce((suma, e) => suma + e.examenes.length, 0),
        sinExamenes: estudios.value.filter(e => e.examenes.length === 0).length,
    }
})

const filtros = computed(() => [
    { valor: 'todos', etiqueta: 'Todos', total: resumen.value.total },
    { valor: 'visibles', etiqueta: 'Visibles', total: resumen.value.visibles },
    { valor: 'ocultos', etiqueta: 'Ocultos', total: resumen.value.total - resumen.value.visibles },
    { valor: 'sin_examenes', etiqueta: 'Sin exámenes', total: resumen.value.sinExamenes },
])

const terminoBusqueda = computed(() => normalizar(busqueda.value))

const coincideExamen = (examen) => {
    const q = terminoBusqueda.value
    return q !== '' && (normalizar(examen.nombre_examen).includes(q) || normalizar(examen.seccion).includes(q))
}

const examenesCoincidentes = (estudio) => estudio.examenes.filter(coincideExamen)

const comparadores = {
    nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es'),
    precio_asc: (a, b) => Number(a.precio) - Number(b.precio),
    precio_desc: (a, b) => Number(b.precio) - Number(a.precio),
    examenes: (a, b) => b.examenes.length - a.examenes.length,
}

const estudiosFiltrados = computed(() => {
    const q = terminoBusqueda.value

    const lista = estudios.value.filter((estudio) => {
        if (filtro.value === 'visibles' && !estudio.visible_publico) return false
        if (filtro.value === 'ocultos' && estudio.visible_publico) return false
        if (filtro.value === 'sin_examenes' && estudio.examenes.length > 0) return false
        if (!q) return true

        return [estudio.nombre, estudio.tipo_muestra, estudio.metodo].some(campo => normalizar(campo).includes(q))
            || estudio.examenes.some(coincideExamen)
    })

    return [...lista].sort(comparadores[orden.value])
})

// Agrupa los exámenes por sección conservando su orden
const agruparPorSeccion = (examenes) => {
    const grupos = []
    for (const examen of examenes) {
        const seccion = (examen.seccion || '').trim()
        const ultimo = grupos[grupos.length - 1]
        if (ultimo && ultimo.seccion === seccion) {
            ultimo.examenes.push(examen)
        } else {
            grupos.push({ seccion, examenes: [examen] })
        }
    }
    return grupos
}

const alternarExpandido = (id) => {
    const abiertos = new Set(expandidos.value)
    abiertos.has(id) ? abiertos.delete(id) : abiertos.add(id)
    expandidos.value = abiertos
}

// Guarda al instante la casilla "visible" desde la tarjeta
const cambiarVisibilidad = async (estudio) => {
    const nuevoValor = !estudio.visible_publico
    estudio.visible_publico = nuevoValor
    actualizandoVisibilidad.value = new Set(actualizandoVisibilidad.value).add(estudio.id)

    try {
        await axios.patch(`/api/estudios/${estudio.id}/visibilidad`, { visible_publico: nuevoValor })
        notificar(nuevoValor ? `"${estudio.nombre}" marcado como visible` : `"${estudio.nombre}" marcado como oculto`)
    } catch (error) {
        console.error('Error al cambiar visibilidad:', error)
        estudio.visible_publico = !nuevoValor
        notificar('No se pudo cambiar la visibilidad', 'error')
    } finally {
        const pendientes = new Set(actualizandoVisibilidad.value)
        pendientes.delete(estudio.id)
        actualizandoVisibilidad.value = pendientes
    }
}

// ===== Editor =====
const hayCambios = computed(() => JSON.stringify(form.value) !== formOriginal.value)

const abrirEditor = (estudio = null) => {
    form.value = estudio
        ? {
            id: estudio.id,
            nombre: estudio.nombre,
            leyenda: estudio.leyenda || '',
            tipo_muestra: estudio.tipo_muestra || '',
            metodo: estudio.metodo || '',
            tiempo_entrega: estudio.tiempo_entrega || '',
            precio: Number(estudio.precio) || 0,
            costo: Number(estudio.costo) || 0,
            visible_publico: Boolean(estudio.visible_publico),
            // Copia para no modificar el catálogo hasta guardar
            examenes: estudio.examenes.map(examen => ({ ...examen })),
        }
        : estudioVacio()

    formOriginal.value = JSON.stringify(form.value)
    editorAbierto.value = true
}

const cerrarEditor = (forzar = false) => {
    if (!forzar && hayCambios.value && !confirm('Tienes cambios sin guardar. ¿Deseas descartarlos?')) return
    editorAbierto.value = false
    mostrarModalEliminar.value = false
}

const tieneNombre = (examen) => Boolean(examen.nombre_examen && examen.nombre_examen.trim())

// Explica en vivo cómo se usará el valor de referencia en el reporte
const formatoNumero = (n) => n.toLocaleString('es-MX', { maximumFractionDigits: 4 })
const avisoReferencia = (valor) => {
    const ref = interpretarReferencia(valor)
    switch (ref.tipo) {
        case 'rango':
            return { clase: 'text-emerald-700', texto: `✓ Rango de ${formatoNumero(ref.min)} a ${formatoNumero(ref.max)}: marcará ↑ si el resultado es mayor y ↓ si es menor.` }
        case 'maximo':
            return { clase: 'text-emerald-700', texto: `✓ Máximo ${formatoNumero(ref.max)}: marcará ↑ si el resultado es mayor.` }
        case 'minimo':
            return { clase: 'text-emerald-700', texto: `✓ Mínimo ${formatoNumero(ref.min)}: marcará ↓ si el resultado es menor.` }
        case 'texto':
            return { clase: 'text-amber-700', texto: 'Texto libre: se imprime tal cual, sin flechas automáticas (se pueden marcar al capturar).' }
        default:
            return { clase: 'text-gray-500', texto: 'Sin referencia: la columna quedará vacía en el reporte.' }
    }
}

const valoresUnicos = (valores) => [...new Set(
    valores.map(v => (v || '').trim()).filter(Boolean)
)].sort((a, b) => a.localeCompare(b, 'es'))

// Unidades ya usadas en el catálogo, para sugerirlas al escribir
const unidadesCatalogo = computed(() => valoresUnicos(
    estudios.value.flatMap(e => e.examenes.map(x => x.unidad))
))

// Secciones del estudio en edición, para escribirlas siempre igual
const seccionesEstudio = computed(() => valoresUnicos(form.value.examenes.map(x => x.seccion)))

const agregarExamen = async () => {
    const siguienteOrden = form.value.examenes.length > 0
        ? Math.max(...form.value.examenes.map(e => e.orden || 0)) + 1
        : 1

    // Hereda la sección del último examen para agregar varios seguidos
    const ultimo = form.value.examenes[form.value.examenes.length - 1]

    form.value.examenes.push({
        _clave: ++claveExamen,
        seccion: ultimo?.seccion || '',
        orden: siguienteOrden,
        nombre_examen: '',
        unidad: '',
        valor_referencia: '',
    })

    await nextTick()
    const campo = document.getElementById(`examen-nombre-${form.value.examenes.length - 1}`)
    campo?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    campo?.focus({ preventScroll: true })
}

const moverExamen = (index, direccion) => {
    const examenes = form.value.examenes
    const destino = index + direccion
    if (destino < 0 || destino >= examenes.length) return

    const [examen] = examenes.splice(index, 1)
    examenes.splice(destino, 0, examen)
    examenes.forEach((e, i) => { e.orden = i + 1 })
}

const eliminarExamen = (index) => {
    form.value.examenes.splice(index, 1)
}

const guardarEstudio = async () => {
    if (!form.value.nombre || form.value.nombre.trim() === '') {
        notificar('Ingresa el nombre del estudio.', 'error')
        return
    }
    if (!form.value.precio || form.value.precio <= 0) {
        notificar('Ingresa un precio válido para el estudio.', 'error')
        return
    }
    if (form.value.costo === '' || form.value.costo === null || form.value.costo === undefined) {
        notificar('Ingresa un costo válido para el estudio.', 'error')
        return
    }

    guardando.value = true
    try {
        if (form.value.id) {
            await axios.put(`/api/estudios/${form.value.id}`, form.value)
            notificar('Estudio actualizado correctamente.')
        } else {
            await axios.post('/api/estudios', form.value)
            notificar('Estudio creado correctamente.')
        }
        cerrarEditor(true)
        await cargarEstudios()
    } catch (error) {
        console.error('Error al guardar estudio:', error)
        notificar(error.response?.data?.message || 'Error al guardar el estudio.', 'error')
    } finally {
        guardando.value = false
    }
}

const eliminarEstudio = async () => {
    try {
        await axios.delete(`/api/estudios/${form.value.id}`)
        notificar('Estudio eliminado correctamente.')
        cerrarEditor(true)
        await cargarEstudios()
    } catch (error) {
        console.error('Error al eliminar estudio:', error)
        notificar('Error al eliminar el estudio.', 'error')
        mostrarModalEliminar.value = false
    }
}

const cerrarConEscape = (evento) => {
    if (evento.key !== 'Escape') return
    if (mostrarModalEliminar.value) {
        mostrarModalEliminar.value = false
    } else if (editorAbierto.value) {
        cerrarEditor()
    }
}

onMounted(() => {
    cargarEstudios()
    window.addEventListener('keydown', cerrarConEscape)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', cerrarConEscape)
    clearTimeout(toastTimer)
})
</script>
