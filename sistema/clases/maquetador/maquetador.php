<?php
require_once(conf('ruta/phpquery'));

class Maquetador
{
	private static $interpretes;

	// Este metodo devolvera un array conteniendo el nombre de clase de todos los modulos utilizados en la estructura
	private static function determinarModulos($estructura)
	{
		$estructura = (array) $estructura;
		$modulos = [$estructura['src']];
		foreach($estructura['hijos'] as $hijo)
			$modulos = array_merge($modulos, Self::determinarModulos($hijo));
		return $modulos;
	}

	// Este metodo sera el encargado de imprimir la informacion spa, con la particularidad de que debera recordar que maquetas se entregaron en consultas anteriores y enviar solamente aquellas maquetas de las que el usuario carezca.
	// Para ello se valera de una estructura almacenada en $_SESSION['maquetador']['sentStructures']
	private static function imprimirSpa($estructura)
	{
		// Se procedera a identificar que maquetas son necesarias para mostrar correctamente la estructura actual
		$modulosEstructuraActual = array_unique(Self::determinarModulos($estructura));

		$modulosFaltantes = [];
		// Si hay una estructura previa, se establece que elementos falta enviar al cliente
		if(isset($_SESSION['maquetador']['sentStructures']))
		{
			foreach($modulosEstructuraActual as $moduloFaltante)
				if(!in_array($moduloFaltante,$_SESSION['maquetador']['sentStructures']))
				{
					$_SESSION['maquetador']['sentStructures'][] = $modulosFaltantes[] = $moduloFaltante;
				}
		}
		else
			$modulosFaltantes = $_SESSION['maquetador']['sentStructures'] = $modulosEstructuraActual;

		// Por cada modulo que falte enviar, se construye una estructura del tipo [nombre, maqueta, controlador]
		$modulosFaltantes = array_map(function($el)
		{
			preg_match('/(?:.*\/)?([^\/]+)$/', $el, $nombreModulo);
			$nombreModulo = $nombreModulo[1];
			$maqueta = file_exists(conf('ruta/modulos') . "/$el/$nombreModulo.html") ? file_get_contents(conf('ruta/modulos') . "/$el/$nombreModulo.html") : '';
			$controlador = file_exists(conf('ruta/modulos') . "/$el/$nombreModulo.js") ? file_get_contents(conf('ruta/modulos') . "/$el/$nombreModulo.js") : '';
			return ['clase' => $el, 'maqueta' => $maqueta, 'controlador' => $controlador];
		}, $modulosFaltantes);

		// La estructura enviada al cliente sera del tipo 
		// [estructura => Modificaciones que deben hacerse al arbol para que se actualice con el arbol actual,
		// modulos => array de estructuras [nombre,maqueta,controlador]]
		$spa = ['estructura' => $estructura];
		if(sizeof($modulosFaltantes)) $spa['modulos'] = $modulosFaltantes;
		echo json_encode($spa);
	}

	// Cuando se llama a este metodo, se crea el arbol de modulos, se evalua la necesidad de enviar informacion en html o json al cliente y se procede de acuerdo a ello.
	public static function run()
	{
		// Activar spa no debe invocar ningun metodo del maquetador mas que aquellos que impriman la informacion del documento recientemente enviado al cliente
		if(isset($_GET['activarSpa']))
		{
			Self::imprimirSpa(Modulo::compare(['clase'=>[],'data'=>[],'hijos'=>[]],$_SESSION['maquetador']['storedTree']));
			exit;
		}

		Basic::sesion();
		// Se prepara la maqueta inicial
		$maquetaRaiz = file_get_contents(sprintf("%s/%s",conf('ruta/maquetas'),conf('maquetaRaiz')));
		$maqueta = phpQuery::newDocument($maquetaRaiz);

		// Se ejecuta el modulo inicial
		if(Router::get('gestorFramework') && conf('habilitarGestor'))
			$moduloRaiz = new Modulo('gestor');
		else
			$moduloRaiz = new Modulo(conf('moduloRaiz'));
		$moduloRaiz->appendTo('body');

		if(isset($_GET['spa']) && isset($_SESSION['maquetador']['storedTree']))
		{ 
			$segundo = 1000000;
			usleep($segundo * 0);
			// Si hay que trabajar sobre un sistema spa, se imprime la informacion
			// 
			// Se guarda el arbol actual
			$dataTreeActual = $moduloRaiz->store();
			$diferencias = Modulo::compare($_SESSION['maquetador']['storedTree'],$dataTreeActual);
			$_SESSION['maquetador']['storedTree'] = $dataTreeActual;
			Self::imprimirSpa($diferencias);
		}
		else
		{ // Si no hay que trabajar sobre un modulo spa, se imprime el resultado
			// Se eliminan los notag y los tag modulo
			$_SESSION['maquetador'] = [];
			$maqueta = Basic::parseVar((string)$maqueta);
			echo preg_replace('/< *modulo.*?>|< *\/modulo.*?>|< *nt.*?>|< *\/nt.*?>/', '', $maqueta);
			$_SESSION['maquetador']['storedTree'] = $moduloRaiz->store();
		}

		// De cualquier manera se guardan los resultados para siguientes consultas

	}

	// Es un metodo destinado a obtener todos los interpretes disponibles
	public static function getInterpretes()
	{
		if(Self::$interpretes == null) 
			Self::$interpretes = array_map(function($el)
			{
				return new Interprete(__DIR__ . "/interpretes/$el");
			}, array_filter(scandir(__DIR__ . '/interpretes'),function($el)
			{
				if(in_array($el, ['.','..']))
					return false;
				return true;
			}));
		return Self::$interpretes;
	}

	// Metodo encargado de crear modulos de acuerdo a los requerimientos de los mismos,
	// Acepta un array asociativo:
	// 'ruta': La ruta sobre la que se creara el modulo
	// 'maqueta': true o false, define si se crea maqueta.html
	// 'modulo': true o false, define si se crea main.php
	// 'estilos': true o false, define si se crea estilos.css
	// 'controlador': true o false, define si se crea controlador.js
	public static function crearModulo($parametros)
	{
		$dirModulos = conf('ruta/modulos');
		$nombreModulo = ucfirst(@end(explode('/',$parametros['ruta'])));
		// Si ya existe algun archivo relacionado al modulo que se desea crear, no se continuara
		if(file_exists("$dirModulos/{$parametros['ruta']}/$nombreModulo.php") 
			|| file_exists("$dirModulos/{$parametros['ruta']}/$nombreModulo.html")
			|| file_exists("$dirModulos/{$parametros['ruta']}/$nombreModulo.css")
			|| file_exists("$dirModulos/{$parametros['ruta']}/$nombreModulo.js")
		)
			return 'Modulo ya existente';

		// Si no se establece que se desea crear, no se continuara
		else if(!$parametros['maqueta']
			&& !$parametros['modulo']
			&& !$parametros['estilos']
			&& !$parametros['controlador']
		) 
			return 'Debe establecer al menos un elemento para la creacion (Modulo, maqueta, estilos o controlador).';

		$nameSpace = str_replace('/','\\',strtolower($parametros['ruta']));
		$rutaDirectorioModulo = Basic::makeDir(conf('ruta/modulos'),$parametros['ruta']);
		$dirGestor = __DIR__ . '/misc';
		
		if($parametros['modulo'])
		{
			file_put_contents("$rutaDirectorioModulo/" . strtolower($nombreModulo) . ".php", str_replace(['{Ruta}','{Namespace}','{Nombre}'], [$parametros['ruta'],$nameSpace,$nombreModulo], file_get_contents("$dirGestor/claseModulo.php")));
		}
		if($parametros['controlador'])
		{
			file_put_contents("$rutaDirectorioModulo/" . strtolower($nombreModulo) . ".js", file_get_contents("$dirGestor/controlador.js"));
		}
		if($parametros['maqueta'])
		{
			file_put_contents("$rutaDirectorioModulo/" . strtolower($nombreModulo) . ".html", file_get_contents("$dirGestor/maqueta.html"));
		}
		if($parametros['estilos'])
		{
			file_put_contents("$rutaDirectorioModulo/" . strtolower($nombreModulo) . ".css", file_get_contents("$dirGestor/estilos.css"));
		}

		file_put_contents("$rutaDirectorioModulo/" . strtolower($nombreModulo) . ".md", '');

		return true;
	}
}

// La clase interprete es una representacion de los interpretes contenidos dentro del directorio ./interpretes. Ofrece el nombre del mismo y el metodo parse, mediante el cual se ejecuta.
class Interprete
{
	private $interprete;
	public $nombre;

	public function __construct($ruta)
	{
		preg_match('/.*\d+(\w+)\.php/',$ruta,$nombreInterprete);
		$this->nombre = $nombreInterprete[1];
		$nombreInterprete = "\\Interpretes\\Interprete" . ucfirst(strtolower($nombreInterprete[1]));
		require_once($ruta);
		$this->interprete = $nombreInterprete;
	}

	// Es un atajo hacia la funcion de parse y evita que el publico entre en contacto directo con el interprete
	public function parse($valor,&$elemento,$data)
	{
		$this->interprete::parse($valor,$elemento,$data);
	}
}

?>