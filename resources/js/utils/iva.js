// Porcentaje de IVA usado en cotizaciones y ordenes de trabajo.
// Debe coincidir con config/facturacion.php ('iva').
export const IVA_PORCENTAJE = 16

/**
 * Calcula el monto de IVA sobre una base gravable.
 */
export function calcularIva(base, aplica = true) {
  if (!aplica) return 0
  const monto = (parseFloat(base) || 0) * (IVA_PORCENTAJE / 100)
  return Math.round(monto * 100) / 100
}
