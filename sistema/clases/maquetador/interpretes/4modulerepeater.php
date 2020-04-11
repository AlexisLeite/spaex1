<?php
namespace Interpretes;

// Interpreta arrays asociativos y reemplaza las propiedaes declaradas en la sentencia repeat con el formato {nombreArray.nombrePropiedad}
class InterpreteModulerepeater
{
	public static function parse($nombreVar,&$elemento,$data)
	{
		$valor = \Basic::getRoutedValue($data,$nombreVar);
		if($valor == null || !is_array($valor) || !sizeof($valor))
		{
			$elemento->empty();
			return;
		}
		$sentencias = [];

		$sentencia = $elemento->html();
		$elemento->empty();

		foreach($valor as $k => $v)
		{
			$regex = '/(?:\{|\%7B)' . $nombreVar . '\.([a-zA-Z][\w\d\-_]*)(?:\}|\%7D)/';
			// $sentencias[] = 

			$nuevaSentencia = preg_replace_callback($regex, function($res) use ($v,$nombreVar)
			{
				return isset($v[$res[1]]) ? $v[$res[1]] : '';
			}, $sentencia);
			$nuevaSentencia = str_replace('{index}', $k, $nuevaSentencia);
			$sentencias[] = $nuevaSentencia;
		}
		$elemento->html(join(PHP_EOL,$sentencias));
	}

}

?>