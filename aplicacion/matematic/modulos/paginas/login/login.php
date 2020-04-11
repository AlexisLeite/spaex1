<?php
namespace paginas\login;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Login';
	public $rutaModulo = 'paginas/login';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{
		if(isset($_SESSION['identificador']['sesionActual']))
		{
			$this->data['usuario'] = $_SESSION['identificador']['sesionActual']['nombre'];
		}
		else if(isset($_SESSION['identificador']['redireccionar']))
		{
			$this->data['motivoRedireccion'] = $_SESSION['identificador']['redireccionar']['motivo'];
			$this->data['direccionRedireccion'] = $_SESSION['identificador']['redireccionar']['urlFinal'];
		}

		if(isset($_SESSION['identificador']['errorAutenticacion'])) $this->data['errorAutenticacion'] = $_SESSION['identificador']['errorAutenticacion'];
	}
}

?>