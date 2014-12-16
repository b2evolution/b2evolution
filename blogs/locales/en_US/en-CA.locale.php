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
$locale_defs['en-CA'] = array(
		'name' => NT_('English (CA) utf-8'),
		'charset' => 'utf-8',
		'datefmt' => 'm/d/y',
		'timefmt' => 'h:i:s a',
		'shorttimefmt' => 'h:i a',
		'startofweek' => 0,
		'messages' => 'en_US',
	);
?>