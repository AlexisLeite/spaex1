require 'misc.js';
require 'modulestack.js';
require 'sentenciarepeater.js';
require 'anchor.js';
require 'anchorstack.js';
require 'modulo.js';

var spa;

var cargando = false;
var cuentasCargando = 0;

setInterval(()=>
{
	cuentasCargando++;
	if(cargando && cuentasCargando == 4)
		$('#ErrorDemoraMucho').show();
},2000);

$(document).ready(()=>
{
	spa = new function()
	{
		var modulos = [];
		var moduloRaiz = null;
		var regexLocalDomain = new RegExp('^(?:https?:\\/\\/)?' + Conf.baseUri + 
			'.*/');

		function procesarLocationChange(ev,href,method='get',data={})
		{
			// Aceptar solicitudes solamente desde el dominio actual
			if(!(regexLocalDomain.test(href))) return true;

			cargando = true;
			cuentasCargando = 0;
			$('#Loader').addClass('Abierto');

				$('#ErrorDemoraMucho').find('a').attr('href', href);

			// Ejecutar la accion
			if(ev.currentTarget)
				$(ev.currentTarget).addClass('spa-active');

			// Actualizar historial
			if(ev.type != 'popstate')
				history.pushState({},'',href);
			
			$[method](href+'?spa',data,spa.procesarRespuesta);
			return false;
		}

		function procesarSpa()
		{
			$('a:not(.spa-active)[href]:not([target=\'_blank\'])').each((i,el) =>
			{
				el = $(el);
				el.off('click');
				el.click(function(ev)
				{
					return procesarLocationChange(ev,ev.currentTarget.href);
				})
			});
			$('form:not(.spa-active)[action]').each((i,el) =>
			{
				el = $(el);
				el.off('submit');

				// Definir el method
				let method = el.attr('method');
				method = method ? method : 'get';

				// Definir el action
				let action = el.attr('action');
				action = action ? action : window.location;
				if(!regexLocalDomain.test(action)) action = `${Conf.baseUri}/${action}`;

				// Establecer el evento
				el.submit(function(ev)
				{
					let data = $(this).serialize();
					return procesarLocationChange(ev,action,method,data);
				})
			});
		}

		this.procesarRespuesta = function(respuesta)
		{
			try
			{
				respuesta = JSON.parse(respuesta);
			}
			catch(e)
			{
				clog(e);
				$('body').html(respuesta);
				return;
			}
			if('modulos' in respuesta)
			{
				modulos = modulos.concat(Object.values(respuesta.modulos));
				delete respuesta['modulos'];
			}
			clog(modulos);
			if(moduloRaiz == null)
			{
				let loader = $('#Loader').detach();
				let demoraMucho = $('#ErrorDemoraMucho').detach();
				$('body').empty().append(loader);
				loader.addClass('Minimal')
				demoraMucho.appendTo('body').hide();
				moduloRaiz = new Modulo(respuesta.estructura.src, $('body'));
			}
			restartExecutionTime();
			moduloRaiz.procesar(respuesta.estructura);
			$('#Loader').removeClass('Abierto');
			activarDebugs();
			procesarSpa();
			cargando = false;
		}

		$.get('{baseUri}/?activarSpa',this.procesarRespuesta);

		// Se refiere al codigo fuente de los modulos, la maqueta y el controlador. No a los modulos de la clase Modulo
		this.getModulo = function(clase)
		{
			for(let it of modulos)
				if(it.clase == clase)
					return it;
		}

		window.onpopstate = function(event) {
			procesarLocationChange(event,document.location);
		};

	}
});