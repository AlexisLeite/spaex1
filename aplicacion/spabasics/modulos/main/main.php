<?php
namespace main;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Main';
	public $rutaModulo = 'main';
	public $args;

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
	];

	public function __construct($args = [])
	{
		$bienvenidas =
		[
			'Bienvenido usuario',
			'Desarrolle rápido',
			'Desarrolle bien'
		];

		$this->data['bienvenida'] = $bienvenidas[floor(rand(0,sizeof($bienvenidas)-1))];
	}
}

?>