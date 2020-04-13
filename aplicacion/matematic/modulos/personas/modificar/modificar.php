<?php
namespace personas\modificar;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Modificar';
	public $rutaModulo = 'personas/modificar';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'form' =>
		[
			'action' => '{uri/base}/personas/modificar',
			'fields' => 
			[
				['label'=>'C.I.','name'=>'Ci','type'=>'text/readonly'],
				['label'=>'Nombre','name'=>'Nombre','type'=>'text'],
				['label'=>'Apellido','name'=>'Apellido','type'=>'text'],
				['label'=>'Direccion','name'=>'Direccion','type'=>'text'],
				['label'=>'Telefono','name'=>'Telefono','type'=>'text'],
				['label'=>'Telefono','name' => 'ModificarPersona','type'=>'hidden'],
			],
			'submitValue' => 'Modificar'
		]
	];

	public function __construct($args)
	{
		if(isset($_POST['ModificarPersona']))
		{
			\Comunicador::modificarPersona([
				'ci' => htmlentities($_POST['Ci']),
				'nombre' => ucfirst(strtolower(htmlentities($_POST['Nombre']))),
				'apellido' => ucfirst(strtolower(htmlentities($_POST['Apellido']))),
				'direccion' => ucfirst(strtolower(htmlentities($_POST['Direccion']))),
				'telefono' => ucfirst(strtolower(htmlentities($_POST['Telefono']))),
			]);
		}
		if(\Router::get('persona'))
		{
			$persona = \Comunicador::getPersona(\Router::get('persona'));
			// deb('Personas',$persona,'Datos de persona a modificar');
			$this->data['form']['fields'][0]['value'] = $persona['ci'];
			$this->data['form']['fields'][1]['value'] = $persona['nombre'];
			$this->data['form']['fields'][2]['value'] = $persona['apellido'];
			$this->data['form']['fields'][3]['value'] = $persona['direccion'];
			$this->data['form']['fields'][4]['value'] = $persona['telefono'];
		}
	}
}

?>