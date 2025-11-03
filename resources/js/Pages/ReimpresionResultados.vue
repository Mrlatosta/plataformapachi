<template>
  <Head title="Reimpresión de Resultados" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Reimpresión de Resultados
      </h2>
    </template>

    <div class="container mt-4">
      <h4 class="mb-4 fw-bold">Buscar reporte por folio</h4>
<!-- Buscar por folio -->
<div class="row mb-4">
  <div class="col-md-6">
    <label class="form-label">Folio del reporte</label>
    <div class="input-group">
      <span class="input-group-text">RPT-</span>
      <input
        type="number"
        class="form-control"
        v-model="folioBusqueda"
        placeholder="Ejemplo: 156"
        @keyup.enter="buscarReporte"
      />
    </div>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100" @click="buscarReporte">
      Buscar
    </button>
  </div>
</div>
      <!-- Datos cargados -->
      <div v-if="form.id" class="mt-4">
        <div class="alert alert-success mb-4">
          <strong>Reporte encontrado:</strong> {{ form.folio }}
        </div>

        <!-- Fechas y médico -->
        <div class="row mb-4">
          <div class="col-md-3">
            <label class="form-label">Toma de muestra</label>
            <input type="datetime-local" class="form-control" v-model="form.toma_muestra" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha de reporte</label>
            <input type="datetime-local" class="form-control" v-model="form.fecha_reporte" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha de validación</label>
            <input type="datetime-local" class="form-control" v-model="form.fecha_validacion" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Médico solicitante</label>
            <input type="text" class="form-control" v-model="form.medico_solicitante" />
          </div>
        </div>

        <!-- Cliente -->
        <div class="card mb-4">
          <div class="card-header">Datos del Cliente</div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" v-model="form.cliente.nombre" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" v-model="form.cliente.email" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha de nacimiento</label>
              <input type="date" class="form-control" v-model="form.cliente.fecha_nacimiento" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Edad</label>
              <input type="number" class="form-control" v-model="form.cliente.edad" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Sexo</label>
              <select class="form-select" v-model="form.cliente.sexo">
                <option value="">Seleccione</option>
                <option>Masculino</option>
                <option>Femenino</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Buscar nuevo estudio -->
        <div class="card mb-4">
          <div class="card-header">Agregar Estudio</div>
          <div class="card-body">
            <div class="mb-3 position-relative" style="max-width: 500px;">
              <label class="form-label">Buscar estudio</label>
              <input
                type="text"
                class="form-control"
                placeholder="Escribe o haz clic para ver todos..."
                v-model="busquedaEstudio"
                @focus="mostrarTodosEstudios"
                @input="filtrarEstudios"
              />
              <ul
                v-if="estudiosFiltrados.length > 0"
                class="list-group position-absolute w-100 mt-1 shadow"
                style="z-index: 1000; max-height: 180px; overflow-y: auto;"
              >
                <li
                  v-for="estudio in estudiosFiltrados"
                  :key="estudio.id"
                  class="list-group-item list-group-item-action"
                  @click="agregarEstudio(estudio)"
                  style="cursor: pointer;"
                >
                  <div class="d-flex justify-content-between align-items-center">
                    <span>{{ estudio.nombre }}</span>
                    <small class="text-muted">${{ parseFloat(estudio.precio).toFixed(2) }}</small>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Estudios existentes -->
        <div class="card mb-4">
          <div class="card-header">Estudios y Resultados</div>
          <div class="card-body">
            <div
              v-for="(estudio, index) in form.estudios"
              :key="index"
              class="border rounded p-3 mb-4"
            >
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5>{{ estudio.estudio?.nombre || estudio.nombre }}</h5>
                <button class="btn btn-sm btn-outline-danger" @click="eliminarEstudio(index)">
                  🗑️ Eliminar
                </button>
              </div>

              <div class="row mb-2">
                <div class="col-md-3">
                  <label class="form-label">Tipo de muestra</label>
                  <input type="text" class="form-control" v-model="estudio.tipo_muestra" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Método</label>
                  <input type="text" class="form-control" v-model="estudio.metodo" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Elaboró</label>
                  <input type="text" class="form-control" v-model="estudio.elaboro" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Validó</label>
                  <input type="text" class="form-control" v-model="estudio.valido" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Precio ($)</label>
                  <input type="number" class="form-control" step="0.01" v-model="estudio.precio" />
                </div>
              </div>

              <!-- Tabla -->
              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead>
                    <tr>
                      <th>Examen</th>
                      <th>Unidad</th>
                      <th>Valor referencia</th>
                      <th>Resultado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(resultado, rIndex) in estudio.resultados"
                      :key="rIndex"
                    >
                      <td>{{ resultado.examen?.nombre_examen || resultado.nombre_examen }}</td>
                      <td>{{ resultado.examen?.unidad || resultado.unidad }}</td>
                      <td>{{ resultado.examen?.valor_referencia || resultado.valor_referencia }}</td>
                      <td>
                        <input type="text" class="form-control" v-model="resultado.resultado" />
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" v-model="resultado.fuera_rango" />
                          <label class="form-check-label">F.R.</label>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Total -->
        <div class="card">
          <div class="card-body text-end">
            <h5 class="fw-bold">Total: ${{ totalEstudios.toFixed(2) }}</h5>
          </div>
        </div>

        <!-- Botones -->
        <div class="text-end mt-4" style="padding-bottom: 200px;">
          <button class="btn btn-primary me-2" @click="actualizarReporte">Guardar Cambios</button>
          <button class="btn btn-success" @click="reimprimirReporte">Descargar PDF</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

const folioBusqueda = ref("");
const estudiosDisponibles = ref([]);
const estudiosFiltrados = ref([]);

const form = reactive({
  id: null,
  folio: "",
  toma_muestra: "",
  fecha_reporte: "",
  fecha_validacion: "",
  medico_solicitante: "",
  cliente: {
    nombre: "",
    email: "",
    fecha_nacimiento: "",
    edad: "",
    sexo: "",
  },
  estudios: [],
});

// Cargar estudios
onMounted(async () => {
  const response = await axios.get("/api/estudios");
  estudiosDisponibles.value = response.data;
});

const totalEstudios = computed(() => {
  return form.estudios.reduce((sum, e) => sum + (parseFloat(e.precio) || 0), 0);
});

// function eliminarEstudio(index) {
//   if (confirm('¿Deseas eliminar este estudio del reporte?')) {
//     form.estudios.splice(index, 1)
//   }
// }


// Buscar por folio
async function buscarReporte() {
  if (!folioBusqueda.value) return alert("Ingrese un número de folio válido");

  try {
    // 🔹 Formatear el folio automáticamente (RPT-0001, RPT-0156, etc.)
    const numero = folioBusqueda.value.toString().padStart(4, "0");
    const folioFormateado = `RPT-${numero}`;

    // 🔹 Consultar la API
    const response = await axios.get(`/api/reportes/folio/${folioFormateado}`);
    const data = response.data;

    // 🔹 Rellenar datos del reporte
    form.id = data.id;
    form.folio = data.folio;
    form.toma_muestra = data.toma_muestra;
    form.fecha_reporte = data.fecha_reporte;
    form.fecha_validacion = data.fecha_validacion;
    form.medico_solicitante = data.medico_solicitante;

    // 🔹 Datos del cliente
    form.cliente.nombre = data.nombre_cliente || "";
    form.cliente.email = data.email || "";
    form.cliente.fecha_nacimiento = data.fecha_nacimiento || "";
    form.cliente.edad = data.edad || "";
    form.cliente.sexo = data.sexo || "";

    // 🔹 Estudios y resultados
    form.estudios = (data.estudios || []).map(e => ({
      id: e.id,
      estudio_id: e.estudio_id,
      nombre: e.estudio?.nombre || "",
      tipo_muestra: e.tipo_muestra,
      metodo: e.metodo,
      elaboro: e.elaboro,
      valido: e.valido,
      precio: e.precio || 0,
      resultados: (e.resultados || []).map(r => ({
        id: r.id,
        examen_id: r.examen_id,
        nombre_examen: r.examen?.nombre_examen || "",
        unidad: r.examen?.unidad || "",
        valor_referencia: r.examen?.valor_referencia || "",
        resultado: r.resultado || "",
        fuera_rango: r.fuera_rango || false,
      })),
    }));

    alert(`Reporte ${folioFormateado} cargado correctamente ✅`);
  } catch (error) {
    console.error(error);
    alert("❌ No se encontró el reporte");
  }
}


// Guardar cambios
async function actualizarReporte() {
  try {
    await axios.put(`/api/reportes/${form.id}`, form);
    alert("Cambios guardados correctamente ✅");
  } catch (error) {
    console.error(error);
    alert("Error al guardar los cambios");
  }
}

// Descargar PDFs automáticamente
async function reimprimirReporte() {
  if (!form.id) return alert("Primero busca un reporte válido");

  try {
    const pdfResponse = await axios.get(`/api/reportes/${form.id}/pdf`, { responseType: "blob" });
    const url = window.URL.createObjectURL(new Blob([pdfResponse.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `reporte-${form.folio}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    const ordenResponse = await axios.get(`/api/reportes/${form.id}/orden`, { responseType: "blob" });
    const urlOrden = window.URL.createObjectURL(new Blob([ordenResponse.data]));
    const linkOrden = document.createElement("a");
    linkOrden.href = urlOrden;
    linkOrden.setAttribute("download", `orden-trabajo-${form.folio}.pdf`);
    document.body.appendChild(linkOrden);
    linkOrden.click();
    document.body.removeChild(linkOrden);

  } catch (error) {
    console.error(error);
    alert("Error al generar el PDF");
  }
}

// Autocompletado
function filtrarEstudios() {
  const termino = busquedaEstudio.value.toLowerCase().trim();
  estudiosFiltrados.value = termino === ""
    ? [...estudiosDisponibles.value]
    : estudiosDisponibles.value.filter(e => e.nombre.toLowerCase().includes(termino));
}
function mostrarTodosEstudios() {
  estudiosFiltrados.value = [...estudiosDisponibles.value];
}
function agregarEstudio(estudio) {
  if (form.estudios.some(e => e.estudio_id === estudio.id || e.id === estudio.id)) {
    alert("Este estudio ya fue agregado.");
    return;
  }
  form.estudios.push({
    estudio_id: estudio.id,
    nombre: estudio.nombre,
    tipo_muestra: estudio.tipo_muestra,
    metodo: estudio.metodo,
    elaboro: "Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS",
    valido: "Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS",
    precio: estudio.precio || 0,
    resultados: estudio.examenes.map(e => ({
      examen_id: e.id,
      nombre_examen: e.nombre_examen,
      unidad: e.unidad,
      valor_referencia: e.valor_referencia,
      resultado: "",
      fuera_rango: false,
    })),
  });
  busquedaEstudio.value = "";
  estudiosFiltrados.value = [];
}
function eliminarEstudio(index) {
  if (confirm("¿Deseas eliminar este estudio?"))
   form.estudios.splice(index, 1);
}
</script>

<style>
.list-group-item:hover {
  background-color: #e9f0ff;
}
</style>
