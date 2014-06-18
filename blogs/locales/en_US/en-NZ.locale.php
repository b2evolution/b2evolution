<?php
/**
 * Locale definition
 *
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 *
 * Documentation of the keys:
 *  - 'messages': The directory where the locale's files are. (may seem redundant but allows to have fr-FR and fr-CA
 *                tap into the same language file.)
 *  - 'charset':  Character set of the locale's messages files.
 */
$locale_defs['en-NZ'] = array(
		'name' => NT_('English (NZ) latin1'), // New Zealand
		'charset' => 'iso-8859-1',
		'datefmt' => 'd/m/y',
		'timefmt' => 'h:i:s a',
		'startofweek' => 1,
		'messages' => 'en_NZ',
	);
?>