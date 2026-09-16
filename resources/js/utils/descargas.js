// Descarga un blob recibido con axios respetando el nombre que manda el
// servidor en Content-Disposition (p. ej. "Juan Perez - RPT-0155.pdf").

export function nombreDesdeRespuesta(response, respaldo) {
    const disposicion = response?.headers?.['content-disposition'] ?? '';

    // filename*=UTF-8''Juan%20P%C3%A9rez.pdf  (nombres con acentos)
    const utf8 = disposicion.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (utf8) {
        try {
            return decodeURIComponent(utf8[1].trim());
        } catch {
            // se intenta con el nombre simple
        }
    }

    const simple = disposicion.match(/filename\s*=\s*"?([^";]+)"?/i);
    return simple ? simple[1].trim() : respaldo;
}

export function descargarRespuesta(response, respaldo) {
    const url = window.URL.createObjectURL(
        new Blob([response.data], { type: 'application/pdf' })
    );
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', nombreDesdeRespuesta(response, respaldo));
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    // Se libera despues para no cortar la descarga en navegadores lentos
    setTimeout(() => window.URL.revokeObjectURL(url), 10000);
}
