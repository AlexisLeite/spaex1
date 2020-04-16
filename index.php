<?php
require('configure.php');


// El plantar la semilla no deberia modificar ninguna variable de entorno, lo mejor seria que el maquetador trabaje y luego se termine el script
if(isset($_GET['activarSpa'])) Maquetador::run();

// Se inicializan los componentes necesarios
Router::run();
Identificador::run();
deb('Router', Router::$rutaActual,'Ruta actual');
deb('Router', Router::$rutasProcesadas,'Rutas procesadas');
deb('Identificador', $_SESSION['identificador']);
if(isset($_SESSION['comunicador']))
{
	deb('Comunicador', $_SESSION['comunicador']['llamado'],'Llamado');
	deb('Comunicador', $_SESSION['comunicador']['respuesta'],'Respuesta');
	unset($_SESSION['comunicador']);
}

// Variable que determina si la seccion de pruebas se muestra siempre o solo cuando se realizan cambios
$keepTesting = false;
// Archivo incluido solamente para poder imprimir primero que nada las pruebas que se realicen y solamente cuando se le realicen cambios
$ptime = file_exists(__DIR__ . '/ptime.txt') ? (int) file_get_contents(__DIR__ . '/ptime.txt') : 0;
if(filemtime('pruebas.php') > $ptime || $keepTesting)
{
	file_put_contents(__DIR__ . '/ptime.txt', filemtime('pruebas.php'));
	include('pruebas.php');
}

Maquetador::run();
?>