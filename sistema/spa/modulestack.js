// El modulestack sera un contenedor de modulos utilizado por los anchors para crear sus modulos. 
class ModuleStack
{
	constructor()
	{
		this.arr = [];
	}

	// Agregar un modulo al final del stack
	add(modulo)
	{
		this.arr.push(modulo);
	}

	// Agregar un nuevo stack al final del stack, esto permite que los modulos que sean agregados al stack creado se agreguen en un punto intermedio del stack actual. Esto es asi en cuanto al stack actual se agreguen modulos luego de esta llamada. Es decir, al crear un nuevo stack se crea un punto en el array actual en el cual se pueden agregar modulos.
	addStack()
	{
		let nuevoStack = new ModuleStack();
		this.arr.push(nuevoStack);
		return nuevoStack;
	}

	// Elimina un elemento del stack actual; 
	remove(i)
	{
		if(this.arr[i] instanceof ModuleStack)
			this.arr[i].reset();

		this.arr = null;
	}

	// Modifica el indice dado con el nuevo modulo. Si el indice dado es un stack se emitira un error
	modify(i,modulo)
	{
		if(!(this.arr[i] instanceof ModuleStack))
			this.arr[i] = modulo;
		else
			throw new Error(`Se intenta establecer un modulo en un indice de ModuleStack. Indice: ${i}.`);
	}

	// Devuelve todos los modulos agregados.
	getAll()
	{
		let modulos = [];
		for(let i in this.arr)
			if(this.arr[i] instanceof ModuleStack)
				modulos = modulos.concat(this.arr[i].getAll());
			else if(this.arr[i] != null)
				modulos.push(this.arr[i]);
		return modulos;
	}

	// Devuelve el array a 0
	reset()
	{
		for(let i in this.arr)
			if(this.arr[i] instanceof ModuleStack)
				this.arr[i].reset();
		this.arr = [];
	}
}