require 'parsers.js';

class Anchor
{

	getAnchor()
	{
		let ret = $(`[ancla='${this.numeroAncla}'],[ancla^='${this.numeroAncla},'],[ancla$=',${this.numeroAncla}'],[ancla~=',${this.numeroAncla},']`);
		return ret;
	}

	// Se decide pasar todo el array de data porque a veces un ancla puede depender de mas de una propiedad, por ejemplo al definir atributos.
	// data es una referencia al array de datos del Modulo
	// Cambios sera un array con __delete y __modify indicando que propiedades de esta ruta son modificadas
	procesar(data,cambios)
	{
		let valor,regex,res,htmlJq,clase,atributo,propiedad;
		this.anchor = this.getAnchor();

		if(this.parser)
			this.parser.procesar(data,cambios,this.anchor);
		else
			switch(this.kind)
			{
				case 'variable':
					this.getAnchor().html(Basic.getRoutedValue(data,this.declaracion));
					break;
				case 'modulo':
					clase = this.declaracion;
					while(res = clase.match(Regex.variables))
					{
						checkExecutionTime();
						clase = clase.replace(res[0],Basic.getRoutedValue(data,res[1]));
					}

					this.getAnchor().empty();
					this.stack.modify(0,new Modulo(clase,this.getAnchor()));
					break;
				case 'modulerepeater':
					break;

				default:
					// Cuando el kind no esta clasificado es porque se recogio de cualquier declaracion del tipo: style='personas-{nombre}'.
					atributo = this.declaracion;
					while(res = atributo.match(Regex.variables))
					{
						checkExecutionTime();
						atributo = atributo.replace(res[0],Basic.getRoutedValue(data,res[1]));
					}
					this.getAnchor().attr(this.kind,atributo);
					if(this.kind == 'value')
						this.getAnchor().val(atributo);
					break;
			}
	}

	// El numero ancla servira para hacer comprobaciones en el DOM, si el numero de ancla coincide con el atributo ancla, se esta hablando del mismo elemento
	constructor(declaracion, anchor, kind, moduleStack, numeroAncla)
	{
		this.declaracion = declaracion;
		this.anchor = anchor;
		this.kind = kind;
		this.stack = moduleStack;
		this.numeroAncla = numeroAncla;
		this.propiedadesDepende = [];

		let res;
		this.parser = getParser(kind, declaracion, anchor, kind, moduleStack, numeroAncla);
		if(this.parser)
			this.propiedadesDepende = this.parser.propiedadesDepende;
		else
			switch(kind)
			{
				case 'modulo':
					// Si el modulo no depende de ninguna variable, se carga;
					if(!(Regex.variables.test(declaracion)))
						moduleStack.modify(0,new Modulo(declaracion,anchor));
					else
					{
						Regex.variablesGlobales.lastIndex=0;
						while(res = Regex.variablesGlobales.exec(declaracion))
						{
							checkExecutionTime();
							this.propiedadesDepende.push(res[1]);
						}
					}
					break;

				case 'variable':
					this.propiedadesDepende.push(declaracion);
					break;
				default:
					// Se identifican las propiedades de las que depende
					Regex.variablesGlobales.lastIndex=0;
					while(res = Regex.variablesGlobales.exec(declaracion))
					{
						checkExecutionTime();
						this.propiedadesDepende.push(res[1]);
					}

					// Se verifica si hay variables de configuracion involucradas
					let nDeclaracion = declaracion;
					while(res = nDeclaracion.match(Regex.variables))
						nDeclaracion = nDeclaracion.replace(res[0],Basic.getRoutedValue(Conf,res[1]));
					nDeclaracion = nDeclaracion.replace(Regex.variablesGlobales,'');

					this.getAnchor().attr(kind,nDeclaracion);
					break;
			}
	}
}