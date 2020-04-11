<?php
namespace common\form\fields\hidden;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Hidden';
	public $rutaModulo = 'common/form/fields/hidden';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data = $args['options'];
	}
}

?>