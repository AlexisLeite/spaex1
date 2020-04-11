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

		if(in_array($accion, ['buscarPersonas','eliminarPersona','agregarPersona','getEstadisticas','getPersonas','getPersona','modificarPersona']))
		{
			$respuesta =  Self::atenderPersonas($accion,$argumentos);
		}

		$_SESSION['comunicador']['respuesta'] = print_r($respuesta,true);
		return $respuesta;
	}

	private static function atenderPersonas($accion,$argumentos)
	{
		if(isset($argumentos[0]))
			$argumentos = $argumentos[0];

		$orden = isset($argumentos['orden']) ? $argumentos['orden'] : 'ci';
		$ordenAsc = isset($argumentos['ascDesc']) ? $argumentos['ascDesc'] : 'asc';
		$limitCuantos = isset($argumentos['limite']) ? $argumentos['limite'] : 30;
		$offset = isset($argumentos['pagina']) ? $argumentos['pagina'] * $limitCuantos : 0;

		switch($accion)
		{
			case 'agregarPersona':
				$res = Self::query("insert into personas (ci,nombre,apellido,direccion,telefono) values ('{$argumentos['ci']}','{$argumentos['nombre']}','{$argumentos['apellido']}','{$argumentos['direccion']}','{$argumentos['telefono']}')");

				if($res)
					return true;
				else
				{
					$res = Self::query("select ci from personas where ci='{$argumentos['ci']}'");

					if($res->num_rows)
						return 'Ya existe una persona con ese numero de cedula.';

					return Self::db()->error;
				}

			case 'buscarPersonas':

				if((int) $argumentos)
					$res = Self::query("(SELECT personas.ci, personas.nombre, personas.apellido, personas.direccion, personas.telefono ,categorias.nombre as categoria FROM `personas`,categorias WHERE ci like ('{$argumentos}%') and personas.categoria = categorias.numero

						union 

						SELECT personas.ci, personas.nombre, personas.apellido, personas.direccion, personas.telefono ,categorias.nombre as categoria FROM `personas`,categorias WHERE concat(ci, ' ', telefono) like ('%{$argumentos}%') and personas.categoria = categorias.numero)
						 limit 40");
				else
					$res = Self::query("SELECT personas.ci, personas.nombre, personas.apellido, personas.direccion, personas.telefono ,categorias.nombre as categoria FROM `personas`,categorias WHERE (match (personas.nombre,apellido,direccion,telefono) against ('{$argumentos}*' in BOOLEAN MODE) or concat(personas.nombre,' ',apellido,' ',direccion,' ',telefono) like replace('%{$argumentos}%',' ','%')) and personas.categoria = categorias.numero order by match (personas.nombre,apellido,direccion,telefono) against ('{$argumentos}*' in BOOLEAN MODE) desc limit 40");

				if($res)
					return $res->fetch_all(MYSQLI_ASSOC);
				else
					return Self::db()->error;

			case 'eliminarPersona':
				$res = Self::query("delete from personas where ci in ('{$argumentos}')");
				if($res)
					return true;
				else
					return Self::db()->error;

			case 'getEstadisticas':
				$res = Self::query("select count(ci) as registros, count(ci) div $limitCuantos as paginas, count(ci) % $limitCuantos as ultimaPagina from personas");
				if($res)
					return $res->fetch_assoc();
				else
					return $Self::db()->error();

			case 'getPersonas':
				if($orden == 'categoria') $orden = 'categorias.nombre';
				$res = Self::query("select personas.*, categorias.nombre as categoria from personas,categorias where personas.categoria = categorias.numero order by $orden $ordenAsc limit $offset,$limitCuantos");

				if($res)
					return $res->fetch_all(MYSQLI_ASSOC);
				else
					return Self::db()->error;

			case 'getPersona':
				$res = Self::query("select personas.* from personas where ci='{$argumentos}'");
				if($res)
					return $res->fetch_assoc();
				else
					return Self::db()->error;

			case 'modificarPersona':
				$res = Self::query("update personas set nombre = '{$argumentos['nombre']}', apellido='{$argumentos['apellido']}', telefono='{$argumentos['telefono']}', direccion='{$argumentos['direccion']}' where ci='{$argumentos['ci']}'");

				if($res)
					deb('Resultadoquery',print_r($res,true));
				else
					deb('Resultadoquery',print_r(Self::db(),true));
		}
	}

	private static function prepareQueries()
	{
		// Queries del identificador
		Self::$qUserPass = Self::$conn->prepare("select personas.*, usuarios.nroSesion from personas,usuarios where personas.ci=usuarios.ci and usuarios.usuario=? and usuarios.password=?");
		Self::$qUserClave = Self::$conn->prepare("select personas.*, usuarios.nroSesion from personas,usuarios,claves_aleatorias where personas.ci=usuarios.ci and personas.ci=claves_aleatorias.ci and usuarios.usuario=? and claves_aleatorias.clave=?");

		Self::$qUserPass->bind_param('ss', Self::$qUser, Self::$qPass);
		Self::$qUserClave->bind_param('ss', Self::$qUser, Self::$qPass);
	}

	private static function initConn()
	{
		if(Self::$conn) return;

		Self::$conn = new mysqli('localhost','Alexis','Alexis','rrhh');

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

					$res = Self::query("insert into claves_aleatorias (ci,clave) values ('{$respuesta['usuario']['ci']}','{$password}')");
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
				Self::query("DELETE claves_aleatorias from claves_aleatorias left join usuarios on claves_aleatorias.ci = usuarios.ci where claves_aleatorias.clave='{$argumentos['claveAleatoria']}' and usuarios.usuario='{$argumentos['user']}'");
				return true;
		}

		return $respuesta;
	}
	private static function fetchAnswer($usuario)
	{
		return [
			'estado' => 1,
			'usuario'=> 
			[	
				'ci' => $usuario['ci'],
				'nombre' => $usuario['nombre'],
				'apellido' => $usuario['apellido'],
				'telefono' => $usuario['telefono'],
				'direccion' => $usuario['direccion']
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