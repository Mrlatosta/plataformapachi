// node tests/js/rangoReferencia.test.mjs — mismos casos que tests/Unit/RangoReferenciaTest.php
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import { evaluarResultado } from '../../resources/js/utils/rangoReferencia.js';

const casos = JSON.parse(readFileSync(new URL('../fixtures/rangos.json', import.meta.url)));
let fallas = 0;

for (const caso of casos) {
    const r = evaluarResultado(caso.resultado, caso.referencia, caso.sexo ?? null, caso.edad ?? null, caso.niveles ?? null);
    try {
        assert.equal(r.fuera_rango, caso.fuera, 'fuera_rango');
        assert.equal(r.direccion, caso.direccion, 'direccion');
    } catch {
        fallas++;
        console.error(`✗ ${caso.caso}: esperado ${caso.fuera}/${caso.direccion}, obtenido ${r.fuera_rango}/${r.direccion}`);
    }
}

console.log(`${casos.length - fallas}/${casos.length} casos correctos`);
process.exit(fallas ? 1 : 0);
