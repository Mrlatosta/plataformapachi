// node tests/js/funcionRenal.test.mjs — casos de prueba del ticket T10 (y T11)
import assert from 'node:assert/strict';
import {
    analizarFuncionRenal,
    categoriaA,
    categoriaG,
    claveRenal,
    relacionAlbuminaCreatinina,
    tfgeCistatina,
    tfgeCombinada,
    tfgeCreatinina,
} from '../../resources/js/utils/funcionRenal.js';
import { destinoEnter } from '../../resources/js/utils/navegacionCaptura.js';

// TFGe por creatinina (CKD-EPI 2021)
for (const [cr, edad, sexo, esperada] of [
    [0.71, 48, 'F', 105],
    [1.0, 50, 'M', 92],
    [1.0, 50, 'F', 69],
    [1.3, 70, 'M', 59],
    [2.5, 65, 'M', 28],
    [1.1, 20, 'M', 99],
]) {
    assert.equal(tfgeCreatinina(cr, edad, sexo), esperada, `creatinina ${cr}, ${edad} ${sexo}`);
}

// Cistatina C (2012) y combinada (2021)
for (const [cr, cis, edad, sexo, porCistatina, combinada] of [
    [1.0, 1.0, 50, 'M', 81, 88],
    [1.2, 1.2, 60, 'F', 57, 56],
    [0.8, 0.7, 40, 'F', 113, 108],
    [2.0, 1.8, 70, 'M', 34, 35],
]) {
    assert.equal(tfgeCistatina(cis, edad, sexo), porCistatina, `cistatina ${cis}`);
    assert.equal(tfgeCombinada(cr, cis, edad, sexo), combinada, `combinada ${cr}/${cis}`);
}

// El sexo llega como lo guarda el sistema
assert.equal(tfgeCreatinina(1.0, 50, 'Femenino'), 69);
assert.equal(tfgeCreatinina(1.0, 50, 'masculino'), 92);

// Menores de 18, sin sexo o sin creatinina: no se calcula
assert.equal(tfgeCreatinina(1.0, 17, 'M'), null);
assert.equal(tfgeCreatinina(1.0, '', 'M'), null);
assert.equal(tfgeCreatinina(1.0, 40, ''), null);
assert.equal(tfgeCreatinina(0, 40, 'M'), null);

// RAC: 45 mg/L ÷ 150 mg/dL = 30.0 mg/g (A2); con albúmina en mg/dL el factor es 1000
assert.equal(relacionAlbuminaCreatinina(45, 'mg/L', 150), 30);
assert.equal(relacionAlbuminaCreatinina(4.5, 'mg/dL', 150), 30);
assert.equal(categoriaA(30).codigo, 'A2');
assert.equal(categoriaA(29.9).codigo, 'A1');
assert.equal(categoriaA(300).codigo, 'A2');
assert.equal(categoriaA(300.1).codigo, 'A3');

// Categorías G
assert.deepEqual([92, 69, 56, 35, 28, 10, 59, 60, 90].map((v) => categoriaG(v).codigo),
    ['G1', 'G2', 'G3a', 'G3b', 'G4', 'G5', 'G3a', 'G2', 'G1']);

// Reconocer analitos
assert.equal(claveRenal('Creatinina', 'mg/dL', 'Suero'), 'creatinina');
assert.equal(claveRenal('Creatinina', 'mg/dl', 'ORINA'), 'creatinina_orina');
assert.equal(claveRenal('Creatinina en orina', 'mg/dL', ''), 'creatinina_orina');
assert.equal(claveRenal('Albúmina en orina', 'mg/L', ''), 'albumina_orina');
assert.equal(claveRenal('Microalbuminuria', 'mg/L', 'Orina'), 'albumina_orina');
assert.equal(claveRenal('Albumina', 'g/dL', 'Suero'), null); // la de la química no
assert.equal(claveRenal('Cistatina C', 'mg/L', 'SUERO'), 'cistatina');
assert.equal(claveRenal('Creatine Kinase (CK)', 'U/L', 'suero'), null);
assert.equal(claveRenal('Depuración de creatinina', 'mL/min', ''), null);

// Orden completa: química + cistatina + albuminuria
const orden = [
    { tipo_muestra: 'SUERO', examenes: [
        { nombre_examen: 'Glucosa', unidad: 'mg/dL', resultado: '90' },
        { nombre_examen: 'Creatinina', unidad: 'mg/dL', resultado: '1.3' },
    ] },
    { tipo_muestra: 'SUERO', examenes: [{ nombre_examen: 'Cistatina C', unidad: 'mg/L', resultado: '1.0' }] },
    { tipo_muestra: 'ORINA', examenes: [
        { nombre_examen: 'Albúmina en orina', unidad: 'mg/L', resultado: '45' },
        { nombre_examen: 'Creatinina en orina', unidad: 'mg/dL', resultado: '150' },
    ] },
];
const { filas, valores } = analizarFuncionRenal(orden, { edad: 70, sexo: 'Masculino' });
assert.deepEqual(Object.keys(filas).sort(), ['0:1', '1:0', '2:1']);
assert.equal(filas['0:1'][0].valor, 59);
assert.equal(filas['0:1'][0].bajo, true); // < 60 → ↓ Bajo
assert.deepEqual(filas['1:0'].map((f) => f.clave), ['tfge_cis', 'tfge_comb']);
assert.equal(filas['2:1'][0].texto, '30.0');
assert.equal(filas['2:1'][0].categoria.codigo, 'A2');
assert.equal(valores.G.codigo, categoriaG(valores.tfge_comb).codigo); // G de la combinada
assert.equal(valores.A.codigo, 'A2');

// Menor de edad: no aparece ningún renglón de TFGe
assert.deepEqual(analizarFuncionRenal(orden, { edad: 15, sexo: 'Masculino' }).filas['0:1'], undefined);

// T11: Enter avanza, Shift+Enter regresa, en los extremos no se mueve
const campos = ['a', 'b', 'c'];
assert.equal(destinoEnter(campos, 'a'), 'b');
assert.equal(destinoEnter(campos, 'c'), null);
assert.equal(destinoEnter(campos, 'b', true), 'a');
assert.equal(destinoEnter(campos, 'a', true), null);
assert.equal(destinoEnter(campos, 'x'), null);

console.log('funcionRenal: OK');
