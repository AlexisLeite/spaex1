<?php
/*

Jsoner es una biblioteca dedicada a procesar ficheros json de forma sencilla.

Api publica:

Jsoner::open($uri, $autocrear=true)
Jsoner::load($uri, $assoc=false, $autocrear=true)
*/
class JsonFile
{
	private $uri;
	private $contenido;
	public function getAll($refresh=false)
	{
		return $this->contenido;
	}

	public function read($prop,$refresh=false)
	{
		if($refresh) $this->refresh();
		return SBasics::getRoutedValue($prop,$this->contenido);
	}

	public function write($prop,$val)
	{
		$this->refresh();
		$archivo = fopen($this->uri,'w');
		flock($archivo,LOCK_EX);

		if($prop = '' || $prop == null)
			$this->contenido[] = $val;
		else
			SBasics::setRoutedValue($prop,$val,$this->contenido);

		fwrite($archivo, json_encode($this->contenido));
		fclose($archivo);
	}

	public function refresh()
	{
		$this->contenido = Jsoner::load($this->uri,true);
	}

	public function __construct($uri)
	{
		$this->uri = $uri;
		$this->refresh();
	}
}

class Jsoner
{
	public static $error = null;

	private static function check($uri,$autocrear)
	{
		if(!file_exists($uri))
		{
			if($autocrear)
			{
				$f = fopen($uri, 'w');
				fwrite($f, json_encode([]));
				fclose($f);
				return true;
			}
			else
			{
				Static::$error = 'No existe el archivo';
				return false;
			}
		}
		return true;
	}

	public static function open($uri,$autocrear=true)
	{
		try
		{
			if(!Static::check($uri,$autocrear))
				return null;
			return new JsonFile($uri);
		}
		catch(Exception $e)
		{
			return null;
		}
	}

	public static function load($uri,$assoc = false,$autocrear=true)
	{
		try
		{
			if(!Static::check($uri,$autocrear))
				return null;
			//Se retiran los comentarios
			return json_decode(preg_replace("/(\/\/.*)$/m",'',file_get_contents($uri)),$assoc);
		}
		catch(Exception $e)
		{
			return null;
		}
	}
}
?>