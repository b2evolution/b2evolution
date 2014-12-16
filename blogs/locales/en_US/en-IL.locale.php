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
$locale_defs['en-IL'] = array(
		'name' => NT_('English (IL) utf-8'),
		'charset' => 'utf-8',
		'datefmt' => 'Y-m-d',
		'timefmt' => 'H:i:s',
		'shorttimefmt' => 'H:i',
		'startofweek' => 1,
		'messages' => 'en_US',
	);
?>