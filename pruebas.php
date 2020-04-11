<?php
$a = 
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
						'value' => 'Nombres',
						'th' => true,
						'colspan' => 1
					],
					[ // Cell 1 de Row 1 de thead
						'mode' => 'value',
						'class' => 'NodeClass',
						'value' => 'Apellidos',
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
			['Alito','Brasil','Pepes'],
		]
	]
];

$b =
[
	[ // tbody
		'role' => 'tbody',
		'rows' =>
		[
			['Alito','Brasil','Pepes'],
		]
	]
];
print_r(Basic::compararArrays($a,$b));
exit;
?>