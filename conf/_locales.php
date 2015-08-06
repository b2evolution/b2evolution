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
 * No Translation. Does nothing.
 *
 * Nevertheless, the string will be extracted by the gettext tools
 */
if( ! function_exists('NT_') )
{	// A workaround for 'reset' action in locales.ctrl.php
	function NT_( $string )
	{
		return $string;
	}
}


/**
 * Enable localization?
 *
 * Set to 0 to disable localization.
 * Set to 1 to enable localization.
 *
 * @global integer
 */
$use_l10n = 1;


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
$evo_charset = 'utf-8'; // Set utf-8 because we started to use utf-8 internally with the Portable UTF-8 library

if( version_compare( phpversion(), '5.6', '>=' ) )
{	// In case of php version greater than 5.6 set the default charset to UTF-8
	// All other charsets ( inconv, mbstring and php internal functions ) default value is based on the 'default_charset'
	ini_set( 'default_charset', 'UTF-8' );
}


/**
 * Set this to a specific charset, to force this as {@link $io_charset I/O charset}, if the browser accepts it.
 *
 * DO NOT CHANGE THIS if your language requires UTF8 (East Asian, Arabic, etc, etc, etc) !!!
 * This is NOT the correct way to do it. If you change this setting it may look like it works but YOU WILL HAVE ISSUES!
 * The correct way to use UTF8 for some languages/locales is to install the appropriate language pack into the locales folder.
 * Language packs can be downloaded here: http://b2evolution.net/downloads/language-packs.html
 *
 * If your language is not available, you can create your own (you may use /locales/ru-RU as a model)
 * OR... WORST CASE SCENARIO: you can always use the en-US-utf8 locale: "English (US) utf8"
 *
 * Please share new language packs with the community.
 *
 * Setting this to "utf-8" allows you to deliver all pages in this encoding even if the selected locale was not
 * translated to utf-8. Typically requires MBSTRING. Make sure, that your PHP/MySQL setup supports this.
 *
 * @global string
 */
$force_io_charset_if_accepted = 'utf-8'; // Temporary solution to force here the io-charset to utf-8


/**
 * This variable is included here for documentation only.
 *
 * If not empty, this will issue a mysqli::set_charset() command.
 * This must be a MySQL charset. Example: 'latin1' or 'utf8'
 * fp> Actually, DB::set_connection_charset(x,true) can convert from 'iso-8859-1' to 'latin1' for example.
 *
 * If left empty, the default charset will be used. The default here is the default set your MySQL Server.
 *
 * NOTE: in any case, this will be OVERRIDDEN by init_charsets() when initializing a locale.
 *
 * This should match the charset you are using internally in b2evolution.
 * This allows b2evo to work internally in a different charset from the database charset.
 * Example: b2evo will use latin1 whereas the database uses utf8.
 */
$db_config['connection_charset'] = '';


/**
 * Default locale used for backoffice (when we cannot autodetect) and fallback.
 * This will be overwritten from database settings, if configured there.
 * These use an ISO 639 language code, a '-' and an ISO 3166 country code.
 *
 * This MUST BE in the list below.
 *
 * @todo this should actually be used by the installer only. After that we should use the value from the DB.
 *
 * @global string
 */
$default_locale = 'en-US';


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
		'name' => NT_('English (US) utf-8'),
		'charset' => 'utf-8',
		'datefmt' => 'm/d/y',
		'timefmt' => 'h:i:s a',
		'shorttimefmt' => 'h:i a',
		'startofweek' => 0,
		'messages' => 'en_US',
		'enabled' => false,	// We need this line to prevent notices iin locales conf screen and user profile screen.
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


/**
 * Background position for each country by code
 * The sprite image file is located: "/rsc/icons/flags_sprite.png"
 * **** To change this file use the original file from: "/rsc/icons/src/flags.PSD"
 * **** You should save the changed file as PNG-8 with 256 colors to the "flags_sprite.png"
 * **** Also don't forget to save the "flags.PSD"
 *
 * NEW ADDED FLAGS:
 *   aq - Antarctica
 *   ax - Aland Islands
 *   bl - Saint Barthelemy
 *   cc - Cocos Islands
 *   cx - Christmas Island
 *   sj - Svalbard And Jan Mayen
 *
 * CHANGED FLAGS TO 16px WIDTH:
 *   np - Nepal
 *   ch - Switzerland
 */
$country_flags_bg = array(
	'ad' => '-16px 0',
	'ae' => '-32px 0',
	'af' => '-48px 0',
	'ag' => '-64px 0',
	'ai' => '-80px 0',
	'al' => '-96px 0',
	'am' => '-112px 0',
	'an' => '-128px 0',
	'ao' => '-144px 0',
	'aq' => '-96px -165px',
	'ar' => '-160px 0',
	'as' => '-176px 0',
	'at' => '-192px 0',
	'au' => '-208px 0',
	'aw' => '-224px 0',
	'ax' => '-112px -165px',
	'az' => '-240px 0',
	'ba' => '0 -11px',
	'bb' => '-16px -11px',
	'bd' => '-32px -11px',
	'be' => '-48px -11px',
	'bf' => '-64px -11px',
	'bg' => '-80px -11px',
	'bh' => '-96px -11px',
	'bi' => '-112px -11px',
	'bj' => '-128px -11px',
	'bl' => '-128px -165px',
	'bm' => '-144px -11px',
	'bn' => '-160px -11px',
	'bo' => '-176px -11px',
	'br' => '-192px -11px',
	'bs' => '-208px -11px',
	'bt' => '-224px -11px',
	'bv' => '-240px -11px',
	'bw' => '0 -22px',
	'by' => '-16px -22px',
	'bz' => '-32px -22px',
	'ca' => '-48px -22px',
	'cc' => '-144px -165px',
	'ct' => '-64px -22px',
	'cd' => '-80px -22px',
	'cf' => '-96px -22px',
	'cg' => '-112px -22px',
	'ch' => '-128px -22px',
	'ci' => '-144px -22px',
	'ck' => '-160px -22px',
	'cl' => '-176px -22px',
	'cm' => '-192px -22px',
	'cn' => '-208px -22px',
	'co' => '-224px -22px',
	'cr' => '-240px -22px',
	'cu' => '0 -33px',
	'cv' => '-16px -33px',
	'cx' => '-160px -165px',
	'cy' => '-32px -33px',
	'cz' => '-48px -33px',
	'de' => '-64px -33px',
	'dj' => '-80px -33px',
	'dk' => '-96px -33px',
	'dm' => '-112px -33px',
	'do' => '-128px -33px',
	'dz' => '-144px -33px',
	'ec' => '-160px -33px',
	'ee' => '-176px -33px',
	'eg' => '-192px -33px',
	'eh' => '-208px -33px',
	'england' => '-224px -33px',
	'er' => '-240px -33px',
	'es' => '0 -44px',
	'et' => '-16px -44px',
	'eu' => '-32px -44px',
	'fi' => '-48px -44px',
	'fj' => '-64px -44px',
	'fk' => '-80px -44px',
	'fm' => '-96px -44px',
	'fo' => '-112px -44px',
	'fr' => '-128px -44px',
	'ga' => '-144px -44px',
	'gb' => '-160px -44px',
	'gd' => '-176px -44px',
	'ge' => '-192px -44px',
	'gf' => '-208px -44px',
	'gg' => '-224px -44px',
	'gh' => '-240px -44px',
	'gi' => '0 -55px',
	'gl' => '-16px -55px',
	'gm' => '-32px -55px',
	'gn' => '-48px -55px',
	'gp' => '-64px -55px',
	'gq' => '-80px -55px',
	'gr' => '-96px -55px',
	'gs' => '-112px -55px',
	'gt' => '-128px -55px',
	'gu' => '-144px -55px',
	'gw' => '-160px -55px',
	'gy' => '-176px -55px',
	'hk' => '-192px -55px',
	'hm' => '-208px -55px',
	'hn' => '-224px -55px',
	'hr' => '-240px -55px',
	'ht' => '0 -66px',
	'hu' => '-16px -66px',
	'id' => '-32px -66px',
	'ie' => '-48px -66px',
	'il' => '-64px -66px',
	'im' => '-80px -66px',
	'in' => '-96px -66px',
	'io' => '-112px -66px',
	'iq' => '-128px -66px',
	'ir' => '-144px -66px',
	'is' => '-160px -66px',
	'it' => '-176px -66px',
	'je' => '-192px -66px',
	'jm' => '-208px -66px',
	'jo' => '-224px -66px',
	'jp' => '-240px -66px',
	'ke' => '0 -77px',
	'kg' => '-16px -77px',
	'kh' => '-32px -77px',
	'ki' => '-48px -77px',
	'km' => '-64px -77px',
	'kn' => '-80px -77px',
	'kp' => '-96px -77px',
	'kr' => '-112px -77px',
	'kw' => '-128px -77px',
	'ky' => '-144px -77px',
	'kz' => '-160px -77px',
	'la' => '-176px -77px',
	'lb' => '-192px -77px',
	'lc' => '-208px -77px',
	'li' => '-224px -77px',
	'lk' => '-240px -77px',
	'lr' => '0 -88px',
	'ls' => '-16px -88px',
	'lt' => '-32px -88px',
	'lu' => '-48px -88px',
	'lv' => '-64px -88px',
	'ly' => '-80px -88px',
	'ma' => '-96px -88px',
	'mc' => '-112px -88px',
	'md' => '-128px -88px',
	'me' => '-144px -88px',
	'mg' => '-160px -88px',
	'mh' => '-176px -88px',
	'mk' => '-192px -88px',
	'ml' => '-208px -88px',
	'mm' => '-224px -88px',
	'mn' => '-240px -88px',
	'mo' => '0 -99px',
	'mp' => '-16px -99px',
	'mq' => '-32px -99px',
	'mr' => '-48px -99px',
	'ms' => '-64px -99px',
	'mt' => '-80px -99px',
	'mu' => '-96px -99px',
	'mv' => '-112px -99px',
	'mw' => '-128px -99px',
	'mx' => '-144px -99px',
	'my' => '-160px -99px',
	'mz' => '-176px -99px',
	'na' => '-192px -99px',
	'nc' => '-208px -99px',
	'ne' => '-224px -99px',
	'nf' => '-240px -99px',
	'ng' => '0 -110px',
	'ni' => '-16px -110px',
	'nl' => '-32px -110px',
	'no' => '-48px -110px',
	'np' => '-64px -110px',
	'nr' => '-80px -110px',
	'nu' => '-96px -110px',
	'nz' => '-112px -110px',
	'om' => '-128px -110px',
	'pa' => '-144px -110px',
	'pe' => '-160px -110px',
	'pf' => '-176px -110px',
	'pg' => '-192px -110px',
	'ph' => '-208px -110px',
	'pk' => '-224px -110px',
	'pl' => '-240px -110px',
	'pm' => '0 -121px',
	'pn' => '-16px -121px',
	'pr' => '-32px -121px',
	'ps' => '-48px -121px',
	'pt' => '-64px -121px',
	'pw' => '-80px -121px',
	'py' => '-96px -121px',
	'qa' => '-112px -121px',
	're' => '-128px -121px',
	'ro' => '-144px -121px',
	'rs' => '-160px -121px',
	'ru' => '-176px -121px',
	'rw' => '-192px -121px',
	'sa' => '-208px -121px',
	'sb' => '-224px -121px',
	'sc' => '-240px -121px',
	'scotland' => '0 -132px',
	'sd' => '-16px -132px',
	'se' => '-32px -132px',
	'sg' => '-48px -132px',
	'sh' => '-64px -132px',
	'si' => '-80px -132px',
	'sj' => '-176px -165px',
	'sk' => '-96px -132px',
	'sl' => '-112px -132px',
	'sm' => '-128px -132px',
	'sn' => '-144px -132px',
	'so' => '-160px -132px',
	'sr' => '-176px -132px',
	'ss' => '-192px -132px',
	'st' => '-208px -132px',
	'sv' => '-224px -132px',
	'sy' => '-240px -132px',
	'sz' => '0 -143px',
	'tc' => '-16px -143px',
	'td' => '-32px -143px',
	'tf' => '-48px -143px',
	'tg' => '-64px -143px',
	'th' => '-80px -143px',
	'tj' => '-96px -143px',
	'tk' => '-112px -143px',
	'tl' => '-128px -143px',
	'tm' => '-144px -143px',
	'tn' => '-160px -143px',
	'to' => '-176px -143px',
	'tr' => '-192px -143px',
	'tt' => '-208px -143px',
	'tv' => '-224px -143px',
	'tw' => '-240px -143px',
	'tz' => '0 -154px',
	'ua' => '-16px -154px',
	'ug' => '-32px -154px',
	'um' => '-48px -154px',
	'us' => '-64px -154px',
	'uy' => '-80px -154px',
	'uz' => '-96px -154px',
	'va' => '-112px -154px',
	'vc' => '-128px -154px',
	've' => '-144px -154px',
	'vg' => '-160px -154px',
	'vi' => '-176px -154px',
	'vn' => '-192px -154px',
	'vu' => '-208px -154px',
	'wales' => '-224px -154px',
	'wf' => '-240px -154px',
	'ws' => '0 -165px',
	'ye' => '-16px -165px',
	'yt' => '-32px -165px',
	'za' => '-48px -165px',
	'zm' => '-64px -165px',
	'zw' => '-80px -165px',
);
?>