var forrepeater_parser = function(declaracion, anchor, kind, moduleStack, numeroAncla)
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
			// Si no existe la sentencia en el stack de sentencias, se crea una nueva.
			if(!(mod in this.sentencias))
			{
				let nodeType = anchor.is('table') ? 'tr' : 'span';
				let anclaSentencia = $(`<${nodeType} class="sentencia-repeater"></${nodeType}>`).appendTo(anchor);
				this.sentencias[mod] = new SentenciaRepeater(this.sentencia,anclaSentencia,this.stack.addStack(),this.declaracion,SentenciaRepeaterString);
			}

			// Se procesa el valor de la sentencia actual
			let valor = Basic.getRoutedValue(data,`${this.declaracion}/${mod}`);
			this.sentencias[mod].procesar(mod,valor,data);
		}
	}
}