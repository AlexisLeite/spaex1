<?php
namespace navegacion\menu;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Menu';
	public $rutaModulo = 'navegacion/menu';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data = $args;
	}
}

?>