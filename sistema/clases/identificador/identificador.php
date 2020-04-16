<?php

define('oIdentificador', 'identificador');
define('oRedireccionamiento', 'redireccionar');
define('oErrorAutenticacion', 'errorAutenticacion');
define('oSesionActual','sesionActual');
define('oCredenciales','credenciales');
define('nroSesion','nroSesion');
define('rangoActual','rangoActual');
define('cookieIdentificador','identificador');

class Identificador
{
	// Propiedades de configuracion
	private static $variableSesion = 'DatosIdentificadorSesion';
	private static $ficheroRequisitos;
	private static $rangoPredeterminado = 5;

	// Propiedades privadas
	private static $rangoActual = 5; //Esta propiedad DEBERA ser definida por la comunicacion con el servidor, actualmente esta definida manualmente para realizar testeos en el redireccionamiento.

	// Metodos Api publica
	public static function run()
	{
		Self::$ficheroRequisitos = conf('ruta/conf') . '/requisitos.json';
		Self::$rangoPredeterminado = conf('rangoPredeterminado');

		Basic::sesion();

		// Si no existe objeto de identificador, se crea un array vacio para su posterior utilizacion
		if(!isset($_SESSION[oIdentificador]))
			$_SESSION[oIdentificador] = [];
		else if(isset($_SESSION[oIdentificador][rangoActual]))
			Self::$rangoActual = $_SESSION[oIdentificador][rangoActual];

		// Si no me encuentro en login, el objeto de redireccionamiento debe ser borrado para evitar confusiones
		if(Router::get('seccion') != 'login')
		{
			unset($_SESSION[oIdentificador][oRedireccionamiento]);
			if(Router::get('seccion') == 'logout')
			{
				// Enviando un estado 100, el cual no existe entre las respuestas del servidor, me aseguro de eliminar todas las variables de estado de sesion y no crear ninguna. Por lo tanto, cierro la sesion
				Self::corroborarRespuestaAutenticacion(['estado' => 100],[]);
				if(isset($_COOKIE[cookieIdentificador]))
					Comunicador::logout(unserialize($_COOKIE[cookieIdentificador]));
				setcookie(cookieIdentificador,'',time()-1);
				// Se redirecciona al inicio
				Router::redirect('');
				return;
			}
		}
		
		if(Router::get('seccion') == 'login' 
			&& isset($_POST['User']) 
			&& isset($_POST['Pass']))
		{
			$user = htmlspecialchars($_POST['User']);
			$pass = htmlspecialchars($_POST['Pass']);
			$credenciales = ['user' => $user,	'pass' => $pass];
			$recordar = isset($_POST['Recordar']) ? $_POST['Recordar'] : false;
			// Hay un intento de iniciar sesion
			$respuesta = Comunicador::autenticar([
				'credenciales' => $credenciales,
				'recordar' => $recordar ? 1 : 0
			]);

			Self::corroborarRespuestaAutenticacion($respuesta,$credenciales);
		} 
		else if(isset($_SESSION[oIdentificador][oSesionActual]))
		{ // Existe una sesion interna?
			// Corroborar numero de sesion con el servidor
			$respuesta = Comunicador::corroborarNumeroSesion(
			[
				'credenciales' => $_SESSION[oIdentificador][oCredenciales],
				'nroSesion' => $_SESSION[oIdentificador][nroSesion]
			]);

			// Si el numero de sesion esta desactualizado, se actualizaran los datos
			if($respuesta['estado'] == 2)
			{
				$_SESSION[oIdentificador][nroSesion] = $respuesta['nroSesion'];
				$_SESSION[oIdentificador][oSesionActual] = $respuesta['usuario'];
			}
			else if($respuesta['estado'] == 0)
				// Si hubo un error en la autenticacion, se pasa al metodo encargado de recibir las respuestas del servidor, no interesan las credenciales ya que de todas formas dejaron de ser validas. Lo importante de esta accion es que se destruyan los datos guardados para evitar la utilizacion de datos equivocos.
				Self::corroborarRespuestaAutenticacion($respuesta,[]);
		}
		else if(isset($_COOKIE[cookieIdentificador]))
		{
			// Si existe una sesion guardad en los cookies, se debe corroborar con el servidor
			$cookie = unserialize($_COOKIE[cookieIdentificador]);
			$user = htmlspecialchars($cookie['user']);
			$claveAleatoria = htmlspecialchars($cookie['claveAleatoria']);
			$credenciales = ['user' => $user,	'claveAleatoria' => $claveAleatoria];
			$respuesta = Comunicador::autenticarGuardado([
				'credenciales' => $credenciales,
			]);
			if($respuesta['estado'] == 0)
				// Si los datos son incorrectos no se deben usar mas
				setcookie(cookieIdentificador,'',time()-1);
			else
				Self::corroborarRespuestaAutenticacion($respuesta,$credenciales);
		}

		// Se necesita enrutar?
		$requisitos = Jsoner::load(Self::$ficheroRequisitos,true);
		$mayorCantidadDeRequisitos = -1;

		// El procedimiento es descartas las entradas de registros de requisitos cuando:
		// - El requisito tenga claves que no se encuentran en el ruteo actual
		// - El requisito tenga menos claves que otro requisito que ya haya coincidido anteriormente
		// De esta forma se logra encontrar aquel requisito que tenga todas sus claves coincidentes y ademas sea el requisito con mayor cantidad de claves establecidas.
		// Esto permite lograr un mayor numero de especificacion a la hora de definir los registros de requisitos.
		for($i = sizeof($requisitos)-1; $i>=0; $i--)
		{
			foreach($requisitos[$i]['claves'] as $nombre => $valor)
			{
				if(!array_key_exists($nombre, Router::$rutaActual) || $valor != Router::$rutaActual[$nombre])
				{
					array_splice($requisitos, $i, 1);
					continue 2;
				}
			}
			if(sizeof($requisitos[$i]['claves']) <= $mayorCantidadDeRequisitos)
			{
				array_splice($requisitos, $i, 1);
				continue;
			}
			$mayorCantidadDeRequisitos = sizeof($requisitos[$i]['claves']);
		}

		// Se separa el mas restrictivo, en realidad en este momento obligadamente se encuentra solamente una clave en el array de requisitos, pero al no conocer su indice es mas sencillo acceder a traves de shift
		$masRestrictivo = array_shift($requisitos);

		// Se determina si el nivel de restriccion del registro es mayor que el rango autenticado por el usuario actualmente, si es asi se realiza un redireccionamiento interno.
		if($masRestrictivo['restriccion'] < Self::$rangoActual)
		{
			// Aqui es donde se crea el objeto de redireccionamiento. Define las siguientes propiedades:
			// 'motivo' que sera trasmitida al usuario cuando llegue a la seccion de logueo y explica por que esta siendo redireccionado.
			// 'urlFinal' que especifica a que direccion estaba intentando ingresar el usuario cuando fue redireccionado, se deberia usar a futuro para mejorar la experiencia del usuario y llevarlo nuevamente cuando logre iniciar sesion con el rango correspondiente.
			$_SESSION[oIdentificador][oRedireccionamiento] = 
			[
				'urlFinal' => Router::current(),
				'motivo' => 'Rango insuficiente'
			];
			Router::redirect('login');
		}

	}

	private static function corroborarRespuestaAutenticacion($respuesta,$credencialesUsadas)
	{
		// Se eliminan los datos de sesion correspondientes a la autenticacion, de esta forma se evitan conflictos entre las distintas posibles respuestas
		unset($_SESSION[oIdentificador][oCredenciales]);
		unset($_SESSION[oIdentificador][nroSesion]);
		unset($_SESSION[oIdentificador][oSesionActual]);
		unset($_SESSION[oIdentificador][oErrorAutenticacion]);
		unset($_SESSION[oIdentificador][rangoActual]);
		Self::$rangoActual = Self::$rangoPredeterminado;

		switch($respuesta['estado'])
		{
			// El servidor rechaza el intento, se guarda un motivo
			case 0:
				// Se establece el motivo
				$_SESSION[oIdentificador][oErrorAutenticacion] = $respuesta['motivo'];

				// Se eliminan los cookies
				setcookie(cookieIdentificador,'',time()-1);
				break;
			// El servidor acepta el intento, se guarda el estado de usuario
			case 1:
				$_SESSION[oIdentificador][oSesionActual] = $respuesta['usuario'];
				$_SESSION[oIdentificador][oCredenciales] = $credencialesUsadas;
				$_SESSION[oIdentificador][nroSesion] = $respuesta['nroSesion'];

				$_SESSION[oIdentificador][rangoActual] = Self::$rangoActual = $respuesta['categoria'];

				// Si se envio al servidor la bandera recordar, este deberia haber respondido con una clave aleatoria. Esto es accesible desde 'claveAleatoria' en la respuesta, verificando esta propiedad podriamos determinar si debemos guardar en cookies la sesion.
				if(isset($respuesta['claveAleatoria']))
				{
					// Si se recibe una nueva clave aleatoria y era el mecanismo de autenticacion que se estaba utilizando, se actualiza
					if(isset($_SESSION[oIdentificador][oCredenciales]['claveAleatoria']))
						$_SESSION[oIdentificador][oCredenciales]['claveAleatoria'] = $respuesta['claveAleatoria'];

					// De todas formas la clave aleatoria siempre debe ser escrita en los cookies
					setcookie(cookieIdentificador,
					serialize([
						'user' => $_SESSION[oIdentificador][oCredenciales]['user'],
						'claveAleatoria' => $respuesta['claveAleatoria']
					]),time()+60*60*24*30);
				}

				if(isset($_SESSION[oIdentificador][oRedireccionamiento]))
				{
					Router::redirect($_SESSION[oIdentificador][oRedireccionamiento]['urlFinal']);
					unset($_SESSION[oIdentificador][oRedireccionamiento]);
				}
				break;
		}
	}
}

?>