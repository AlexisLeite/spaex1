<?php
namespace paginas\contacto;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Contacto';
	public $rutaModulo = 'paginas/contacto';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'datos' =>
		[
			'Telefono' => '2202 2202',
			'Email' => 'contacto@bestfotos.com.uy',
			'Direccion' => 'L. Alberto de Herrera 2202'
		]
	];

	public function __construct($args)
	{
	}
}

?>