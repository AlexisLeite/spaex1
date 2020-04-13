<?php
namespace paginas\galeria;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Galeria';
	public $rutaModulo = 'paginas/galeria';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		$this->data['fotos'] = array_values(array_map(function($el)
		{
			return conf('uri/base') . "/aplicacion/spaex1/modulos/paginas/galeria/pictures/$el";
		},array_filter(scandir(__DIR__ . '/pictures'),function($el)
		{
			return !in_array($el, ['..','.']);
		})));

		if(\Router::get('imagen') !== null)
			$this->data['imagenGrande'] = $this->data['fotos'][\Router::get('imagen')];
	}
}

?>