var tableMakerTablesIndex = 0;
var tableMakerRolesIndex = 0;
var tableMakerRowsIndex = 0;
var tableMakerNodesIndex = 0;

class TableNode
{
	jq()
	{
		return $(`[node='${this.index}']`);
	}

	constructor(cambios, row, data)
	{
		this.mode = 'value';
		this.href = '';
		this.title = '';
		this.text = '';

		if(typeof cambios == typeof {})
			this.kind = ('th' in cambios.__modify) && cambios.__modify.th ? 'th' : 'td';
		else
			this.kind = 'td';

		this.index = tableMakerNodesIndex++;
		$(`<${this.kind} node='${this.index}' />`).appendTo(row);

		this.procesar(cambios, data);
	}
	// TABLE NODE
	procesar(cambios, data)
	{
		let nuevoValor = false;
		if(typeof cambios == typeof {})
		{
			let knownProps = ['mode','th','text','href','title'];
			cambios = cambios.__modify;

			for(let prop in cambios)
			{
				if(!knownProps.includes(prop) && isNaN(parseInt(prop)))
				{
					this.jq().attr(prop,cambios[prop]);
					delete(cambios[prop]);
				}
			}

			for(let known of knownProps)
				if(known in cambios)
					this[known] = cambios[known];

			switch(this.mode)
			{
				case 'value':
					nuevoValor = this.text;
					break;

				case 'href':
					nuevoValor = `<a title='${this.title}' href='${this.href}'>${this.text}</a>`;
					break;
			}

			// parse value against data
		}
		else
			nuevoValor = cambios;

		if(nuevoValor)
		{
			let res;
			while(res = nuevoValor.match(Regex.variables))
				nuevoValor = nuevoValor.replace(res[0],Basic.getRoutedValue(data,res[1]));

			this.jq().html(nuevoValor);
		}
	}

	remove()
	{
		this.jq().remove();
	}
}

class TableRow
{
	jq()
	{
		return $(`[row='${this.index}']`);
	}

	makeNode(index,cambios, data)
	{
		this.nodes[index] = new TableNode(cambios,this.jq(), data);
	}

	constructor(cambios,role, data)
	{
		this.nodes = [];
		this.index = tableMakerRowsIndex++;

		$(`<tr row='${this.index}' />`).appendTo(role);

		this.procesar(cambios, data);
	}
	// TABLE ROW
	procesar(cambios, data)
	{
		if('__modify' in cambios)
		{
			for(let prop in cambios.__modify)
			{
				if(!['nodes'].includes(prop) && isNaN(parseInt(prop)))
				{
					this.jq().attr(prop, cambios.__modify[prop]);
					delete(cambios.__modify[prop]);
				}
			}

			if('nodes' in cambios.__modify)
				cambios = cambios.__modify.nodes;

			if('__delete' in cambios)
				for(let del in cambios.__delete)
				{
					if(cambios.__delete[del] == 'nodes')
					{
						for(let nodo of this.nodes)
							nodo.remove();
						this.nodes = [];
						break;
					}
					this.nodes[cambios.__delete[del]].remove();
					delete this.nodes[cambios.__delete[del]];
				}

			for(let mod in cambios.__modify)
				if(mod in this.nodes)
					this.nodes[mod].procesar(cambios.__modify[mod], data);
				else
					this.makeNode(mod,cambios.__modify[mod], data);
		}
	}

	remove()
	{
		this.jq().remove();
		this.nodes = [];
	}
}

class TableRole
{
	jq()
	{
		return $(`[rol='${this.index}']`);
	}

	constructor(cambios,table, data)
	{
		this.rows = [];
		this.index = tableMakerRolesIndex++;
		this.role = cambios.__modify.role;

		$(`<${this.role} rol='${this.index}' />`).appendTo(table);

		this.procesar(cambios, data);
	}

	makeRow(index,cambios, data)
	{
		this.rows[index] = new TableRow(cambios,this.jq(), data);
	}
	// TABLE ROLE
	procesar(cambios, data)
	{
		if('__modify' in cambios)
		{
			for(let prop in cambios.__modify)
			{
				if(!['rows','role'].includes(prop) && isNaN(parseInt(prop)))
				{
					this.jq().attr(prop, cambios.__modify[prop]);
					delete(cambios.__modify[prop]);
				}
			}

			if('role' in cambios.__modify)
				delete cambios.__modify['role'];

			if('rows' in cambios.__modify)
				cambios = cambios.__modify.rows;

			if('__delete' in cambios)
				for(let del in cambios.__delete)
				{
					if(cambios.__delete[del] == 'rows')
					{
						for(let row of this.rows)
							row.remove();
						this.rows = [];
						break;
					}
					if(cambios.__delete[del] in this.rows)
					{
						this.rows[cambios.__delete[del]].remove();
						delete this.rows[cambios.__delete[del]];
					}
				}

			for(let mod in cambios.__modify)
			{
				if(mod in this.rows)
					this.rows[mod].procesar(cambios.__modify[mod], data);
				else
					this.makeRow(mod,cambios.__modify[mod], data);
			}
		}
	}

	remove()
	{
		this.jq().remove();
		this.rows = [];
	}
}

class Table
{
	jq()
	{
		return $(`[table='${this.index}']`);
	}

	makeRole(index, cambios, data)
	{
		this.roles[index] = new TableRole(cambios,this.jq(), data);
	}

	procesar(cambios, data)
	{
		if(this.roleMode)
		{
			if('__delete' in cambios)
				for(let del in cambios.__delete)
				{
					if(cambios.__delete[del] in this.roles)
					{
						this.roles[cambios.__delete[del]].remove();
						delete this.roles[cambios.__delete[del]];
					}
				}

			if('__modify' in cambios)
			{
				for(let prop in cambios.__modify)
				{
					if(typeof cambios.__modify[prop] == typeof 'a')
						this.jq().attr(prop,cambios.__modify[prop]);
				}

				for(let mod in cambios.__modify)
				{
					if(typeof cambios.__modify[mod] == typeof 'a') continue;

					if(mod in this.roles)
						this.roles[mod].procesar(cambios.__modify[mod], data);
					else
						this.makeRole(mod,cambios.__modify[mod], data);
				}
			}
		}
		else
			this.roles[0].procesar({rows: cambios}, data);
	}

	constructor(anchor, cambios, data)
	{
		this.roleMode = false;

		// Determinar modo role o no
		let primerCambioProcesado = false;
		for(let mod in cambios.__modify)
		{
			if(typeof cambios.__modify[mod] != typeof 'a' && ('role' in cambios.__modify[mod].__modify && this.roleMode) 
				|| (typeof cambios.__modify[mod] != typeof 'a' && 'role' in cambios.__modify[mod].__modify && !primerCambioProcesado))
				this.roleMode = true;
			else if(typeof cambios.__modify[mod] != typeof 'a' && 'role' in cambios.__modify[mod].__modify)
				throw new Error('Error en la estructura de la tabla. O todos los nodos primarios ocupan un rol o ninguno lo hace. Ver manual.');

			primerCambioProcesado = true;
		}

		// Se establece el atributo tabla para poder luego encontrar la tabla en el dom
		this.index = tableMakerTablesIndex++;
		$(`<table table='${this.index}' />`).appendTo(anchor);

		this.roles = [];
		if(!this.roleMode)
			this.makeRole({role: 'tbody'}, data);

		this.procesar(cambios, data);
	}
}

var tablemaker_parser = function(declaracion, anchor, kind, moduleStack, numeroAncla)
{
	this.declaracion = declaracion;
	this.kind = kind;
	this.stack = moduleStack;
	this.propiedadesDepende = [];
	this.numeroAncla = numeroAncla;

	this.propiedadesDepende.push(declaracion);

	anchor.empty();
	this.tabla = null;

	this.procesar = function(data,cambios,anchor)
	{
		if(!this.tabla)
			this.tabla = new Table(anchor,cambios,data);
		else
			this.tabla.procesar(cambios,data);
	}
}