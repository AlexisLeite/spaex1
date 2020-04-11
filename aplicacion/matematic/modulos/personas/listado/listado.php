<?php
namespace personas\listado;

define('ObjetoListado','objetoListado');

class Modulo
{
	// No modificar
	public $nombreModulo = 'Listado';
	public $rutaModulo = 'personas/listado';

	// Array de datos que utilizara el sistema para procesar la maqueta, debe ser escrito durante la construccion del modulo
	public $data = 
	[
		'tablaPersonas' =>
		[
			[
				'role' => 'thead',
				'rows' =>
				[
					[
						'nodes'=>
						[
							[
								'th' => true,
								'text' => '-'
							],
							[
								'th' => true,
								'text' => 'Modificar'
							],
							[
								'th' => true,
								'text' => 'Eliminar'
							]
						]
					]
				]
			],
			[
				'role' => 'tbody',
				'rows' =>
				[
				]
			]
		]
	];

	private $default =
	[
		'orden' => 'ci',
		'ordenAsc' => 'asc',
		'limite' => 30,
		'pagina' => 0
	];

	private $columnas =
	[
		'ci', 'nombre', 'apellido', 'direccion', 'telefono', 'categoria'
	];

	public function __construct($args)
	{
		// Establecer la sesion
		\Basic::sesion();
		if(!isset($_SESSION[ObjetoListado]))
			$_SESSION[ObjetoListado] = [];

		// Resultados por pagina
		$limitePorPagina = 10; 


		// Si se presiona cancelar la busqueda
		if(\Router::get('cancelarBusqueda') == 'cancelarBusqueda')
		{
			unset($_SESSION[ObjetoListado]['busqueda']);
			$this->data['busqueda'] = false;
		}


		// Desarrollar la busqueda
		if(isset($_POST['Busqueda']))
		{
			$_SESSION[ObjetoListado]['busqueda'] = [];
			$_SESSION[ObjetoListado]['busqueda']['query'] = htmlentities($_POST['Busqueda']);

			// Se almacenan los datos en memoria para no repetir la misma busqueda varias veces.
			$_SESSION[ObjetoListado]['busqueda']['resultados'] = \Comunicador::buscarPersonas($_SESSION[ObjetoListado]['busqueda']['query']);

			for($i=0; $i<sizeof($_SESSION[ObjetoListado]['busqueda']['resultados']); $i++)
				foreach($_SESSION[ObjetoListado]['busqueda']['resultados'][$i] as $propiedad => $valor)
					$_SESSION[ObjetoListado]['busqueda']['resultados'][$i]["{$propiedad}Highlighted"] = preg_replace("/({$_SESSION[ObjetoListado]['busqueda']['query']})/i", "<span class='highlighted'>\\1</span>", $_SESSION[ObjetoListado]['busqueda']['resultados'][$i][$propiedad]);
		}

		// Si hay una busqueda en curso, se debe mostrar al usuario
		if(isset($_SESSION[ObjetoListado]['busqueda']))
			$this->data['busqueda'] = $_SESSION[ObjetoListado]['busqueda']['query'];

		// Establecer pagina actual
		$pagina = \Router::get('nroPagina') 
					? \Router::get('nroPagina') 
					: (isset($_POST['getPage'])
							? $_POST['getPage']
							: 1);


		// Establecer columna por la cual ordenar
		$orden = 'ci';
		$orden = \Router::get('ordenar') 
					? \Router::get('columna') 
					:	(isset($_SESSION[ObjetoListado]['orden']) 
								? $_SESSION[ObjetoListado]['orden'] 
								: $orden);


		// Establecer orden
		$ascDesc = 'asc';
		$ascDesc = \Router::get('ascDesc')
					? \Router::get('ascDesc')
					: (isset($_SESSION[ObjetoListado]['ascDesc'])
								? $_SESSION[ObjetoListado]['ascDesc']
								:$ascDesc);


		// Establecer la cabecera de la tabla de listado
		foreach(array_reverse($this->columnas) as $col)
		{
			$ascDescCol = $orden == $col ? ($ascDesc == 'asc' ? 'desc' : 'asc') : 'asc';
			array_splice($this->data['tablaPersonas'][0]['rows'][0]['nodes'], 1, 0,
				[[
					'mode' => 'href',
					'href' => "{baseUri}/personas/listado/ordenar/$col/$ascDescCol",
					'title' => "Ordenar resultados por $col",
					'th' => true, 
					'text' => ucfirst($col)
				]]);
		}


		// Establecer enlaces de orden de columnas
		foreach($this->columnas as $col)
			$this->data["{$col}Asc"] = 'asc';
		$this->data["{$orden}Asc"] = $ascDesc == 'asc' ? 'desc' : 'asc';
	
		// Se guardan los datos en sesion
		$_SESSION[ObjetoListado]['orden'] = $orden;
		$_SESSION[ObjetoListado]['ascDesc'] = $ascDesc;


		// Generar resultados para mostrar
		if(isset($_SESSION[ObjetoListado]['busqueda']['resultados']))
		{
			// Se crea objeto de estadisticas para la busqueda
			$this->data['resultadosTotal'] = count($_SESSION[ObjetoListado]['busqueda']['resultados']);
			$this->data['paginasTotal'] = ceil(count($_SESSION[ObjetoListado]['busqueda']['resultados'])/$limitePorPagina);

			if(isset($_SESSION[ObjetoListado]['busqueda']['ordenAnterior'])
				&& $_SESSION[ObjetoListado]['busqueda']['ordenAnterior'] != $orden)
			{
							usort($_SESSION[ObjetoListado]['busqueda']['resultados'], function($a,$b) use ($orden, $ascDesc)
							{
								return strcasecmp($a[$orden],$b[$orden]) * ($ascDesc == 'asc' ? 1 : -1);
							});
				$_SESSION[ObjetoListado]['busqueda']['ordenAnterior'] = $orden;
			}
			else
				$_SESSION[ObjetoListado]['busqueda']['ordenAnterior'] = '';


			// Se traen los resultados desde el array de resultados de busqueda
			$personas = array_slice($_SESSION[ObjetoListado]['busqueda']['resultados'],($pagina-1) * $limitePorPagina, $limitePorPagina);
		}
		else
		{
			// Objeto de estadisticas:
			// 'registros': Cantidad de registros
			// 'ultimaPagina': Cantidad de elementos que muestra la ultima pagina
			// 'paginas': Cantidad de paginas (se debe sumar 1 si ultimaPagina != 0)
			$estadisticas = \Comunicador::getEstadisticas(['limite' => $limitePorPagina]);
			$this->data['resultadosTotal'] = $estadisticas['registros'];
			$this->data['paginasTotal'] = ($estadisticas['ultimaPagina'] == 0) ? $estadisticas['paginas'] : $estadisticas['paginas'] + 1;

			// Se traen los resultados desde la base de datos
			$personas = \Comunicador::getPersonas(array_merge($_SESSION[ObjetoListado],['pagina' => $pagina - 1, 'limite' => $limitePorPagina]));
		}

		// Establecer pagina anterior, actual y siguiente
		$this->data['paginaActual'] = $pagina;
		$this->data['paginaAnterior'] = $pagina > 1 ? $pagina-1 : 1;
		$this->data['paginaSiguiente'] = $pagina + 1 > $this->data['paginasTotal'] ? $pagina : $pagina+1;


		// Agregar informacion a la tabla
		foreach($personas as $persona)
		{
			$nuevoArray = 
			[
				'-',
				[
					'mode' => 'href',
					'href' => "{baseUri}/personas/modificar/{$persona['ci']}",
					'text' => 'Modificar',
					'title' => "Modificar {$persona['nombre']} {$persona['apellido']}."
				],
				[
					'mode' => 'href',
					'href' => "{baseUri}/personas/eliminar/{$persona['ci']}",
					'text' => 'Eliminar',
					'title' => "Eliminar {$persona['nombre']} {$persona['apellido']}."
				]
			];

			foreach(array_reverse($this->columnas) as $col)
				array_splice($nuevoArray,1,0,isset($persona["{$col}Highlighted"]) ? $persona["{$col}Highlighted"] : $persona[$col]);

			$this->data['tablaPersonas'][1]['rows'][] = $nuevoArray;
		}
	}
}

?>