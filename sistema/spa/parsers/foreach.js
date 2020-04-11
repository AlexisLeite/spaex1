var foreach_parser = function(declaracion, anchor, kind, moduleStack, numeroAncla)
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
		for(let del of cambios.__delete)
			this.sentencias[del].remove();

		// Lo otro que queda por hacer es proceder con las modificaciones, si es que existieran
		for(let mod in cambios.__modify)
		{
			// Si no existe la sentencia en el stack de sentencias, se crea una nueva.
			if(!(mod in this.sentencias))
			{
				let nodeType = anchor.is('table, tbody') ? 'tr' : 'span';
				let anclaSentencia = $(`<${nodeType} class="sentencia-repeater"></${nodeType}>`).appendTo(anchor);
				this.sentencias[mod] = new SentenciaRepeater(this.sentencia,anclaSentencia,this.stack.addStack(),this.declaracion,SentenciaRepeaterAsociativa);
			}

			// Se procesa el valor de la sentencia actual
			let valor = Basic.getRoutedValue(data,`${this.declaracion}/${mod}`);
			this.sentencias[mod].procesar(mod,valor,data);
		}
	}
}