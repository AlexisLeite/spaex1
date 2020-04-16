<?php

require_once __DIR__ . '/../basic/basic.php';
require_once __DIR__ . '/../jsoner.php';	

class Conf
{
	private static $conf;

	private static function load()
	{
		if(!Self::$conf)
			Self::$conf = Jsoner::load(__DIR__ . '/../../../aplicacion/conf/conf.json',true);
	}

	public static function run()
	{
		Self::load();
		Self::set('ruta/conf',__DIR__ . '/../../../aplicacion/conf');
	}

	public static function get($rutaConf)
	{
		Self::load();
		return preg_replace_callback('/{([\/\w]+)}/', function($resultado)
		{
			$valor = Self::get($resultado[1]);
			return $valor != null ? $valor : '';
		}, Basic::getRoutedValue(Self::$conf,$rutaConf));
	}

	public static function set($rutaConf, $valor)
	{
		Self::load();
		Basic::setRoutedValue(Self::$conf,$rutaConf,$valor);
	}
}

function conf($valor)
{
	return Conf::get($valor);
}

Conf::run();
?>