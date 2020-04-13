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
		'menuPrincipal' =>
		[
			['link' => '{uri/base}/', 'title' => 'Ir al inicio', 'text' => 'Inicio'],
			['link' => '{uri/base}/tabla', 'title' => 'Prueba de tablas', 'text' => 'Tabla'],
			['link' => '{uri/base}/gestor', 'title' => 'Gestor de secciones', 'text' => 'Conf'],
			['link' => '{uri/base}/personas', 'title' => 'Gestor de personas', 'text' => 'Personas'],
			['link' => '{uri/base}/login', 'title' => 'Iniciar o cerrar sesion', 'text' => 'Login']
		]
	];

	public function __construct($args = [])
	{
		$seccion = \Router::get('seccion') ;
		$this->data['pagina'] = $seccion ? $seccion : 'inicio';
	}
}

?>