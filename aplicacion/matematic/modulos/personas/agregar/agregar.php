<?php
namespace personas\agregar;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Agregar';
	public $rutaModulo = 'personas/agregar';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'form' =>
		[
			'action' => '{uri/base}/personas/agregar',
			'fields' => 
			[
				['label'=>'C.I.','name'=>'Ci','type'=>'text', 'placeholder'=>'1.111.111-1'],
				['label'=>'Nombre','name'=>'Nombre','type'=>'text', 'placeholder'=>'Juan'],
				['label'=>'Apellido','name'=>'Apellido','type'=>'text', 'placeholder'=>'Perez'],
				['label'=>'Direccion','name'=>'Direccion','type'=>'text', 'placeholder'=>'18 de julio 65545'],
				['label'=>'Telefono','name'=>'Telefono','type'=>'text'],
				['label'=>'Telefono','name' => 'AgregarPersona','type'=>'hidden']
			],
			'submitValue' => 'Agregar'
		]
	];

	public function __construct($args)
	{
		if(isset($_POST['AgregarPersona']))
		{
			$resultado = \Comunicador::agregarPersona(
			[
				'nombre' => ucfirst(strtolower(htmlentities($_POST['Nombre']))),
				'ci' => ucfirst(strtolower(htmlentities($_POST['Ci']))),
				'apellido' => ucfirst(strtolower(htmlentities($_POST['Apellido']))),
				'direccion' => ucfirst(strtolower(htmlentities($_POST['Direccion']))),
				'telefono' => ucfirst(strtolower(htmlentities($_POST['Telefono'])))
			]);
			$this->data['resultado'] = $resultado === true ? 'Usuario agregado exitosamente' : $resultado;
		}
	}
}

?>