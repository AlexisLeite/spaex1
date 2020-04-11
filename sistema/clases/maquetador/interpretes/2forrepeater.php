<?php
namespace Interpretes;

class InterpreteForrepeater
{
	public static function parse($valor,&$elemento,$data)
	{
		$key = $valor;
		$valor = array_values(\Basic::getRoutedValue($data,$valor));
		if($valor == null || !is_array($valor) || !sizeof($valor))
		{
			$elemento->remove();
			return;
		}
		$sentencias = [];

		$sentencia = $elemento->html();
		$elemento->empty();

		for($i=0; $i<sizeof($valor); $i++)
		{
			$regex = '/(?:\{|\%7B)' . str_replace('/', '\\/', $key) . '(?:\}|\%7D)/';
			$nuevaSentencia = preg_replace_callback($regex, function($res) use ($valor,$i)
			{
				return $valor[$i];
			}, $sentencia);
			$sentencias[] = str_replace('{index}', $i, $nuevaSentencia);
		}
		$elemento->html(join(PHP_EOL,$sentencias));

	}

}

?>