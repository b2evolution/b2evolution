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
 *  - 'set_locales':
 *    This gets used for {@link setlocale()} (currently only when using gettext support [$use_l10n=1]).
 *    This is a list of locales that get tried. One of them must be available on the system ("locale -a").
 *    If not given, the value of 'messages' gets used. fp> gettext support is deprecated
 */
$locale_defs['de-DE'] = array(
		'name' => NT_('German (DE)'),
		'messages' => 'de_DE',
		'charset' => 'iso-8859-1',
		'datefmt' => 'd.m.y',
		'timefmt' => 'H:i:s',
		'startofweek' => 1,
		'set_locales' => 'de_DE.latin1 de_DE de',  // fp> errrr....
	);
?>