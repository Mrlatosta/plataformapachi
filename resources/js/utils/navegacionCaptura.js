// T11: capturar todo con el teclado. Enter pasa a la casilla de resultado del
// siguiente renglón (y al primero del siguiente estudio); Shift + Enter regresa.
// Se saltan los renglones AUTO (solo lectura) y los bloqueados.
// Enter nunca guarda, valida ni genera el PDF: la pantalla no tiene <form>
// y aquí se cancela la acción normal de la tecla.

export const ATRIBUTO_RESULTADO = 'data-captura-resultado';

/**
 * Casilla a la que se mueve el cursor, o null si ya es la última (o la primera,
 * hacia atrás). Función pura para poder probarla sin navegador.
 */
export const destinoEnter = (campos, origen, haciaAtras = false) => {
    const indice = campos.indexOf(origen);
    if (indice === -1) return null;
    return campos[indice + (haciaAtras ? -1 : 1)] ?? null;
};

const esCapturable = (el) =>
    !el.readOnly && !el.disabled && el.offsetParent !== null && el.getAttribute('aria-hidden') !== 'true';

const mover = (origen, haciaAtras) => {
    const campos = [...document.querySelectorAll(`[${ATRIBUTO_RESULTADO}]`)].filter(esCapturable);
    const destino = destinoEnter(campos, origen, haciaAtras);
    if (!destino) return;
    destino.focus();
    destino.select?.();
};

/**
 * Manejador de keydown para la casilla de resultado.
 */
export const alPresionarEnter = (evento) => {
    if (evento.key !== 'Enter' || evento.isComposing || evento.altKey || evento.ctrlKey || evento.metaKey) return;

    const origen = evento.currentTarget;

    // Casillas con lista (Positivo/Negativo...): si hay una opción resaltada, el
    // navegador la confirma con este mismo Enter. No se cancela la tecla para no
    // perder esa selección; el cursor se mueve justo después.
    if (origen.list) {
        setTimeout(() => mover(origen, evento.shiftKey), 0);
        return;
    }

    evento.preventDefault();
    mover(origen, evento.shiftKey);
};
