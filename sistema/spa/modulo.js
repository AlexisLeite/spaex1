var numeroAncla = 0;
class Modulo
{

	// Jquery methods, usados para manipular el Dom
	appendTo(el)
	{
		this.jqObject.appendTo(el);
	}
	insertAfter(el)
	{
		this.jqObject.insertAfter(el);
	}
	insertBefore(el)
	{
		this.jqObject.insertBefore(el);
	}
	prependTo(el)
	{
		this.jqObject.prependTo(el);
	}

	constructor(clase, htmlParent)
	{
		this.data = [];
		// Clase del modulo
		this.clase = clase;
		// Array de contenedores de modulos
		this.hijos = [];
		// Se carga el codigo fuente de este modulo
		this.modulo = spa.getModulo(this.clase);

		if(this.modulo == undefined)
		{
			clog(this.modulo,this.clase);
			return;
		}

		// Se crea el modulo en el DOM
		this.jqObject = $(htmlParent);

		this.jqObject.attr('class','mod-' + this.clase.replace(/\//g,'-'));
		// Se almacena el controlador
		this.controlador = this.modulo.controlador;

		// Procesar anchors
		this.anchors = new AnchorStack();
		this.findAnchors();
	}

	findAnchors() 
	{
		let maqueta = this.modulo.maqueta;

		// Se eliminan los comentarios
		maqueta = maqueta.replace(Regex.htmlComments,'');

		// Todos los bloques repeat seran encapsulados de forma que no sean procesados, seran tarea interna de sus respectivos loops
		// La encapsulacion en realidad consiste en cambiar la cadena completa del outerHTML por un bloque de tipo [[Repeat#]] para posteriormente volver a colocarlo en el orden que estaban
		let res, i=0, repeats = [];
		Regex.repeaters.lastIndex = 0;
		while(res = Regex.repeaters.exec(maqueta))
		{
			checkExecutionTime();
			let j = maqueta.indexOf(res[0]);
			let insertar = res[0].replace(res[3],`[[Repeat${i++}]]`);
			maqueta = maqueta.substr(0,j) + insertar + maqueta.substr(j+res[0].length);
			repeats.push(res[3]);
		}
		if(res)
		{
			while(res.length)
			{
			}
		}
		// Se buscan todas las anclas en la maqueta
		res = maqueta.match(Regex.anchors);
		let anchors = [];
		if(res)
		{
			while(res.length)
			{
				checkExecutionTime();
				let r = res.shift();
				let r2;
				let j = maqueta.indexOf(r);
				// Se reemplazan las anclas en la maqueta

				if(r2 = r.match(Regex.attribute))
				{
					let kind = r2[1];
					let prop = r2[2];

					anchors[numeroAncla] = [kind,prop];
					maqueta = maqueta.substr(0,j) + `ancla='${numeroAncla}'` + maqueta.substr(j+r.length);
				}
				else
				{
					r2 = r.match(Regex.onlyVar);
					let prop = r2[1];
					anchors[numeroAncla] = ['variable',prop];
					maqueta = maqueta.substr(0,j) + `<span ancla='${numeroAncla}'></span>` + maqueta.substr(j+r.length);
				}

				numeroAncla++;
			}
		}

		for(let i in repeats)
			maqueta = maqueta.replace(`[[Repeat${i}]]`,`<!--${repeats[i]}-->`);

		// Desarrollar puntos donde haya varias anclas
		let multiplesAnclas = new RegExp(/< *\w+ (?:[^>]*ancla *= *(['"]) *(\d+) *\1[^>]*){2,}[^>]*>/,'g');
		while(res = multiplesAnclas.exec(maqueta))
		{
			checkExecutionTime();
			let res2, anclas = [], match = res[0];
			while(res2 = /ancla *= *(['"]) *(\d+) *\1/.exec(match))
			{
				checkExecutionTime();
				match = match.replace(res2[0],'');
				anclas.push(res2[2]); //Agregar numero de ancla
			}

			match = match.replace(/<(.*?)(\/)?>/,`<$1 ancla='${anclas.join(',')}' $2>`);
			maqueta = maqueta.replace(res[0], match);
		}

		maqueta = this.jqObject.append(maqueta);
		for(let ancla of maqueta.find('[ancla]'))
		{
			for(let i of ancla.attributes.ancla.value.split(','))
			{
				ancla = $(ancla);
				let kind = anchors[i][0];
				let prop = anchors[i][1];
				let stack = new ModuleStack();

				this.hijos.push(stack);
				this.anchors.add(prop,ancla,kind,stack,i,this.clase);
			}
		}
		return;
	}

	getModules()
	{
		let modulos = [];
		for(let anchor of this.anchors.ordered)
			modulos = modulos.concat(anchor.stack.getAll());
		return modulos;
	}

	procesarData(cambios, dirActual)
	{
		if('__delete' in cambios)
			for(let del of cambios.__delete)
				delete dirActual[del];

		if('__modify' in cambios)
			for(let mod in cambios.__modify)
				if(Object.keys(cambios.__modify[mod]).join(',') == '__modify,__delete')
				{
					if(!(mod in dirActual) || typeof dirActual[mod] != typeof [])
						dirActual[mod] = [];
					this.procesarData(cambios.__modify[mod],dirActual[mod]);
				}
				else
					dirActual[mod] = cambios.__modify[mod];
	}

	procesarCambios(cambios,dirActual,sufijo = '')
	{
		if(sufijo != '')
		{
			for(let anchor of this.anchors.get(sufijo))
				anchor.procesar(this.data,cambios);
		}

		if('__modify' in cambios)
			for(let mod in cambios.__modify)
			{
				let nuevoSufijo = `${sufijo}/${mod}`;
				if(nuevoSufijo[0] == '/') nuevoSufijo = nuevoSufijo.substr(1);

				if(Object.keys(cambios.__modify[mod]).join(',') == '__modify,__delete')
					this.procesarCambios(cambios.__modify[mod],dirActual[mod],nuevoSufijo);
				else
				{
					for(let anchor of this.anchors.get(nuevoSufijo))
						anchor.procesar(this.data,cambios);
				}
			}
	}

	procesar(estructura)
	{
		// Primero se procesa, de esa forma cuando se llame a los anchors podran acceder a un array de data actualizado
		this.procesarData(estructura.data, this.data);
		this.procesarCambios(estructura.data, this.data);
		let modulos = this.getModules();
		for(let i in modulos)
		{
			modulos[i].procesar(estructura.hijos[i]);
		}
	}
}