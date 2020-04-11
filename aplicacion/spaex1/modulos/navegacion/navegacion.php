<?php
namespace navegacion;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Navegacion';
	public $rutaModulo = 'navegacion';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data['menu'] = $args['options'];
	}
}

?>