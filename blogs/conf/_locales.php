<?php
/**
 * This is b2evolution's localization & language config file
 *
 * This file sets the default configuration for locales.
 * IMPORTANT: Most of these settings can be overriden in the admin (regional settings) and will then
 * be saved to the database. The database settings superseede settings in this file.
 * Last significant changes to this file: version 0.9.0.5
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

# Enable localization?
# set to 0 to disable localization
# set to 1 to enable gettext localization if supported (not recommended)
#		note: you will have to compile the .po files with msgfmt before this will work.
# set to 2 to enable b2evo advanced localization (recommended)
$use_l10n = 2;

# To be used for m17n support:
$dbcharset = 'iso-8859-1';		// If you don't know, don't change this setting.


# Default locale used for backoffice (when we cannot autodetect) and fallback.
# This will be overwritten from database settings, if configured there.
# These use an ISO 639 language code, a '-' and an ISO 3166 country code.
# This MUST BE in the list below.
$default_locale = 'en-EU';


/**
 * Load locale related functions: (we need NT_(-) here)
 */
require_once( dirname(__FILE__). "/$conf_dirout/$core_subdir/_functions_locale.php" );


//{{{ defining the locales:
# These are the default settings.
# This array will be overwritten from DB if locales are set there,
# that is when they get updated from the Backoffice.
# They are also used as fallback, if we have no access to the DB yet.
# Flag source: http://www.crwflags.com/fotw/flags/iso3166.html
# IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
$locales = array(
	'cs-CZ' => array(	'name' => NT_('Czech (CZ)'),
										'charset' => 'utf-8',
										'datefmt' => 'd. m. y',
										'timefmt' => 'H.i:s',
										'messages' => 'cs_CZ',
										'enabled' => 1,
									),
	'de-DE' => array(	'name' => NT_('German (DE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'de_DE',
										'enabled' => 1,
									),
	'en-EU' => array( 'name' => NT_('English (EU)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'messages' => 'en_EU',
										'enabled' => 1,
									),
	'en-UK' => array( 'name' => NT_('English (UK)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'en_UK',
										'enabled' => 1,
									),
	'en-US' => array( 'name' => NT_('English (US)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'en_US',
										'enabled' => 1,
									),
	'en-CA' => array( 'name' => NT_('English (CA)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'en_CA',
										'enabled' => 1,
									),
	'en-AU' => array( 'name' => NT_('English (AU)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'en_AU',
										'enabled' => 1,
									),
	'es-ES' => array(	'name' => NT_('Spanish (ES)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'es_ES',
										'enabled' => 1,
									),
	'fr-FR' => array(	'name' => NT_('French (FR)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'fr-CA' => array( 'name' => NT_('French (CA)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'fr-BE' => array(	'name' => NT_('French (BE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s',
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'it-IT' => array(	'name' => NT_('Italian (IT)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'it_IT',
										'enabled' => 1,
									),
	'ja-JP' => array(	'name' => NT_('Japanese (JP)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y/m/d',
										'timefmt' => 'H:i:s',
										'messages' => 'ja_JP',
										'enabled' => 1,
									),
	'lt-LT' => array( 'name' => NT_('Lithuanian (LT)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'messages' => 'lt_LT',
										'enabled' => 1,
									),
	'nb-NO' => array( 'name' => NT_('Bokm&aring;l (NO)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'nb_NO',
										'enabled' => 1,
									),
	'nl-NL' => array(	'name' => NT_('Dutch (NL)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd-m-y',
										'timefmt' => 'H:i:s',
										'messages' => 'nl_NL',
										'enabled' => 1,
									),
	'nl-BE' => array(	'name' => NT_('Dutch (BE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s',
										'messages' => 'nl_NL',
										'enabled' => 1,
									),
	'pt-BR' => array(	'name' => NT_('Portuguese (BR)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'messages' => 'pt_BR',
										'enabled' => 1,
									),
	'sv-SE' => array(	'name' => NT_('Swedish (SE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'messages' => 'sv_SE',
										'enabled' => 1,
									),
	'zh-CN' => array( 'name' => NT_('Simpl. Chinese (CN)'),
										'charset' => 'gb2312',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'messages' => 'zh_CN',
										'enabled' => 1,
									),
	'zh-TW' => array(	'name' => NT_('Trad. Chinese (TW)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'messages' => 'zh_TW',
										'enabled' => 1,
									),
);
//}}}

# Default language (ISO code)
# We get this one from the default locale above
#$default_language = substr( $default_locale, 0, 2 );

# day at the start of the week: 0 for Sunday, 1 for Monday, 2 for Tuesday, etc
$start_of_week = 1;

# Set this to 1 if you are a translator and wish to extract strings from your .po file
# Warning: do *not* extract .PO files you have not edited yourself. 
# Shipped .PO files contain automatic translations that have *not* been reviewed.
$allow_po_extraction = 0;

?>