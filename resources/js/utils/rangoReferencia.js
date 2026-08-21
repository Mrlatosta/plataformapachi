// Espejo en JS de App\Support\RangoReferencia (PHP).
// Determina si un resultado quedo por arriba ('alto') o por debajo ('bajo')
// del valor de referencia. Devuelve null cuando no se puede determinar.

const normalizar = (valor) => {
    if (valor === null || valor === undefined) return '';

    let texto = String(valor).trim().toLowerCase();

    texto = texto.replace(/[–—−]/g, '-');
    texto = texto.replace(/[áéíóú]/g, (letra) => ({ á: 'a', é: 'e', í: 'i', ó: 'o', ú: 'u' })[letra]);

    // 5,000 -> 5000 (miles) y 1,5 -> 1.5 (decimal)
    texto = texto.replace(/(\d),(\d{3})(?!\d)/g, '$1$2');
    texto = texto.replace(/(\d),(\d+)/g, '$1.$2');

    return texto.replace(/\s+/g, ' ');
};

const aNumero = (valor) => {
    const texto = normalizar(valor);
    if (!texto) return null;

    const match = texto.match(/-?\d+(?:\.\d+)?/);
    return match ? parseFloat(match[0]) : null;
};

export const direccionFueraRango = (resultado, valorReferencia) => {
    const valor = aNumero(resultado);
    if (valor === null) return null;

    const referencia = normalizar(valorReferencia);
    if (!referencia) return null;

    // Rango: "70 - 110", "0.5 a 1.2", "70 hasta 110"
    const rango = referencia.match(/(-?\d+(?:\.\d+)?)\s*(?:-|a|hasta)\s*(-?\d+(?:\.\d+)?)/);
    if (rango) {
        let min = parseFloat(rango[1]);
        let max = parseFloat(rango[2]);
        if (min > max) [min, max] = [max, min];

        if (valor < min) return 'bajo';
        if (valor > max) return 'alto';
        return null;
    }

    // Limite superior: "< 200", "menor a 200", "hasta 200"
    const superior = referencia.match(/^(?:<=?|menor(?:\s+(?:a|de|que))?|inferior(?:\s+a)?|hasta|maximo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/);
    if (superior) {
        return valor > parseFloat(superior[1]) ? 'alto' : null;
    }

    // Limite inferior: "> 40", "mayor a 40"
    const inferior = referencia.match(/^(?:>=?|mayor(?:\s+(?:a|de|que))?|superior(?:\s+a)?|minimo(?:\s+de)?)\s*(-?\d+(?:\.\d+)?)/);
    if (inferior) {
        return valor < parseFloat(inferior[1]) ? 'bajo' : null;
    }

    return null;
};

export const flechaRango = (direccion) => {
    if (direccion === 'alto') return '↑';
    if (direccion === 'bajo') return '↓';
    return '';
};
