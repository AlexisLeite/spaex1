<?php
namespace paginas\personas;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Personas';
	public $rutaModulo = 'paginas/personas';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'acciones' =>
		[
			['href' => '{baseUri}/personas/agregar', 'title' => 'Agregar personas al sistema', 'texto' => 'Agregar']
		]
	];

	public function __construct($args)
	{
		$accion =\Router::get('accion');
		$this->data['accion'] = $accion ? $accion : 'resumen';
	}
}

?>