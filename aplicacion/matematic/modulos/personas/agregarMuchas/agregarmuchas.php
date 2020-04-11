<?php
namespace personas\agregarmuchas;

class Modulo
{
	// No modificar
	public $nombreModulo = 'AgregarMuchas';
	public $rutaModulo = 'personas/agregarMuchas';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = [];

	public function __construct($args)
	{

		/*
		Cargos de la empresa:
		5 sucursales
		1 gerente general
		1 gerente sucrusal por sucursal
		2 encargados por seccion por sucursal
		2 subencargados por seccion por sucursal
		4 operarios calificados por seccion por sucursal
		6 operarios por seccion por sucursal

		Gerencia 11 a 20
		Encargados 21 a 40
		Sub Encargados 41 a 60
		Operarios calificados 61 a 80
		Operarios 81 a 100

		 */
	}
}

?>