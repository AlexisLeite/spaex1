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
			['link' => '{uri/base}/inicio', 'title' => 'Ir al inicio', 'text' => 'Inicio'],
			['link' => '{uri/base}/galeria', 'title' => 'Galeria de fotos', 'text' => 'Galeria'],
			['link' => '{uri/base}/contacto', 'title' => 'Medios de contacto', 'text' => 'Contacto']
		]
	];

	public function __construct($args = [])
	{
		$pagina = \Router::get('pagina');
		$this->data['pagina'] = $pagina ? $pagina : 'inicio';

		\Conf::set('tituloPagina','Framework SPA');
		\Conf::set('sloganPagina','Super sencillo');
		\Conf::set('keywordsPagina','framework, web design, development, javascript, php, css');
		\Conf::set('descripcionPagina','Este framework pretende facilitar el desarrollo de aplicaciones SPA accesibles sin javascript.');
	}
}

?>