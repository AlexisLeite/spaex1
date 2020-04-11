var clog = (...args) => { console.log(...args) };

if (!Array.isArray) {
  Array.isArray = function(arg) {
    return Object.prototype.toString.call(arg) === '[object Array]';
  };
}

function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

if(!getCookie('DebugerCliente'))
	setCookie('DebugerCliente','[]',1);

function activarDebugs()
{
	$cookies = JSON.parse(getCookie('DebugerCliente'));

	if(getCookie('DebugerAbierto') == 'true')
	{
		$('#Debugs').addClass('Abierto');
		$('h3.debug').each((i,el) =>
		{
			if($cookies.includes(el.innerHTML))
				$(el).next('pre').addClass('Abierto');
		});
	}

	$('#Debugs').prependTo('body');

	$('#Debugs').off('click');
	$('#Debugs h3.debug').off('click');

	$('#Debugs').click(el=>
	{
		$(el.currentTarget).toggleClass('Abierto');
		setCookie('DebugerAbierto', true, $(el.currentTarget).hasClass('Abierto'));
		if(!$(el.currentTarget).hasClass('Abierto'))
			$('h3.debug').each((i,el) =>
			{
				$(el).next('pre').removeClass('Abierto');
			});
		else
			$('h3.debug').each((i,el) =>
			{
				let cookies = JSON.parse(getCookie('DebugerCliente'));
				if(cookies.indexOf(el.innerHTML) >= 0)
					$(el).next('pre').addClass('Abierto');
			});
	});
	$('#Debugs h3.debug').click((el)=>
	{
		let cookies = JSON.parse(getCookie('DebugerCliente'));
		let hasClass = $(el.currentTarget).next('pre').toggleClass('Abierto').hasClass('Abierto');
		let propiedad = el.currentTarget.innerHTML;

		if(cookies.includes(propiedad) && !hasClass)
			cookies.splice(cookies.indexOf(propiedad),1);
		if(!cookies.includes(propiedad) && hasClass)
			cookies.push(propiedad);

		setCookie('DebugerCliente', JSON.stringify(cookies), 1);
		return false;
	});
}