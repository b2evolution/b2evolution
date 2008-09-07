<?php
/**
 * This is b2evolution's localization & language config file
 *
 * This file sets the default configuration for locales.
 * IMPORTANT: Most of these settings can be overriden in the admin (regional settings) and will then
 * be saved to the database. The database settings superseede settings in this file.
 * Last significant changes to this file: version 1.6
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
 *          Also, the locale information has to be available on the system ("locale -a" with Unix)
 * Set to 2 to enable b2evo advanced localization (recommended).
 *
 * @global integer
 */
$use_l10n = 2;


/**
 * The internal charset. It's used to convert user INPUT/OUTPUT and database data into for
 * internal use.
 *
 * Setting it to an empty string means "follow the user's charset", which gets
 * taken off his locale (INPUT/OUTPUT charset; {@link $io_charset}).
 *
 * If you don't know, don't change this setting.
 *
 * This should be supported by {@link mb_list_encodings()}.
 */
$evo_charset = '';


/**
 * Set this to a specific charset, to force this as {@link $io_charset I/O charset},
 * if the browser accepts it.
 *
 * Setting this to "utf-8" allows you to deliver all pages in this encoding.
 *
 * NOTE: make sure, that your PHP/MySQL setup supports this. You most probably need
 *       the mbstring PHP extension and MySQL 4.1 for this to work.
 *
 * @global string
 */
$force_io_charset_if_accepted = '';


/**
 * Request a specific charset for the client connection.
 *
 * This will issue a MySQL SET NAMES command. This must be a MySQL charset. Example: 'latin1' or 'utf8'
 *
 * If left empty, the default charset will be used. The default here is
 * the default set your MySQL Server.
 *
 * This should match the charset you are using internally in b2evolution.
 * This allows b2evo to work internally in a different charset from the database charset.
 * Example: b2evo will use latin1 whereas the database uses utf8.
 *
 * TODO: This gets overridden anyway with "SET NAMES $evo_charset" in init_charsets() and gets only used until that!
 *       So, does it make sense to configure it here? Or shouldn't it get overridden, if set explicitly here?
 */
$db_config['connection_charset'] = '';


/**
 * Default locale used for backoffice (when we cannot autodetect) and fallback.
 * This will be overwritten from database settings, if configured there.
 * These use an ISO 639 language code, a '-' and an ISO 3166 country code.
 *
 * This MUST BE in the list below.
 *
 * @global string
 */
$default_locale = 'en-US';


/**
 * Load locale related functions: (we need NT_() here)
 */
require_once $inc_path.'locales/_locale.funcs.php';


/**
 * Defining the locales:
 * These are the default settings.
 * This array will be overwritten from DB if locales are set there,
 * that is when they get updated from the Backoffice.
 * They are also used as fallback, if we have no access to the DB yet.
 * Flag source: http://www.crwflags.com/fotw/flags/iso3166.html
 * IMPORTANT: Try to keep the locale names short, they take away valuable space on the screen!
 *
 * Documentation of the keys:
 *  - 'messages':
 *    The directory where the locale's files are.
 *  - 'charset':
 *    Character set of the locale's files.
 *
 * @todo Locale message dirs should be named LOCALE.CHARSET and not LOCALE_CHARSET, e.g. "zh_CN.utf8" instead of "zh_CN_utf-8" (according to gettext)
 * @todo fp>Actually, the default locale setting should move to install and we should always use the database after that. What were we smoking when we did that? :P
 */
$locales['en-US'] = array(
		'name' => NT_('English (US)'),
		'charset' => 'iso-8859-1',
		'datefmt' => 'm/d/y',
		'timefmt' => 'h:i:s a',
		'startofweek' => 0,
		'messages' => 'en_US',
		'enabled' => 1,
	);
$locales['en-GB'] = array(
		'name' => NT_('English (GB)'), // correct ISO-3166 code
		'charset' => 'iso-8859-1',
		'datefmt' => 'd/m/y',
		'timefmt' => 'h:i:s a',
		'startofweek' => 1,
		'messages' => 'en_GB',
		'enabled' => 1,
	);

/**
 * Set this to 1 if you are a translator and wish to extract strings from your .po file.
 * Warning: do *not* extract .PO files you have not edited yourself.
 * Shipped .PO files contain automatic translations that have *not* been reviewed.
 *
 * @todo fp>This should be moved to the backoffice.
 *
 * @global boolean
 */
$allow_po_extraction = 0;

?>