<?php
/**
 * This is b2evolution's localization & language config file
 *
 * This file sets the default configuration for locales.
 * IMPORTANT: Most of these settings can be overriden in the admin (regional settings) and will then
 * be saved to the database. The database settings superseede settings in this file.
 * Last significant changes to this file: version 0.9.0.10
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Enable localization?
 *
 * Set to 0 to disable localization.
 * Set to 1 to enable gettext localization if supported (not recommended).
 *    Note: you will have to compile the .po files with msgfmt before this will work.
 * Set to 2 to enable b2evo advanced localization (recommended).
 *
 * @global integer
 */
$use_l10n = 2;


/**
 * To be used for m17n support.
 *
 * If you don't know, don't change this setting.
 */
$dbcharset = 'iso-8859-1';


/**
 * Default locale used for backoffice (when we cannot autodetect) and fallback.
 * This will be overwritten from database settings, if configured there.
 * These use an ISO 639 language code, a '-' and an ISO 3166 country code.
 *
 * This MUST BE in the list below.
 *
 * @global string
 */
$default_locale = 'en-EU';


/**
 * Load locale related functions: (we need NT_(-) here)
 */
require_once dirname(__FILE__).'/'.$conf_dirout.$core_subdir.'_locale.funcs.php';


/**
 * Defining the locales:
 * These are the default settings.
 * This array will be overwritten from DB if locales are set there,
 * that is when they get updated from the Backoffice.
 * They are also used as fallback, if we have no access to the DB yet.
 * Flag source: http://www.crwflags.com/fotw/flags/iso3166.html
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 */
$locales = array(
	'cs-CZ' => array( 'name' => NT_('Czech (CZ)'),
										'charset' => 'utf-8',
										'datefmt' => 'd. m. y',
										'timefmt' => 'H.i:s',
										'startofweek' => 1,
										'messages' => 'cs_CZ',
										'enabled' => 1,
									),
	'da-DK' => array( 'name' => NT_('Danish (DK)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'da_DK',
										'enabled' => 1,
									),
	'de-DE' => array( 'name' => NT_('German (DE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'de_DE',
										'enabled' => 1,
									),
	'en-EU' => array( 'name' => NT_('English (EU)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'en_EU',
										'enabled' => 1,
									),
	'en-UK' => array( 'name' => NT_('English (UK)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 1,
										'messages' => 'en_UK',
										'enabled' => 1,
									),
	'en-US' => array( 'name' => NT_('English (US)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 0,
										'messages' => 'en_US',
										'enabled' => 1,
									),
	'en-CA' => array( 'name' => NT_('English (CA)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 0,
										'messages' => 'en_CA',
										'enabled' => 1,
									),
	'en-AU' => array( 'name' => NT_('English (AU)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 1,
										'messages' => 'en_AU',
										'enabled' => 1,
									),
	'en-IL' => array( 'name' => NT_('English (IL)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'en_IL',
										'enabled' => 1,
									),
	'en-NZ' => array( 'name' => NT_('English (NZ)'), // New Zealand
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 1,
										'messages' => 'en_NZ',
										'enabled' => 1,
									),
	'en-SG' => array( 'name' => NT_('English (SG)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s a',
										'startofweek' => 0,
										'messages' => 'en_SG',
										'enabled' => 1,
									),
	'es-ES' => array( 'name' => NT_('Spanish (ES)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'es_ES',
										'enabled' => 1,
									),
	'es-VE' => array( 'name' => NT_('Spanish (VE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/Y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 1,
										'messages' => 'es_VE',
										'enabled' => 1,
									),
	'fi-FI' => array( 'name' => NT_('Finnish (FI)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.Y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'fi_FI',
										'enabled' => 1,
									),
	'fr-FR' => array( 'name' => NT_('French (FR)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'fr-CA' => array( 'name' => NT_('French (CA)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'startofweek' => 0,
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'fr-BE' => array( 'name' => NT_('French (BE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'fr_FR',
										'enabled' => 1,
									),
	'hu-HU' => array( 'name' => NT_('Hungarian (HU)'),
										'charset' => 'iso-8859-2',
										'datefmt' => 'Y. M. d.',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'hu_HU',
										'enabled' => 1,
									),
	'it-IT' => array( 'name' => NT_('Italian (IT)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'it_IT',
										'enabled' => 1,
									),
	'ja-JP' => array( 'name' => NT_('Japanese (JP)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y/m/d',
										'timefmt' => 'H:i:s',
										'startofweek' => 0,
										'messages' => 'ja_JP',
										'enabled' => 1,
									),
	'lt-LT' => array( 'name' => NT_('Lithuanian (LT)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'lt_LT',
										'enabled' => 1,
									),
	'nb-NO' => array( 'name' => NT_('Bokm&aring;l (NO)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'nb_NO',
										'enabled' => 1,
									),
	'nl-NL' => array( 'name' => NT_('Dutch (NL)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd-m-y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'nl_NL',
										'enabled' => 1,
									),
	'nl-BE' => array( 'name' => NT_('Dutch (BE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd/m/y',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'nl_NL',
										'enabled' => 1,
									),
	'pt-BR' => array( 'name' => NT_('Portuguese (BR)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'd.m.y',
										'timefmt' => 'H:i:s',
										'startofweek' => 0,
										'messages' => 'pt_BR',
										'enabled' => 1,
									),
	'sk-SK' => array( 'name' => NT_('Slovak (SK)'),
										'charset' => 'utf-8',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'sk_SK',
										'enabled' => 1,
									),
	'sv-SE' => array( 'name' => NT_('Swedish (SE)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'sv_SE',
										'enabled' => 1,
									),
	'zh-CN' => array( 'name' => NT_('Chinese(S) gb2312 (CN)'),
										'charset' => 'gb2312',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'zh_CN',
										'enabled' => 1,
									),
	'zh-CN-utf-8' => array( 'name' => NT_('Chinese(S) utf-8 (CN)'),
										'charset' => 'utf-8',
										'datefmt' => 'y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 1,
										'messages' => 'zh_CN_utf-8',
										'enabled' => 1,
									),
	'zh-TW' => array( 'name' => NT_('Trad. Chinese (TW)'),
										'charset' => 'utf-8',
										'datefmt' => 'Y-m-d',
										'timefmt' => 'H:i:s',
										'startofweek' => 0,
										'messages' => 'zh_TW',
										'enabled' => 1,
									),
);


/**
 * Set this to 1 if you are a translator and wish to extract strings from your .po file.
 * Warning: do *not* extract .PO files you have not edited yourself.
 * Shipped .PO files contain automatic translations that have *not* been reviewed.
 *
 * @global boolean
 */
$allow_po_extraction = 0;

?>