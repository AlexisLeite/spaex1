<?php

define('DIR_FIJO', 1);
define('DIR_VARIABLE', 2);

// Api publica de Router
// run(ruta): Metodo que ejecuta los procesos necesarios para el correcto funcionamiento del router
// redirect(ruta): Metodo que invoca al metodo run con la ruta establecida, es en realidad un alias
// get(indice): Devuelve si existe un indie de la ruta desglosada
// current(): Devuelve la ruta actual en la que se encuentra el router
// 
// $arrayRuta: es un array que contiene en la clave 0 el dominio actual y en cada siguiente elemento un directorio mas de la ruta.
// $rutaActual: es un array asociativo que contiene el resultado del procesamiento de las rutas descritas en el fichero rutas.json
// $rutasProcesadas: es un array que contiene el historial de rutas que ha procesado el router en esta oportunidad
class Router
{
	//Propiedades de configuracion
	// ******************************************************
	private static $dominio = '';
	private static $variableGetQueContieneRuta = 'ruta';
	
	// Propiedades de api publica
	// ******************************************************
	public static $arrayRuta = [];
	public static $rutaActual = ['seccion' => 'inicio'];
	public static $rutaPredeterminada = ['seccion' => 'inicio'];
	public static $rutasProcesadas = [];

	// Metodos de api publica
	// ******************************************************
	// Metodo que devuelve el indice requerido de la ruta desglosada actual
	public static function get($indice)
	{
		if(isset(Self::$rutaActual[$indice]))
			return Self::$rutaActual[$indice];
		return null;
	}

	// Funcion que debe llamarse para inicializar el router
	public static function run($ruta = null)
	{
		Self::$ficheroRutas = conf('ruta/conf') . '/rutas.json'	;
		Self::$dominio = conf('uri/base');
		if(!$ruta && $ruta !== '') $ruta = $_GET[Self::$variableGetQueContieneRuta];
		array_push(Self::$rutasProcesadas,$ruta);
		Self::creararrayRuta();
		Self::crearRutaActual();
	}

	public static function redirect($ruta)
	{
		Self::run($ruta);
	}

	public static function current()
	{
		return end(Self::$rutasProcesadas);
	}

	//Propiedades internas
	// ******************************************************
	private static $ficheroRutas;
	private static $ficheroCargado = null;

	// Metodos privados
	// ******************************************************

	// Metodo encargado de crear un array numerico en el cual esten todos los niveles estan representados
	private static function creararrayRuta()
	{
		Self::$arrayRuta = explode('/',end(Self::$rutasProcesadas));
		array_unshift(Self::$arrayRuta, Self::$dominio);

		return Self::$arrayRuta;
	}

	private static function cargarArchivoRutas()
	{
		if(!Self::$ficheroCargado) 
			Self::$ficheroCargado = Jsoner::load(Self::$ficheroRutas);
		return Self::$ficheroCargado;
	}

	// Metodo encargado de crear reconocer los parametros de la ruta actual.
	private static function crearRutaActual()
	{
		// Se carga el fichero de rutas
		$rutas = Self::cargarArchivoRutas();
		$rutasCoincidentes = [];

		// Ante la posibilidad de que no se aplique ninguna ruta, se establece la predeterminada con anterioridad.
		Self::$rutaActual = Self::$rutaPredeterminada;

		// Por cada ruta existente, se crea una clase Ruta y se corrobora su cumplimiento. Si coincide se agrega al stack de rutas 'utiles'
		foreach($rutas as $ruta)
		{
			$nuevaRuta = new Ruta($ruta);
			if($nuevaRuta->match(end(Self::$rutasProcesadas)))
				array_push($rutasCoincidentes,$nuevaRuta);
		}

		$nivelActual = 0;
		if(!sizeof($rutasCoincidentes))
			return;
		if(sizeof($rutasCoincidentes) == 1)
		{
			Self::$rutaActual = $rutasCoincidentes[0]->matches;
		}
		else
			while(true)
			{
				$rutasConNivelesFijos = [];

				// Seleccionar aquellas que tengan niveles fijos
				foreach($rutasCoincidentes as $rutaCoincidente)
				{
					if($rutaCoincidente->niveles[$nivelActual]->esFijo())
						array_push($rutasConNivelesFijos,$rutaCoincidente);
				}
				if(sizeof($rutasConNivelesFijos) > 0)
					$rutasCoincidentes = $rutasConNivelesFijos;
				
				// Queda una sola ruta?
				if(sizeof($rutasCoincidentes) == 1)
				{
					Self::$rutaActual = $rutasCoincidentes[0]->matches;
					break;
				}

				// Hay mas niveles en alguna?
				$rutasDescartadasPorNivel = [];
				for($i=sizeof($rutasCoincidentes) -1; $i>=0; $i--)
					// Si la ruta no tiene un siguiente nivel
					if($rutasCoincidentes[$i]->length <= $nivelActual+1)
						array_push($rutasDescartadasPorNivel, array_splice($rutasCoincidentes, $i, 1)[0]);

				// Queda una sola ruta?
				if(sizeof($rutasCoincidentes) == 1)
				{
					Self::$rutaActual = $rutasCoincidentes[0]->matches;
					break;
				}

				// No hay ninguna ruta que tenga mas niveles
				else if(sizeof($rutasCoincidentes) == 0)
				{
					Self::$rutaActual = $rutasDescartadasPorNivel[0]->matches;
					break;
				}

				$nivelActual++;
				if($nivelActual > 50)
				{
					throw new Exception('Algo anda mal al crear ruta');
				}
			}	
	}
}


// Clase de uso interno
class NivelRuta
{
	public $expresion;
	public $tipo;

	public function __construct($expresion,$tipo = DIR_FIJO)
	{
		$this->expresion = $expresion;
		$this->tipo = $tipo;
	}

	public function esFijo()
	{
		return $this->tipo & DIR_FIJO;
	}
}

// Otra clase de uso interno
class Ruta
{
	public $ruta;
	public $niveles = [];
	public $matches = false;
	public $length = 0;

	private $tiposVariablesRutas = 
	[
		'ALPHA' => '[a-zA-ZñÑ]+',
		'NUM' => '\\d+',
		'ALPHANUM' => '\\w+',
		'ALL' => '[^\\/]+',
		'ETC' => '.*'
	];

	public function match($cadena)
	{
		if(preg_match($this->ruta, $cadena, $res))
		{
			$this->matches = [];
			foreach($res as $k => $v)
				if(is_string($k))
					$this->matches[$k] = $v; 
			return true;
		}
		return false;
	}

	public function __construct($ruta)
	{
		// Una variable que se utilizara para generar la ruta procesada
		$rutaProcesada = '';

		// Se separan los directorios
		$directoriosRuta = explode('/', $ruta);

		// Por cada directorio de la ruta, se configura su correspondiente en expresion regular
		foreach($directoriosRuta as $directorio)
		{
			// Exiten 3 posibilidades por cada directorio: 
			//  - O bien es una cadena fija, carace de : que defina el tipo
			//  - O bien es una cadena fija con nombre, nombre:cadena
			//  - O bien es una variable con nombre, nombre:tipoVar
		  
			$directorioDesglosado = explode(':', $directorio);
			$directorioProcesado = '';

			// Si es una cadena fija
			if(sizeof($directorioDesglosado) == 1)
				$directorioProcesado = new NivelRuta("\/{$directorioDesglosado[0]}",DIR_FIJO);

			// Si es una variable con nombre
			else if(array_key_exists($directorioDesglosado[1], $this->tiposVariablesRutas))
				$directorioProcesado = new NivelRuta(sprintf('\/(?<%s>%s)',$directorioDesglosado[0],$this->tiposVariablesRutas[$directorioDesglosado[1]]),DIR_VARIABLE);

			// Si es una cadena fija con nombre
			else
				$directorioProcesado = new NivelRuta(sprintf('\/(?<%s>%s)',$directorioDesglosado[0],$directorioDesglosado[1]),DIR_FIJO);

			$rutaProcesada .= $directorioProcesado->expresion;
			array_push($this->niveles, $directorioProcesado);
			$this->length ++ ;
		}

		// Se establece la ruta como expresion regular
		$this->ruta = '/^\/?' . substr($rutaProcesada, 2) . '\/?$/';

		// En este punto tenemos:
		// ruta: Es una expresion regular que sirve para contrastar con una ruta deseada
		// niveles: Un array que contiene los distintos niveles en forma de micro expresiones regulares y con indicacion de si son niveles fijos o dinamicos. Esta distincion es necesaria para dar prioridad a aquellas rutas que tengan niveles fijos.
	}
}

?>