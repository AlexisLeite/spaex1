<?php

class Closer 
{
	public function __destruct()
	{
		Comunicador::cerrar();
	}
}

class Comunicador
{
	private static $conn;
	private static $host = 'localhost';
	private static $user = 'Alexis';
	private static $pass = 'Alexis';
	private static $bdUsuarios = 'usuarios',

	private static $queries;
	private static $qUserPass;
	private static $qUserClave;
	private static $qUser = '';
	private static $qPass = '';

	public static function cerrar()
	{
		if(Self::$conn)	
			Self::$conn->close();
	}

	public static function __callStatic($accion,$argumentos)
	{
		Basic::sesion();
		Self::initConn();

		if(!isset($_SESSION['comunicador'])) $_SESSION['comunicador'] = [];
		$_SESSION['comunicador']['llamado'] = 
		[
			'accion' => $accion,
			'argumentos' => print_r($argumentos,true)
		];


		switch($accion)
		{
			case 'queryError':
				return Self::db()->error;

			case 'query':
				return Self::db()->query($argumentos[0]);
		}

		if(in_array($accion, ['autenticar','corroborarNumeroSesion','autenticarGuardado','logout']))
			$respuesta =  Self::atenderIdentificador($accion,$argumentos);

		$_SESSION['comunicador']['respuesta'] = print_r($respuesta,true);
		return $respuesta;
	}

	private static function prepareQueries()
	{
		// Queries del identificador
		Self::$qUserPass = Self::$conn->prepare("select usuarios.* usuarios where usuarios.usuario=? and usuarios.password=?");
		Self::$qUserClave = Self::$conn->prepare("select usuarios.* from usuarios,claves_aleatorias where usuarios.id=claves_aleatorias.usuario and usuarios.usuario=? and claves_aleatorias.clave=?");

		Self::$qUserPass->bind_param('ss', Self::$qUser, Self::$qPass);
		Self::$qUserClave->bind_param('ss', Self::$qUser, Self::$qPass);
	}

	private static function initConn()
	{
		if(Self::$conn) return;

		Self::$conn = new mysqli(Self::$host,Self::$user,Self::$pass,Self::$bdUsuarios);

		Self::prepareQueries();
	}

	// Devuelve una instancia de la db
	private function db()
	{
		Self::initConn();

		if(Self::$conn->connect_error) 
		{
			echo Self::$conn->connect_error;
			exit;
		}
		return Self::$conn;
	}

	/*
	 
	METODOS DEL AUTENTIFICADOR

	 */

	// Siendo el comunicador solamente un intermediario entre 2 interfaces, no deberia conocer los metodos de ninguna llamada, solamente derivarlas al destino que es el servidor. Siendo que esta funcionalidad no esta implementada, se desarrolla todo el sistema como si lo estuviera implementando toda la funcionalidad en el comunicador.
	private static function atenderIdentificador($accion,$argumentos)
	{
		$argumentos = $argumentos[0];
		switch($accion)
		{
			case 'autenticar':
			// Respuestas:
			// [estado=1, usuario, nroSesion, categoria, claveAleatoria]
			// [estado=0, motivo:datos incorrectos]
				$respuesta = Self::checkLogin($argumentos);

				if(isset($argumentos['recordar']) && $argumentos['recordar'] && $respuesta['estado'] == 1)
				{
					// Generar clave aleatoria
					$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
					$password = "";

					// Debe tener 20 caracteres
					for($i=0; $i<20; $i++)
						$password .= substr($str,rand(0,strlen($str)),1);

					$res = Self::query("insert into claves_aleatorias (usuario,clave) values ('{$respuesta['usuario']['id']}','{$password}')");
				}

				if(isset($password))
					$respuesta['claveAleatoria'] = $password;

				break;			

			case 'corroborarNumeroSesion':
				$res = Self::query("SELECT nroSesion from usuarios where usuario='{$argumentos['credenciales']['user']}' and nroSesion='{$argumentos['nroSesion']}'");
				if($res->num_rows > 0)
				{
					$respuesta = ['estado' => 1];
					break;
				}
				else
					$respuesta = Self::checkLogin($argumentos);
			// 	break;

			case 'autenticarGuardado':
				$respuesta = Self::checkLogin($argumentos);
				break;

			case 'logout':
				Self::query("DELETE claves_aleatorias from claves_aleatorias left join usuarios on claves_aleatorias.usuario = usuarios.id where claves_aleatorias.clave='{$argumentos['claveAleatoria']}' and usuarios.usuario='{$argumentos['user']}'");
				return true;
		}

		return $respuesta;
	}
	private static function fetchAnswer($usuario)
	{
		$datosUsuario = [];
		foreach(conf('datosUsuario') as $dato)
			if(isset($usuario[$dato])) $datosUsuario[$dato] = $usuario[$dato];

		return [
			'estado' => 1,
			'usuario'=> 
			[	
				'usuario' => $datosUsuario
			],
			'nroSesion'=> $usuario['nroSesion'],
			'categoria'=> $usuario['categoria']
		];
	}
	private static function checkLogin($argumentos)
	{
		$datosIncorrectos = 
		[
			'estado' => 0,
			'motivo' => 'Datos incorrectos'
		];

		// Verificar credenciales
		Self::$qUser = $argumentos['credenciales']['user'];
		if(isset($argumentos['credenciales']['pass']))
		{
			Self::$qPass = $argumentos['credenciales']['pass'];

			Self::$qUserPass->execute();
			$res = Self::$qUserPass->get_result();

			// Error en la consulta
			if(!$res)
				return $qUserPass->error;
		}
		else if(isset($argumentos['credenciales']['claveAleatoria']))
		{
			Self::$qPass = $argumentos['credenciales']['claveAleatoria'];

			Self::$qUserClave->execute();
			$res = Self::$qUserClave->get_result();

			// Error en la consulta
			if(!$res)
				return $qUserClave->error;
		}

		// Datos correctos
		if($persona = $res->fetch_assoc())
			return Self::fetchAnswer($persona);

		// Datos incorrectos
		return $datosIncorrectos;

	}
}


?>