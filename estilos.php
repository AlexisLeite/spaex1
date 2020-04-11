<?php

header("Content-type: text/css", true);

require('configure.php');

function procesarEstilos($estilos,$id)
{
	$regexEstilos = "/^\s*([#\.]?[_\-a-zA-Z\*][,# :()>\.~\*_\-a-zA-Z0-9]*(?:\[.*\])?)\s*{/m";
	$estilos = preg_replace_callback($regexEstilos, function($matches) use ($id)
	{
		$etiquetas = explode(',', $matches[1]);
		for($i=0;$i<sizeof($etiquetas); $i++)
			if(!preg_match("/^ *from|to|body|html|this(?:[ :#\.+>~]|$)/", $etiquetas[$i])) 
				$etiquetas[$i] = "$id {$etiquetas[$i]}";
		return join(',',$etiquetas) . ' {';
	}, $estilos);

	$estilos = str_replace('this', $id, $estilos);
	return $estilos ? $estilos : '';
}

function scan($ruta)
{
	$modulos =array_filter(scandir(conf('ruta/modulos') . "/$ruta"),function($el) use ($ruta)
	{
		return (is_dir(conf('ruta/modulos') . "/$ruta/$el") && !in_array($el, ['.','..']));
	});

	$estilos = '';
	foreach($modulos as $modulo)
		scan("$ruta/$modulo");

	if($ruta != '')
	{
		preg_match('/(?:.*\/)?([^\/]+)$/', $ruta, $nombreModulo);
		$nombreModulo = $nombreModulo[1];
		if(file_exists(conf('ruta/modulos') . "/$ruta/$nombreModulo.css"))
		{
			echo "/* Estilos de $ruta */" . PHP_EOL . PHP_EOL;
			echo procesarEstilos(file_get_contents(conf('ruta/modulos') . "$ruta/$nombreModulo.css"),'.mod-' . str_replace(['\\','/'], ['-','-'], substr($ruta, 1))) . PHP_EOL . PHP_EOL;
		}
	}
}
scan('');

include('estilos.css');
?>