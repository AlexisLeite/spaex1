require 'parsers/foreach.js';
require 'parsers/forrepeater.js';
require 'parsers/modulerepeater.js';
require 'parsers/tablemaker.js';
require 'parsers/if.js';

function getParser(nombre,declaracion, anchor, kind, moduleStack, numeroAncla)
{
	if(`${nombre}_parser` in window)
		return new window[`${nombre}_parser`](declaracion, anchor, kind, moduleStack, numeroAncla);
	return false;
}