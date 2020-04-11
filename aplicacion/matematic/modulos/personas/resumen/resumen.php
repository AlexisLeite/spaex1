<?php
namespace personas\resumen;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Resumen';
	public $rutaModulo = 'personas/resumen';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
	}
}

?>