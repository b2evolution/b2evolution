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
$locale_defs['fr-CA'] = array(
		'name' => NT_('French (CA) utf-8'),
		'messages' => 'fr_FR',
		'charset' => 'utf-8',
		'datefmt' => 'm/d/y',
		'longdatefmt' => 'm/d/Y',
		'extdatefmt' => 'M d, Y',
		'input_datefmt' => 'm/d/y',
		'timefmt' => 'h:i:s a',
		'shorttimefmt' => 'h:i a',
		'input_timefmt' => 'H:i:s',
		'startofweek' => 0,
		'transliteration_map' => array(),
	);
?>