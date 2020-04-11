<?php
// La clase modulo sera utilizada por el maquetador para tener a mano la informacion necesaria acerca de los modulos que deba manipular
class Modulo
{
	public static $counter = 0;
	
	private $data = [];
	private $interpretes = null;
	private $hijos = [];
	private $parent;
	private $maqueta;
	private $modulo;
	private $controlador;

	// Se usa principalmente para permitir el acceso a metodos de posicionamiento de la maqueta
	public function __call($metodo, $argumentos)
	{
		switch($metodo)
		{
			case 'append': case 'appendTo': case 'after': case 'insertAfter':
			case 'before': case 'insertBefore':
				$this->maqueta->$metodo($argumentos[0]);
				break;
		}
	}

	// Ruta base del modulo, donde se encuentra el codigo fuente
	// $argumentos pasados por el sistema o por el modulo que los crea segun las declaraciones de las maquetas
	public function __construct($rutaSrc,$argumentos=[],$dirModulos=null)
	{
		if(!$dirModulos) $dirModulos = conf('ruta/modulos');
		// Se obtiene la ruta limpia, sin / al principio ni final
		preg_match('/^\/?([\\\\\/\w]+)\/?/', $rutaSrc, $rutaSrc);
		$rutaSrc = $rutaSrc[1];

		// Se obtiene el nombre del modulo, siempre debera comenzar con mayusculas y tener el resto del nombre en minusculas, ademas de otros datos distintivos del modulo actual
		$identificador = get_class()::$counter++;
		$this->data['nombreClase'] = $nombre = ucfirst(@end(explode('/', $rutaSrc)));
		// Es un nombre unico, sin uso por el momento
		$this->data['nombre'] = $identificador . $this->data['nombreClase'];
		// Es el nombre de la clase del modulo actual, en realidad es el nombre del namespace, pero asi es mas acertado ya que todos los modulos seran de la clase Modulo dentro del namespace indicado
		$this->data['clase'] = str_replace('/', '\\', $rutaSrc);
		// Es la ruta relativa al directorio de modulos donde se pueden encontrar los datos del modulo actual
		$this->data['src'] = $rutaSrc;

		// Se establece la ruta a los diferentes ficheros
		$maqueta = strtolower("$dirModulos/$rutaSrc/$nombre.html");
		$modulo = strtolower("$dirModulos/$rutaSrc/$nombre.php");
		$controlador = strtolower("$dirModulos/$rutaSrc/$nombre.js");

		// Si hay una maqueta se crea el objeto phpQuery, sino se establece false
		$this->maqueta = file_exists($maqueta) ? pq(sprintf('<nt>%s</nt>',file_get_contents($maqueta))) : pq('<div>');
		

		// Si hay un modulo, se carga y se crea una nueva instancia
		if(file_exists($modulo))
		{
			require_once($modulo);
			$nombreCompuesto = "\\{$this->clase}\\Modulo";
			$this->modulo = new $nombreCompuesto($argumentos);
		}
		else
		{
			// Se crea un array vacio para procesar la maqueta
			$this->modulo = (Object)['data' => []];
		}
		// Se carga el contenido de estilos y controlador	
		$this->controlador = file_exists($controlador) ? file_get_contents($controlador) : '';

		// Se desarrolla la maqueta, es decir, se aplican los interpretes
		$this->desarrollarMaqueta();
		// Se interpretan las variables, es decir, con los valores tomados desde el modulo correspondiente
		$this->interpretarVariables();
		// Se encuentran los modulos que la maqueta establece deben cargarse y se cargan
		$this->encontrarModulos();
	}

	// Se usa para acceder a datos de solo lectura
	public function __get($valor)
	{
		if(isset($this->data[$valor]))
			return $this->data[$valor];
		return null;
	}

	// Este metodo devuelve un array asociativo en donde cada clave representa una clase de modulo y contiene un array de todos los modulos hijos de este modulo que pertenecen a esa clase
	private static function clasificarHijos($arr)
	{
		$return = [];
		foreach($arr as $hijo)
		{
			if(is_array($hijo)) $hijo = (Object) $hijo;
			if(!isset($return[$hijo->clase]))
				$return[$hijo->clase] = [];
			$return[$hijo->clase][] = $hijo;
		}
		return $return;
	}

	// Devuelve una estructura igual a la del metodo store pero con la diferencia de que solamente contiene las instrucciones necesarias para actualizar el tree2 pasado como parametro de modo que se iguale con el tree1
	public static function compare($tree1, $tree2)
	{
		if(!is_array($tree1)) $tree1 = (Array) $tree1;
		if(!is_array($tree2)) $tree2 = (Array) $tree2;
		// Como actualizar la data del tree para que sea igual a esta data
		$diferenciasData = Basic::compararArrays($tree1['data'], $tree2['data']);
		if($diferenciasData == null) $diferenciasData = [];
		// Si hay algun modulo hijo de este modulo que no sea hijo del tree, se debe indicar con toda la data
		$hijosTree2 = $tree2['hijos'];
		$hijosTree1 = isset($tree1['clase']) && ($tree1['clase'] == $tree2['clase']) ? $tree1['hijos'] : [];
		$diferenciasHijos = [];

		foreach($hijosTree2 as $key => $hijoTree2)
		{
			$hijoTree1 = isset($hijosTree1[$key]) ? $hijosTree1[$key] : ['data'=>[],'hijos'=>[]];
			
			$diferenciasHijos = array_merge($diferenciasHijos, [Self::compare($hijoTree1,$hijoTree2)]);
		}

		return [
			'clase' => $tree2['clase'],
			'src' => $tree2['src'],
			'data' => $diferenciasData, 
			'hijos' => $diferenciasHijos];
	}

	// Busca los modulos hijos declarados en la maqueta y los crea
	private function encontrarModulos()
	{
		$modulos = $this->maqueta['[modulo]'];
		foreach($modulos as $modulo)
		{
			// No se puede cargar un modulo que no se indica cual es
			if(!$modulo->hasAttribute('modulo'))
				continue;
			$ruta = $modulo->getAttribute('modulo');
			$modulo->removeAttribute('modulo');

			preg_match('/^ *(\<.*?\>)/',$modulo->ownerDocument->saveXML($modulo), $res);
			$argumentos = preg_match_all('/(\w+) ?= ?[\'\"]([\/\w\-_]+)[\'\"]/',$res[1], $res) ? array_combine($res[1],$res[2]) : [];
			foreach($argumentos as $argumento => $valor)
			{
				$routedValue = Basic::getRoutedValue($this->modulo->data,$valor);
				$argumentos[$argumento] = $routedValue ? $routedValue : $valor;
				if(!Basic::isHtmlAttr($argumento))
					$modulo->removeAttribute($argumento);
			}

			$modulo = pq($modulo);
			$modulo->addClass('mod-' . str_replace('/', '-', $ruta));
			$nuevoModulo = $this->newChild(new \Modulo($ruta,$argumentos));
			$nuevoModulo->appendTo($modulo);
		}
	}

	// Desarrolla la maqueta hasta que no quede ningun interprete sin desarrollar
	private function desarrollarMaqueta()
	{
		$interpretes = Maquetador::getInterpretes();
		$data = &$this->modulo->data;

		// Se desarrollan todos los interpretes
		$interpretesString = '[' . implode('],[', array_map(function($el){return $el->nombre;}, $interpretes)) . ']';

		// Se analizan los hijos de profundidad del 0 al 5 primero para dar prioridad a los elementos padre sobre los hijos
		for($i=0; $i<6; $i++)
		{
			do
			{	
				/*	Por cada interprete se cargan todos los elementos de la maqueta que contengan un atributo cuyo nombre sea identico al del interprete. De modo que si el interprete es if, se cargaran los tag similares al siguiente <div if='nombreVar'></div> */
				$el = $this->getChildsOfDepth($i)->children($interpretesString);


				// Si hay elementos se procede
				if($el->size())
				{
					foreach($interpretes as $interprete)
					{
						if(!$el->attr($interprete->nombre))
							continue;
						// Se toma el primer elemento de la lista
						$el = $el->slice(0,1);
						// Se lee el valor dado al atributo del interprete, el cual sera utilizado por los interpretes para saber que variable deben consultar.
						$valor = $el->attr($interprete->nombre);
						// Se elimina el atributo personalizado
						$el->removeAttr($interprete->nombre);
						// Se llama al interprete con el valor establecido, la referencia del elemento que esta siendo interpretado y una referencia a los datos establecidos en el modulo.
						$interprete->parse($valor,$el,$data);

					}
				}
			}
			// Esta operacion se reitera siempre que existan elementos con valor personalizado acorde al interprete actual
			while($this->getChildsOfDepth($i)->children($interpretesString)->size());
		}

		// Luego se hace un repaso sobre toda la maqueta para despejar dudas de que no existan elementos con atributos de interprete
		do
		{	
			/*	Por cada interprete se cargan todos los elementos de la maqueta que contengan un atributo cuyo nombre sea identico al del interprete. De modo que si el interprete es if, se cargaran los tag similares al siguiente <div if='nombreVar'></div> */
			$el = $this->maqueta[$interpretesString];


			// Si hay elementos se procede
			if($el->size())
			{
				foreach($interpretes as $interprete)
				{
					if(!$el->attr($interprete->nombre))
						continue;
					// Se toma el primer elemento de la lista
					$el = $el->slice(0,1);
					// Se lee el valor dado al atributo del interprete, el cual sera utilizado por los interpretes para saber que variable deben consultar.
					$valor = $el->attr($interprete->nombre);
					// Se elimina el atributo personalizado
					$el->removeAttr($interprete->nombre);
					// Se llama al interprete con el valor establecido, la referencia del elemento que esta siendo interpretado y una referencia a los datos establecidos en el modulo.
					$interprete->parse($valor,$el,$data);

				}
			}
		}
		// Esta operacion se reitera siempre que existan elementos con valor personalizado acorde al interprete actual
		while($this->maqueta[$interpretesString]->size());
	}

	// Metodo que obtiene todos los hijos existentes en un nivel de profundidad $n
	private function getChildsOfDepth($n=1)
	{
		$res = $this->maqueta;
		for($i=0;$i<$n;$i++)
		{
			$res = $res->children('*');
		}
		return $res;
	}

	// Se establece el indice en donde debe insertarse el nuevo hijo basandose en que tiene que quedar ordenado alfabeticamente pero luego de todos los hijos de la misma clase previamente insertados
	private function insertChild(\Modulo $modulo, $i)
	{
		while($i < sizeof($this->hijos) && $this->hijos[$i]->clase <= $modulo->clase)
			$i++;

		// Si el indice excede el tamano del array, se agrega al final
		if($i >= sizeof($this->hijos))
			$this->hijos[] = $modulo;
		else
			// Sino, se agrega en el indice establecido
			array_splice($this->hijos, $i, 0, array($modulo));
	}

	// Determina el valor de las variables que existen en la maqueta
	private function interpretarVariables()
	{
		$this->maqueta->html(Basic::parseVar((string) $this->maqueta, $this->modulo->data));
	}

	
	private function newChild(\Modulo $modulo)
	{
		$this->hijos[] = $modulo;
		return $modulo;

		// SE DESABILITO LA INSERCION ALFABETICA POR SER CONTRAPRODUCENTE CON LA TRASMISION DE DATOS AL CLIENTE

		// La gracia del metodo newChild es que realice una busqueda binaria en el array de hijos actual y lo inserte donde corresponda para ordenarlos alfabeticamente, con la regla de que el hijo agregado recientemente siempre debe estar inserto luego de aquellos que hayan sido insertados previamente y tengan la misma clase
		// Es decir: en el siguiente array:
		// a, a, a, b
		// Si voy a insertar a, debe quedar inserto en la posicion 3, desplazando b hacia la derecha
		// if(sizeof($this->hijos)==0)
		// 	$this->hijos[] = $modulo;
		// else
		// {
		// 	$it = 0;
		// 	$i1 = 0; $i2 = sizeof($this->hijos) - 1;
		// 	while(true)
		// 	{
		// 		if($i2-$i1 <= 1)
		// 		{
		// 			if($this->hijos[$i1]->clase >= $modulo->clase)
		// 				$this->insertChild($modulo,$i1);
		// 			else
		// 				$this->insertChild($modulo,$i2);
		// 			break;
		// 		}

		// 		$iActual = $i1 + floor(($i2 - $i1)/2);
		// 		if($this->hijos[$iActual]->clase == $modulo->clase)
		// 		{
		// 			$this->insertChild($modulo,$iActual);
		// 			break;
		// 		}
		// 		else if($this->hijos[$iActual]->clase > $modulo->clase)
		// 			$i2 = $iActual-1;
		// 		else
		// 			$i1 = $iActual+1;
		// 	}
		// }
		// return $modulo;
	}

	// Devuelve una estructura de array que contiene la estructura de datos del tree actual, incluyendo nombre, clase, raiz del modulo, data generada dentro del modulo y los hijos.
	public function store()
	{
		$hijos = [];
		foreach($this->hijos as $hijo)
			$hijos[] = $hijo->store();
		return
		[
			'nombre' => $this->data['nombre'],
			'clase' => $this->data['clase'],
			'src' => $this->data['src'],
			'data' => $this->modulo ? $this->modulo->data : [],
			'hijos' => $hijos
		];
	}
}

?>