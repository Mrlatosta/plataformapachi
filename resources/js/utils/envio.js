// T6: enviar al paciente el enlace seguro de sus resultados (el mismo del QR).
// El servidor solo lo entrega si todo lo que se imprime ya está validado.
import axios from 'axios';

const soloDigitos = (telefono) => String(telefono ?? '').replace(/\D/g, '');

// 9981234567 -> 529981234567 (WhatsApp necesita la lada del país)
const telefonoWhatsApp = (telefono) => {
    const digitos = soloDigitos(telefono);
    if (digitos.length === 10) return `52${digitos}`;
    return digitos;
};

const mensajePaciente = ({ nombre, folio, url }) => {
    const primerNombre = String(nombre ?? '').trim().split(/\s+/)[0] || '';
    const nombreBonito = primerNombre.charAt(0).toUpperCase() + primerNombre.slice(1).toLowerCase();
    return `Hola ${nombreBonito}, tus resultados de Laboratorio BIOLAB (folio ${folio}) ya están listos. `
        + `Puedes verlos y descargarlos aquí: ${url}`;
};

/**
 * Abre WhatsApp o el correo con el mensaje listo. Devuelve { ok, mensaje }.
 * La ventana se abre antes de pedir el enlace para que el navegador no la bloquee.
 */
export const enviarResultados = async (reporteId, canal) => {
    const ventana = canal === 'whatsapp' ? window.open('', '_blank') : null;

    try {
        const { data } = await axios.get(`/api/reportes/${reporteId}/envio`);
        const texto = mensajePaciente(data);

        if (canal === 'whatsapp') {
            const telefono = telefonoWhatsApp(data.telefono);
            const destino = `https://wa.me/${telefono}?text=${encodeURIComponent(texto)}`;
            if (ventana) ventana.location.href = destino;
            else window.open(destino, '_blank');
            return {
                ok: true,
                mensaje: telefono ? 'Se abrió WhatsApp con el mensaje listo.' : 'Se abrió WhatsApp; elige el contacto del paciente (no tiene teléfono registrado).',
            };
        }

        const asunto = `Resultados de laboratorio BIOLAB - ${data.folio}`;
        window.location.href = `mailto:${data.email || ''}?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(texto)}`;
        return {
            ok: true,
            mensaje: data.email ? 'Se abrió tu correo con el mensaje listo.' : 'Se abrió tu correo; escribe el correo del paciente (no tiene uno registrado).',
        };
    } catch (error) {
        ventana?.close();
        return { ok: false, mensaje: error.response?.data?.message || 'No se pudo preparar el envío.' };
    }
};
