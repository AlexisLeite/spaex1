<?php
namespace paginas\tabla;

class Modulo
{
	// No modificar
	public $nombreModulo = 'Tabla';
	public $rutaModulo = 'paginas/tabla';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'tabla' => 
		[
			'id' => 'Table rox',
			'class' => 'TableClass',
			[ // thead
				'role' => 'thead',
				'id' => 'Role rox',
				'class' => 'RoleClass',
				'rows' =>
				[
					[ // Row 1 de thead
						'id' => 'RowRox',
						'class' => 'RowClass',
						'nodes' =>
						[
							[ // Cell 1 de Row 1 de thead
								'mode' => 'value',
								'class' => 'NodeClass',
								'text' => 'Nombres',
								'th' => true,
								'colspan' => 1
							],
							[ // Cell 1 de Row 1 de thead
								'mode' => 'href',
								'href' => '{uri/base}/tabla',
								'title' => 'Tablas',
								'text' => 'Tablas',
								'class' => 'NodeClass',
								'th' => true,
								'colspan' => 2
							]
						]
					],
				]
			],
			[ // tbody
				'role' => 'tbody',
				'rows' =>
				[
					['Alito','Pepes'],
				]
			]
		]
	];

	public function __construct($args)
	{
	}
}

?>