var modulerepeater_parser = function(declaracion, anchor, kind, moduleStack, numeroAncla)
{
	this.declaracion = declaracion;
	this.kind = kind;
	this.stack = moduleStack;
	this.propiedadesDepende = [];
	this.numeroAncla = numeroAncla;

	this.propiedadesDepende.push(declaracion);

	this.sentencia = anchor.html();

	anchor.empty();
	this.sentencias = [];

	this.procesar = function(data,cambios,anchor)
	{
		// El array de sentencias se deberia corresponder en todo momento con el array de datos correspondientes con la propiedad asociada a este ancla.

		// Por lo tanto, si hay elementos que eliminar declarados en los cambios, es menester que tambien hayan sido creados previamente en las sentencias del ancla. Por lo tanto, si todo viene funcionando correctamente, se podria proceder a eliminar las sentencias correspondientes sin ningun riesgo.
		for(let del of cambios.__delete)
			this.sentencias[del].remove();

		// Lo otro que queda por hacer es proceder con las modificaciones, si es que existieran
		for(let mod in cambios.__modify)
		{
			for(let del of cambios.__delete)
				this.sentencias[del].remove();

			// Lo otro que queda por hacer es proceder con las modificaciones, si es que existieran
			for(let mod in cambios.__modify)
			{
				// Si no existe la sentencia en el stack de sentencias, se crea una nueva.
				if(!(mod in this.sentencias))
				{
					let sentencia = this.sentencia;
					let res;
					while(res = sentencia.match(`\\{${this.declaracion}\\.([\\w\\-_\\d]+)}`))
					{
						sentencia = sentencia.replace(res[0],Basic.getRoutedValue(data,`${this.declaracion}/${mod}/${res[1]}`));
					}
					sentencia = $(sentencia.replace(/\{index}/g,mod));
					this.sentencias[mod] = sentencia.appendTo(anchor);
					this.stack.add(new Modulo(sentencia.attr('modulo'),sentencia));
				}
			}
		}
	}
}