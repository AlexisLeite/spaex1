// throw new Error('No se ejecutara spa');
var comienzoScript = Date.now();
var maxExecutionTime = 8000;
// La funcion restartExecutionTime se utilizara antes de comenzar operaciones complejas que requieran corroborar el tiempo de ejecucion.
function restartExecutionTime()
{
	comienzoScript = Date.now();
}
// La funcion checkExecutionTime sera utilizada dentro de cada bloque que pudiera desatar un bucle infinito para evitar que el navegador se 'cuelgue'
function checkExecutionTime()
{
	if(Date.now() - comienzoScript > maxExecutionTime)  throw new Error(`Tiempo de ejecucion superado. ${Date.now() - comienzoScript}ms transcurridos.`);
}

var Regex = new function()
{
	this.attribute = new RegExp(/(\w+) *= *['"]([\{}\w\-_\d\/]+)['"]/);
	this.onlyVar = new RegExp(/^\{([\w\/]+)}$/)
	this.variables = /\{([\w\/]+)}/;
	this.variablesGlobales = /\{([\w\/]+)}/g;
	// Esta regex captura: 
	// - foreach|forrepeater|if='nombre/var'
	// - {nombre/var}
	// - atributo='{nombre/var}'
	// - modulo='{nombre/var}'
	// - modulo=nombreModulo
	this.anchors = new RegExp(/(?:(tablemaker|foreach|forrepeater|modulerepeater|if) *= *(['"])([\w\/\-_]+)\2|\{([\w\/\-_]+)}|([\w\-\_]+) *= *(['"])([^'"]*\{[^'"]*)\6|(modulo) *= *(['"])([\\\w\/\-_\{}]+)\9)/,'g');
	this.repeaters = new RegExp(/< *(\w+)[^>]*(?:tablemaker|foreach|forrepeater|modulerepeater) *= *(['"])[\w\/]+\2[^>]*>([\s\S]*?)<\/\1>/,'g');
	this.htmlComments = new RegExp(/<!--[\s\S]*?-->\s*/,'g');
}

var Conf = new function()
{
	this.uri = {base: '{uri/base}'};
}

var Basic = new function()
{
	this.compareArrays = function(a1,a2)
	{
		if(a1.length != a2.length) return false;
		for(let i in a1)
			if(a1[i] != a2[i])
				return false;

		return true;
	}
	this.getRoutedValue = function(arr,ruta,conf=false)
	{
		let rutaAux = ruta.split('/');
		let dir;
		while(dir = rutaAux.shift())
		{
			checkExecutionTime();
			if(dir in arr)
				arr = arr[dir];
			else if(conf)
				return '';
			else
				return this.getRoutedValue(Conf,ruta,true);
		}
		return arr;
	}
}