<?php
namespace navegacion\boton;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Boton';
	public $rutaModulo = 'navegacion/boton';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data = $args['options'];
	}
}

?>