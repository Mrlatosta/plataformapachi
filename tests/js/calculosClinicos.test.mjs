// node tests/js/calculosClinicos.test.mjs — ejemplo de prueba del ticket T9
import assert from 'node:assert/strict';
import { aplicarCalculos, claveAnalito, NO_CALCULABLE_TG } from '../../resources/js/utils/calculosClinicos.js';

const ex = (nombre_examen, resultado = '', unidad = 'mg/dL') => ({ nombre_examen, unidad, resultado });

// Nombres tal como están en el catálogo (Química sanguínea)
const quimica = [
    ex('Colesterol Total', '205.5'),
    ex('Trigliceridos', '152.9'),
    ex('Colesterol de Alta Densidad (HDL)', '18.0'),
    ex('Colesterol de Baja Densidad (LDL)'),
    ex('Colesterol de Muy Baja Densidad (VLDL)'),
    ex('Índice Aterogénico', '', ''),
    ex('CLDL/CHDL', '', ''),
    ex('Lipidos Totales'),
    ex('Urea', '42.8'),
    ex('Nitrogeno Ureico (BUN)'),
    ex('Proteínas Totales', '7.2', 'g/dL'),
    ex('Albumina', '4.1', 'g/dL'),
    ex('Globulina', '', 'g/dL'),
    ex('Relación A/G', '', ''),
    ex('Bilirrubina Total', '0.85'),
    ex('Bilirrubina Directa', '0.20'),
    ex('Bilirrubina Indirecta'),
    ex('Glucosa', '100'),
];
const insulina = [ex('INSULINA', '10', 'μlU/ml'), ex('HOMA-IR', '', '')];
const ego = [ex('Glucosa', 'Negativo', '')];

aplicarCalculos([quimica, insulina, ego]);
const valor = (lista, nombre) => lista.find((e) => e.nombre_examen === nombre).resultado;

assert.equal(valor(quimica, 'Colesterol de Muy Baja Densidad (VLDL)'), '30.6');
assert.equal(valor(quimica, 'Colesterol de Baja Densidad (LDL)'), '156.9');
assert.equal(valor(quimica, 'Índice Aterogénico'), '11.42');
assert.equal(valor(quimica, 'Lipidos Totales'), '681.7');
assert.equal(valor(quimica, 'CLDL/CHDL'), '8.72');
assert.equal(valor(quimica, 'Nitrogeno Ureico (BUN)'), '20.0');
assert.equal(valor(quimica, 'Globulina'), '3.1');
assert.equal(valor(quimica, 'Relación A/G'), '1.32');
assert.equal(valor(quimica, 'Bilirrubina Indirecta'), '0.65');
assert.equal(valor(insulina, 'HOMA-IR'), '2.47');
assert.equal(quimica.find((e) => e.nombre_examen === 'Lipidos Totales').calculado, true);

// Si se corrige un valor de origen, el calculado se actualiza
quimica[1].resultado = '450';
aplicarCalculos([quimica, insulina, ego]);
assert.equal(valor(quimica, 'Colesterol de Baja Densidad (LDL)'), NO_CALCULABLE_TG);
assert.equal(valor(quimica, 'Colesterol de Muy Baja Densidad (VLDL)'), NO_CALCULABLE_TG);

// Si falta un dato de origen, el calculado se limpia y vuelve a ser editable
quimica[8].resultado = '';
aplicarCalculos([quimica, insulina, ego]);
assert.equal(valor(quimica, 'Nitrogeno Ureico (BUN)'), '');
assert.equal(quimica[9].calculado, false);

// La glucosa del EGO (sin unidad) no se confunde con la de sangre
assert.equal(claveAnalito('Glucosa', ''), null);
assert.equal(claveAnalito('Colesterol', 'mg/dL'), 'ct');

console.log('calculosClinicos: todos los casos correctos');
