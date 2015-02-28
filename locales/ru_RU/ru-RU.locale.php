<?php
/**
 * Locale definition
 *
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 *
 * Documentation of the keys:
 *  - 'messages':            The directory where the locale's files are. (may seem redundant but allows to have fr-FR and fr-CA
 *                           tap into the same language file.)
 *  - 'charset':             Character set of the locale's messages files.
 *  - 'transliteration_map': An array of non-ASCII chars and their transliterated ASCII equivalents.
 *                           It's used in replace_special_chars() to find and replace non-ASCII chars in post and category slugs.
 *                           You can define your own pattern, e.g. to replace "U" with "you" use 'U'=>'you'.
 *                           See /locales/ru_RU/ru-RU.locale.php for details.
 */
$locale_defs['ru-RU'] = array(
		'name' => NT_('Russian (RU) utf-8'),
		'messages' => 'ru_RU',
		'charset' => 'utf-8',
		'datefmt' => 'j.m.Y',
		'timefmt' => 'H:i:s',
		'shorttimefmt' => 'H:i',
		'startofweek' => 1,
		'transliteration_map' => array(
			'А'=>'A', 'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ё'=>'YO', 'Ж'=>'ZH', 'З'=>'Z', 'И'=>'I', 'Й'=>'J',
			'К'=>'K', 'Л'=>'L', 'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R', 'С'=>'S', 'Т'=>'T', 'У'=>'U', 'Ф'=>'F',
			'Х'=>'X', 'Ц'=>'C', 'Ч'=>'CH', 'Ш'=>'SH', 'Щ'=>'SHH', 'Ъ'=>'', 'Ы'=>'Y', 'Ь'=>'', 'Э'=>'E', 'Ю'=>'YU', 'Я'=>'YA',
			'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'yo', 'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'j',
			'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f',
			'х'=>'x', 'ц'=>'c', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'shh', 'ъ'=>'', 'ы'=>'y', 'ь'=>'', 'э'=>'e', 'ю'=>'yu', 'я'=>'ya',
			'Є'=>'YE', 'І'=>'I', 'Ѓ'=>'G', 'і'=>'i', 'ї'=>'ji', 'є'=>'ye', 'ѓ'=>'g',
			'«'=>'', '»'=>'', '—'=>'-', '№'=>'#'
		),
	);
?>