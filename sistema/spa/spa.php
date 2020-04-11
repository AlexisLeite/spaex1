<?php
header("Content-type: text/javascript", true);
if(false)
{
	echo "$(document).ready(()=>
				{
				$('#Loader').hide()
				});"; return;
}
require(__DIR__ . '/../../configure.php');

function replace_clogs($src,$name,$line)
{
	return preg_replace('/clog\(([^)]+)\)/', "clog(\"%c{$name}#" . ($line+1) . ":\",\"color: green\",$1)", $src);
}

$printedFiles = [];
function processFile($nombre)
{
	if(in_array($nombre, $GLOBALS['printedFiles'])) return '';
	$GLOBALS['printedFiles'][] = $nombre;
	$file = file($nombre);
	$print = '';

	preg_match('/.*?\/([\w\-\.]+)\.js/', $nombre, $res);
	$nombre = $res[1];

	while(list($key,$line) = each($file))
		$print .= replace_clogs($line,$nombre,$key);

	$print = preg_replace('/\{baseUri}/', Conf::get('baseUri'), $print);

	return $print;
}

$print = processFile(__DIR__ . '/spa.js');

$regexRequire = '/require \'([\w\.\-\/]+)\.js\'\;/';
while(preg_match($regexRequire, $print))
	$print = preg_replace_callback($regexRequire, function($res)
	{
		return processFile(__DIR__ . "/{$res[1]}.js");
	}, $print);

echo $print;
?>