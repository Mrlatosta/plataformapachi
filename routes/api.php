<?php

// Las rutas /api/* viven en routes/web.php dentro del grupo 'auth', porque
// el frontend las consume con la sesion del usuario. Aqui no deben declararse
// rutas publicas que expongan datos de pacientes.
