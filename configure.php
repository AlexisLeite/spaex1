<?php
require_once(__DIR__ . '/sistema/clases/conf/conf.php');
Conf::run();

// Esta variable debe ser configurada si su aplicacion se encuentra en un subdirectorio del host, ejemplo: localhost/aplicacion
// $pathPrefix = '/aplicacion';
$pathPrefix = conf('pathPrefix');
// Esta variable permite establecer un array de los nombres de dominios permitidos por la aplicación
$hostPermitidos = conf('hostPermitidos');
// Esta variable permite establecer si el gestor estara disponible para ser utilizado, se recomienda deshabilitar esta configuracion una vez que se haya terminado de configurar el servidor.
$habilitarGestor = conf('habilitarGestor');

if($pathPrefix[strlen($pathPrefix)-1] == '/') $pathPrefix = substr($pathPrefix, 0, strlen($pathPrefix)-1);

// Encontrar la mejor fuente para host name
$hostActual = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
preg_match('/(.*)(?::(.*))?/', $hostActual, $hostInfo);

// Establecer nombre de host y protoclo
$hostName = $hostInfo[1];
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://';

// Filtrar hostname en un white list
if(!in_array($hostName, $hostPermitidos)) {
   header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
  echo 'Bad request';
  exit;
}

$baseUri = "$protocolo$hostName$pathPrefix";

Conf::set('rutaApp',__DIR__);
Conf::set('uri/base',$baseUri);
Conf::set('habilitarGestor',$habilitarGestor);

spl_autoload_register(function ($nombreClase) 
{
	$autoloadPaths = 
	[
		sprintf("%s/%s/%s.php",conf('ruta/clases'),strtolower($nombreClase),strtolower($nombreClase)),
		sprintf("%s/clases/%s.php",conf('ruta/clases'),strtolower($nombreClase))
	];

	foreach($autoloadPaths as $path)
		if(file_exists($path))
		{
			require_once $path;
			return;
		}
});

?>