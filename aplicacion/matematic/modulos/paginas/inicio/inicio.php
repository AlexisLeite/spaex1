<?php
namespace paginas\inicio;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Inicio';
	public $rutaModulo = 'paginas/inicio';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
	}
}

?>