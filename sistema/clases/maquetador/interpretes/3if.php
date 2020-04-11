<?php
namespace Interpretes;

class InterpreteIf
{
	public static function parse($valor,&$elemento,$data)
	{
		$elseSentence = $elemento->children('else')->html();
		$elemento->children('else')->remove();

		$valor = \Basic::getRoutedValue($data,$valor);

		if($valor != 1 && ($valor===null || !$valor))
		{
			if(strlen($elseSentence))
				$elemento->empty()->append($elseSentence);
			else
				$elemento->remove();
		}

	}
}

?>