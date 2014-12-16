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
$locale_defs['fr-BE'] = array(
		'name' => NT_('French (BE) utf-8'),
		'messages' => 'fr_FR',
		'charset' => 'utf-8',
		'datefmt' => 'd/m/y',
		'timefmt' => 'H:i:s',
		'shorttimefmt' => 'H:i',
		'startofweek' => 1,
		'transliteration_map' => array(),
	);
?>