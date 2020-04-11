<?php

class Basic
{ 

	// Mediante este metodo se podran encontrar todas las instrucciones necesarias para actualizar el array 1 para que sea identico que el array 2
	// Se debe especificar por lo tanto:
	// - Que keys faltan en el array 1
	// - Que keys hay que modificar en el array 1
	// - Que keys hay que eliminar en el array 1
	public static function compararArrays(Array $arr1,Array $arr2)
	{
		// Seran aquellos keys en donde el valor del array 2 sea diferente del del array 1
		$differentKeys = [];
		// Seran aquellos keys en donde el array 1 no tenga nada declarado pero el array 2 si
		$deleteKeys = [];

		foreach($arr1 as $key => $valor)
		{
			if(!isset($arr2[$key]))
			{
				$deleteKeys[] = $key;
				continue;
			}

			$key1Array = is_array($valor);
			$key2Array = is_array($arr2[$key]);

			// Si el valor del array 1 no es un array y el valor del array 2 es un array o los valores son diferentes, se agrega el key a las diferencias.
			if((!$key1Array) && ($key2Array || $arr2[$key] != $valor))
				$differentKeys[$key] = $arr2[$key];
			else if($key1Array && $key2Array)
			{
				$diferencias = Self::compararArrays($arr1[$key],$arr2[$key]);
				if($diferencias == null) continue;
				$differentKeys[$key] = $diferencias;
			}
			else if($key1Array)
				$differentKeys[$key] = $arr2[$key];
		}

		// Todos aquellos keys que esten en $arr2 y no esten en $arr1 seran agregados a la lista de diferencias
		foreach($arr2 as $key => $valor)
			if(!isset($arr1[$key]))
			{
				if(is_array($arr2[$key]))
				{
					$diferencias = Self::compararArrays([],$arr2[$key]);
					if($diferencias == null) continue;
					$differentKeys[$key] = $diferencias;
				}
				else
					$differentKeys[$key] = $valor;
			}

		if(sizeof($differentKeys) == 0 && sizeof($deleteKeys) == 0)
			return null;

		return ['__modify' => $differentKeys, '__delete' => $deleteKeys];
	}

	public static function array_diff_assoc_recursive($array1, $array2, $invertido = false)
	{
	  foreach($array1 as $key => $value) {
	    if(is_array($value)) {
	      if(!isset($array2[$key]) || !is_array($array2[$key])) {
	        return true;
	      } else {
	        $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
	        if($new_diff)
	          return true;
	      }
	    } else if(!array_key_exists($key,$array2) || $array2[$key] !== $value) {
	      return true;
	    }
	  }
	  if($invertido)
	  	return false;
	  else
	  	return array_diff_assoc_recursive($array2,$array1,true);
	}
	
	public static function isHtmlAttr($arg)
	{
		$htmlAttrs = ["accept","accept-charset","accesskey","action","alt","async","autocomplete","autofocus","autoplay","charset","checked","cite","class","cols","colspan","content","contenteditable","controls","coords","data","datetime","default","defer","dir","dirname","disabled","download","draggable","dropzone","enctype","for","form","formaction","headers","height","hidden","high","href","hreflang","http-equiv","id","ismap","kind","label","lang","list","loop","low","max","maxlength","media","method","min","multiple","muted","name","novalidate","onabort","onafterprint","onbeforeprint","onbeforeunload","onblur","oncanplay","oncanplaythrough","onchange","onclick","oncontextmenu","oncopy","oncuechange","oncut","ondblclick","ondrag","ondragend","ondragenter","ondragleave","ondragover","ondragstart","ondrop","ondurationchange","onemptied","onended","onerror","onfocus","onhashchange","oninput","oninvalid","onkeydown","onkeypress","onkeyup","onload","onloadeddata","onloadedmetadata","onloadstart","onmousedown","onmousemove","onmouseout","onmouseover","onmouseup","onmousewheel","onoffline","ononline","onpageshow","onpaste","onpause","onplay","onplaying","onprogress","onratechange","onreset","onresize","onscroll","onsearch","onseeked","onseeking","onselect","onstalled","onsubmit","onsuspend","ontimeupdate","ontoggle","onunload","onvolumechange","onwaiting","onwheel","open","optimum","pattern","placeholder","poster","preload","readonly","rel","required","reversed","rows","rowspan","sandbox","scope","selected","shape","size","sizes","span","spellcheck","src","srcdoc","srclang","srcset","start","step","style","tabindex","target","title","translate","type","usemap","value","width","wrap"];

		return in_array(strtolower($arg), $htmlAttrs);
	}

	public static function reemplazarVariables($string,$arrays)
	{
		if(is_array($string))
		{
			foreach($arrays as $array)
			{
				$res = Basic::getRoutedValue($array,$string[1]);
				if($res)
				{
					$string = $res;
					break;
				}
			}
			if(is_array($string)) $string = conf($string[1]);
		}

		return preg_replace_callback('/(?:\{|%7B)([\w\\\\\\/]+)(?:\}|%7D)/', function($res) use ($arrays)
		{
			return Basic::reemplazarVariables($res,$arrays);
		}, $string);
	}
	// Procesa todas las variables de tipo {ruta/variable} o {{variable}} presentes en una cadena de texto
	// Intentara hacer el parse primero en los distintos array pasados como parametros y luego en la configuracion
	public static function parseVar($string)
	{
		$arrays = func_get_args();
		$string = array_shift($arrays);

		return Basic::reemplazarVariables($string, $arrays);
	}

	// Devuelve un valor de un array de acuerdo a la ruta especificada
	public static function getRoutedValue($arr, $rutaConf)
	{
		preg_match('/^\/?(.*?)\/?$/', $rutaConf, $rutaConf);
		$ruta = explode('/', $rutaConf[1]);
		while(($directorio = array_shift($ruta)) !== NULL)
		{
			if(!isset($arr[$directorio]))
				return null;
			$arr = $arr[$directorio];
		}
		return $arr;
	}

	// Crea un arbol de directorios definidos por una ruta
	public static function makeDir($base,$ruta)
	{
		$ruta = explode('/', $ruta);
		while($directorioActual = array_shift($ruta))
		{
			if(!file_exists("$base/$directorioActual"))
				mkdir("$base/$directorioActual");
			$base = "$base/$directorioActual";
		}
		return $base;
	}

	public static function sesion()
	{
		if (session_status() == PHP_SESSION_NONE)
		{
			$result = session_start();
		}
	}

	// Actualiza un array para crear un valor en la ruta especificada
	public static function setRoutedValue(&$arr, $rutaConf, $val)
	{
		preg_match('/^\/?(.*?)\/?$/', $rutaConf, $rutaConf);
		$ruta = explode('/', $rutaConf[1]);
		while($directorio = array_shift($ruta))
		{
			if(!isset($arr[$directorio]) || !is_array($arr[$directorio]))
				$arr[$directorio] = [];
			$arr = &$arr[$directorio];
		}
		$arr = $val;
	}
}


Basic::sesion();
if(isset($_SESSION['debuger'])) $_SESSION['debuger'] = [];

function deb($categoria, $que, $titulo='')
{
	if(!isset($_SESSION['debuger']))
		$_SESSION['debuger'] = [];

	if(!isset($_SESSION['debuger'][$categoria]))
		$_SESSION['debuger'][$categoria] = [];

	$_SESSION['debuger'][$categoria][] = "<strong style='font-size:10.2pt'>$titulo</strong>\n" . print_r($que,true);
}

?>