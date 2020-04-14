<?php
namespace debug;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Debug';
	public $rutaModulo = 'debug';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args = [])
	{

		if(isset($_SESSION['debuger']))
		{
			$this->data['debugs'] = $_SESSION['debuger'];
		}
	}
}

?>