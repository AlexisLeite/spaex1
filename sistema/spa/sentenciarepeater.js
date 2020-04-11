var SentenciaRepeaterAsociativa = 1;
var SentenciaRepeaterString = 2;
class SentenciaRepeater
{
	// el parametro tipo puede ser
	constructor(sentencia,anchor,moduleStack,nombre = null,tipo=SentenciaRepeaterString)
	{
		this.sentencia = sentencia.match(/<!\-\-([\s\S]*?)\-\->/)[1];
		this.anchor = anchor;

		let res;
		if((res = this.sentencia.match(/^\s*(?:<tbody>\s*)?<tr>(.*?)<\/tr>(?:\s*<\/tbody>)?\s*$/)) && this.anchor.is('tr'))
			this.sentencia = res[1];

		this.stack = moduleStack;
		this.nombre = nombre;
		this.tipo = tipo;
	}

	remove()
	{
		this.anchor.empty().hide();
		this.stack.reset();
	}

	// Al ser parte de un proceso de iteracion, el metodo procesar recibira siempre un key correspondiente con el elemento que representa del objeto que genero el loop. 
	// Recibira valor, que es el valor actual del key.
	// Recibira data, array de datos del modulo.
	procesar(key,valor,data)
	{
		let sentencia = this.sentencia, res;
		if(this.tipo & SentenciaRepeaterString)
		{
			while(res = sentencia.match(Regex.variables))
			{
				switch(res[1])
				{
					case 'value':
					case this.nombre:
						sentencia = sentencia.replace(res[0],valor);
						break;
					case 'index':
						sentencia = sentencia.replace(res[0],key);
						break;
					default:
						sentencia = sentencia.replace(res[0],Basic.getRoutedValue(data,res[1]));
				}
				checkExecutionTime();
			}
		}
		else if(this.tipo & SentenciaRepeaterAsociativa)
		{
			while(res = sentencia.match(new RegExp(`\\{${this.nombre}\\.([\\w\\/\\-_\\d]+)}`)))
			{
				sentencia = sentencia.replace(res[0],valor[res[1]]);
				checkExecutionTime();
			}
			while(res = sentencia.match(Regex.variables))
			{
				switch(res[1])
				{
					case 'value':
					case this.nombre:
						sentencia = sentencia.replace(res[0],valor);
						break;
					case 'index':
						sentencia = sentencia.replace(res[0],key);
						break;
					default:
						sentencia = sentencia.replace(res[0],Basic.getRoutedValue(data,res[1]));
				}
				checkExecutionTime();
			}
		}
		else
			return;

		let modulosActuales = this.stack.getAll();
		this.stack.reset();
		this.anchor.html(sentencia).show();

		let modulos = this.anchor.find('[modulo]');
		// No deberia haber diferencia entre los modulos generados en las distintas llamadas a la sentencia repeat, esto es porque la sentencia es inmutable. Entonces se deberian insertar en el mismo orden en que estaban.
		// El unico momento en que se generan nuevos modulos es en la primera llamada
		for(let modulo of modulos)
		{
			modulo = $(modulo);
			let clase = modulo.attr('modulo');

			if(modulosActuales.length)
			{
				let moduloActual = modulosActuales.shift();
				this.stack.add(moduloActual);
				moduloActual.insertAfter(modulo);
				modulo.remove();
			}
			else
				this.stack.add(new Modulo(clase,modulo));
		}
	}
}