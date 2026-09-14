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

/**
 * Totales de una orden de trabajo: el descuento (%) se aplica sobre el
 * subtotal y el IVA sobre el subtotal ya con descuento.
 * Debe coincidir con App\Models\Reporte (monto_descuento, monto_iva, total).
 */
export function calcularTotales(subtotal, descuento = 0, aplicaIva = false) {
  const sub = parseFloat(subtotal) || 0
  const porcentaje = Math.min(Math.max(parseFloat(descuento) || 0, 0), 100)
  const base = sub - sub * (porcentaje / 100)
  const iva = calcularIva(base, aplicaIva)

  return {
    subtotal: sub,
    montoDescuento: Math.round(sub * porcentaje) / 100,
    iva,
    total: Math.round((base + iva) * 100) / 100,
  }
}
