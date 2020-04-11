var if_parser = function(declaracion, anchor, kind, moduleStack, numeroAncla)
{
	this.declaracion = declaracion;
	this.kind = kind;
	this.stack = moduleStack;
	this.propiedadesDepende = [];
	this.numeroAncla = numeroAncla;

	this.propiedadesDepende.push(declaracion);

	this.getElse = function()
	{
		// Ocuparse del else
		let elses = anchor.find('else');
		this.else = null;
		for(let _else of elses)
		{
			_else = $(_else);
			if(_else.parents('[ancla]').first().attr('ancla') == this.numeroAncla)
			{
				this.else = _else;
				break;
			}
		}
		return this.else;
	}
	if(this.getElse()) this.else.detach();

	// Esconde el if
	this.if = $(`<span>${anchor.html()}</span>`);
	anchor.empty();
	this.if.appendTo(anchor).hide();

	if(this.else) this.else.appendTo(anchor);

	this.procesar = function(data,cambios,anchor)
	{
		let valor = Basic.getRoutedValue(data,this.declaracion);
		if(valor)
		{
			if(this.getElse()) this.else.hide();
			this.if.show();
		}
		else
		{
			if(this.getElse()) this.else.show();
			this.if.hide();
		}

	}
}