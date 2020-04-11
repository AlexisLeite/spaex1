<?php
namespace common\form;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Form';
	public $rutaModulo = 'common/form';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data =
		[
			'fields' => $args['fields'],
			'method' => isset($args['method']) ? $args['method'] : 'get',
			'action' => isset($args['action']) ? $args['action'] : '',
			'submitValue' => isset($args['submitvalue']) ? $args['submitvalue'] : 'Enviar',
		];
	}
}

?>