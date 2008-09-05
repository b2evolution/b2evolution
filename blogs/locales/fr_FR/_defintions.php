<?php
/**
 * Locale definition
 *
 * Defining the locales included in this language pack.
 *
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 *
 * Documentation of the keys:
 *  - 'messages': The directory where the locale's files are. (may seem redundant but allows to have fr-FR and fr-CA
 *                tap into the same language file.)
 *  - 'charset':  Character set of the locale's files.
 */
$locales['fr-FR'] = array(
		'name' => NT_('French (FR)'),
		'messages' => 'fr_FR',
		'charset' => 'iso-8859-1',
		'datefmt' => 'd.m.y',
		'timefmt' => 'H:i:s',
		'startofweek' => 1,
	);

$locales['fr-CA'] = array(
		'name' => NT_('French (CA)'),
		'messages' => 'fr_FR',
		'charset' => 'iso-8859-1',
		'datefmt' => 'm/d/y',
		'timefmt' => 'h:i:s a',
		'startofweek' => 0,
	);

$locales['fr-BE'] = array(
		'name' => NT_('French (BE)'),
		'messages' => 'fr_FR',
		'charset' => 'iso-8859-1',
		'datefmt' => 'd/m/y',
		'timefmt' => 'H:i:s',
		'startofweek' => 1,
	);

?>

