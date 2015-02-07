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
 */
$locale_defs['de-DE'] = array(
		'name' => NT_('German (DE) utf-8'),
		'messages' => 'de_DE',
		'charset' => 'utf-8',
		'datefmt' => 'j.m.Y',
		'timefmt' => 'H:i:s',
		'shorttimefmt' => 'H:i',
		'startofweek' => 1,
		'transliteration_map' => array(),
	);
?>