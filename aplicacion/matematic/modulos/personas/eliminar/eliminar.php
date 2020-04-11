<?php
namespace personas\eliminar;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Eliminar';
	public $rutaModulo = 'personas/eliminar';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	private function delete($inStatement)
	{
		\Comunicador::eliminarPersona($inStatement);
	}

	public function __construct($args)
	{
		if(isset($_POST['delete']))
		{
			$this->delete(implode("','", $_POST['delete']));
		}

		if(\Router::get('persona'))
			$this->delete(\Router::get('persona'));
	}
}

?>