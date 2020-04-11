class AnchorStack
{
	constructor()
	{
		this.ordered = [];
		this.clasified = [];
	}

	// declaracion es la forma en la que se declaro la propiedad, muchas veces son declaraciones compuestas: paginas/{pagina}
	// anchor es un elemento jq que contiene la funcionalidad de este ancla
	// kind es el tipo de ancla (atributo, variable, foreach, forrepeater, if)
	// el moduleStack sera pasado por el modulo que crea el AnchorStack y sera usado por el ancla para crear modulos
	// El numero ancla servira para hacer comprobaciones en el DOM, si el numero de ancla coincide con el atributo ancla, se esta hablando del mismo elemento
	add(declaracion,anchor,kind,moduleStack,numeroAncla,clase)
	{
		let newAnchor = new Anchor(declaracion, anchor, kind, moduleStack, numeroAncla);
		let propiedades = newAnchor.propiedadesDepende;
		// Las anclas sin propiedad se crean pero no se almacenan, son basicamente aquellas referentes a modulos estaticos, que no dependen de variables. Deben ser creadas para que se cargue el modulo por primera vez, como permanecera.
		this.ordered.push(newAnchor);

		// Se crea una referencia de propiedad por cada propiedad involucrada
		for(let prop of propiedades)
		{
			if(!(prop in this.clasified))
				this.clasified[prop] = [];
			this.clasified[prop].push(newAnchor);
		}
	}

	get(propiedad)
	{
		if(propiedad in this.clasified)
			return this.clasified[propiedad];
		else
			return [];
	}

	ordered()
	{
		return this.ordered;
	}
}