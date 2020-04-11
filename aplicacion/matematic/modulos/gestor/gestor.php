<?php
namespace gestor;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Gestor';
	public $rutaModulo = 'gestor';
	public $args;

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'success' => false,
		'error' => false
	];

	public function __construct($args = [])
	{
		$dirClases = conf('ruta/clases');
		$dirGestor = __DIR__ . '/misc';
		$dirModulos = __DIR__ . '/../modulos';

		if(isset($_POST['CrearClase']))
		{
			$nombreClase = $_POST['NombreClase'];
			$nombreDirectorioClase = strtolower($nombreClase);
			if(file_exists("$dirClases/$nombreDirectorioClase"))
				$this->data['error']= 'Clase ya existente';
			else
			{
				mkdir("$dirClases/$nombreDirectorioClase");
				file_put_contents("$dirClases/$nombreDirectorioClase/$nombreDirectorioClase.php", str_replace('NombreClase', $nombreClase, file_get_contents("$dirGestor/claseMaqueta.php")));
				$this->data['success'] = 'Clase creada con exito';
			}
		}
		else if(isset($_POST['CrearModulo']))
		{
			$rutaModulo = $_POST['RutaModulo'];
			if(!preg_match('/\/?(\w[\/\w]+\w)\/?/', $rutaModulo, $rutaModulo)) 
				$this->data['error'] = 'Debe introducir un nombre de modulo valido';
			else if(($this->data['error'] = \Maquetador::crearModulo([
				'ruta' => $rutaModulo[1],
				'modulo' => isset($_POST['ModuloCrearModulo']) ? true : false,
				'maqueta' => isset($_POST['ModuloCrearMaqueta']) ? true : false,
				'estilos' => isset($_POST['ModuloCrearEstilos']) ? true : false,
				'controlador' => isset($_POST['ModuloCrearControlador']) ? true : false,
			])) === true)
			{
				$this->data['success'] = 'Modulo creado con exito';
				$this->data['error'] = '';
			}
		}
	}
}

?>