<template>
  <Head title="Reimpresión de Resultados" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Reimpresión de Resultados
      </h2>
    </template>

    <div class="container mt-4">
      <h2 class="mb-4">Buscar reporte por folio</h2>

      <!-- Buscar por folio -->
      <div class="row mb-4">
        <div class="col-md-6">
          <label class="form-label">Folio del reporte</label>
          <input
            type="text"
            class="form-control"
            v-model="folioBusqueda"
            placeholder="Ejemplo: RPT-0001"
          />
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button class="btn btn-primary w-100" @click="buscarReporte">
            Buscar
          </button>
        </div>
      </div>

      <!-- Datos cargados -->
      <div v-if="form.folio" class="mt-4">
        <div class="alert alert-success mb-4">
          <strong>Reporte encontrado:</strong> {{ form.folio }}
        </div>

        <!-- Fechas y médico -->
        <div class="row mb-4">
          <div class="col-md-4">
            <label class="form-label">Toma de muestra</label>
            <input
              type="datetime-local"
              class="form-control"
              v-model="form.toma_muestra"
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de reporte</label>
            <input
              type="datetime-local"
              class="form-control"
              v-model="form.fecha_reporte"
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de validación</label>
            <input
              type="datetime-local"
              class="form-control"
              v-model="form.fecha_validacion"
            />
          </div>

          <div class="col-md-4 mt-3">
            <label class="form-label">Médico solicitante</label>
            <input
              type="text"
              class="form-control"
              v-model="form.medico_solicitante"
            />
          </div>
        </div>

        <!-- Datos del cliente -->
        <div class="card mb-4">
          <div class="card-header">Datos del Cliente</div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre</label>
              <input
                type="text"
                class="form-control"
                v-model="form.cliente.nombre"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input
                type="email"
                class="form-control"
                v-model="form.cliente.email"
              />
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha de nacimiento</label>
              <input
                type="date"
                class="form-control"
                v-model="form.cliente.fecha_nacimiento"
              />
            </div>
            <div class="col-md-4">
              <label class="form-label">Edad</label>
              <input
                type="number"
                class="form-control"
                v-model="form.cliente.edad"
              />
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

        <!-- Estudios existentes -->
        <div class="card mb-4">
          <div class="card-header">Estudios y Resultados</div>
          <div class="card-body">
            <div
              v-for="(estudio, index) in form.estudios"
              :key="index"
              class="border rounded p-3 mb-4"
            >
              <h5>{{ estudio.estudio_nombre }}</h5>

              <div class="row mb-2">
                <div class="col-md-3">
                  <label class="form-label">Tipo de muestra</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="estudio.tipo_muestra"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Método</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="estudio.metodo"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Elaboró</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="estudio.elaboro"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Validó</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="estudio.valido"
                  />
                </div>
              </div>

              <!-- Tabla de resultados -->
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
                      <td>{{ resultado.examen_nombre }}</td>
                      <td>{{ resultado.unidad }}</td>
                      <td>{{ resultado.valor_referencia }}</td>
                      <td>
                        <input
                          type="text"
                          class="form-control"
                          v-model="resultado.resultado"
                        />
                        <div class="form-check">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            v-model="resultado.fuera_rango"
                          />
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

        <!-- Botones -->
        <div class="text-end">
          <button class="btn btn-primary me-2" @click="actualizarReporte">
            Guardar Cambios
          </button>
          <button class="btn btn-success" @click="reimprimirReporte">
            Reimprimir PDF
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from "vue";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const folioBusqueda = ref("");
const form = reactive({
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

// Buscar reporte por folio
async function buscarReporte() {
  if (!folioBusqueda.value) return alert("Ingrese un folio válido");

  try {
    const response = await axios.get(`/api/reportes/folio/${folioBusqueda.value}`);
    Object.assign(form, response.data); // Cargar datos del reporte
    alert("Reporte cargado correctamente ✅");
  } catch (error) {
    console.error(error);
    alert("No se encontró el reporte");
  }
}

// Guardar cambios del reporte
async function actualizarReporte() {
  try {
    await axios.put(`/api/reportes/${form.id}`, form);
    alert("Cambios guardados correctamente ✅");
  } catch (error) {
    console.error(error);
    alert("Error al guardar los cambios");
  }
}

// Reimprimir PDF
function reimprimirReporte() {
  if (!form.id) return alert("Primero busca y guarda un reporte");
  window.open(`/api/reportes/${form.id}/pdf`, "_blank");
}
</script>
