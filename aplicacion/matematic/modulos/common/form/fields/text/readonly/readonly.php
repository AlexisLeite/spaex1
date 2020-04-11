<?php
namespace common\form\fields\text\readonly;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Disabled';
	public $rutaModulo = 'common/form/fields/text/readonly';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data = $args['options'];
	}
}

?>