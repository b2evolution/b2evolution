<?php
/*
 * b2evolution localization & language config
 * Version of this file: 0.8.9+CVS
 */

# Enable localization?
# set to 0 to disable localization
# set to 1 to enable gettext localization if supported (not recommended)
# set to 2 to enable b2evo advanced localization (recommended)
$use_l10n = 2;

# To be used for m17n support:
$dbcharset = 'iso-8859-1';		// If you don't know, don't change this setting.


# Default locale used for backoffice, notification messages and fallback.
# This will be overwritten from database settings, if configured there.
# These use an ISO 639 language code, a '-' and an ISO 3166 country code.
# This MUST BE in the list below.
$default_locale = 'en-US';


if( !function_exists('NT_') )
{ // we want to be able to reload this file.
	/*
	 * NT_(-)
	 *
	 * No Translation
	 * Nevertheless, the string will be extracted by the gettext tools
	 */
	function NT_( $string )
	{
		return $string;
	}
}


//{{{ defining the locales:
# These are the default settings.
# This array will be overwritten from DB if locales are set there,
# that is when they get updated from the Backoffice
# They are also used as fallback, if we have no access to the DB yet.
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
	'en-US' => array( 'name' => NT_('English (US)'),
										'charset' => 'iso-8859-1',
										'datefmt' => 'm/d/y',
										'timefmt' => 'h:i:s a',
										'messages' => 'en_US',
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
										'charset' => 'Windows-1257',
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


// Load locale related functions: (ne need NT_() here)
require_once( dirname(__FILE__). "/$conf_dirout/$core_subdir/_functions_locale.php" );


/*{{{ How to format the dates and times:
The following characters are recognized in the format string:
a - "am" or "pm"
A - "AM" or "PM"
B - Swatch Internet time
d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
D - day of the week, textual, 3 letters; i.e. "Fri"
F - month, textual, long; i.e. "January"
g - hour, 12-hour format without leading zeros; i.e. "1" to "12"
G - hour, 24-hour format without leading zeros; i.e. "0" to "23"
h - hour, 12-hour format; i.e. "01" to "12"
H - hour, 24-hour format; i.e. "00" to "23"
i - minutes; i.e. "00" to "59"
I (capital i) - "1" if Daylight Savings Time, "0" otherwise.
j - day of the month without leading zeros; i.e. "1" to "31"
l (lowercase 'L') - day of the week, textual, long; i.e. "Friday"
L - boolean for whether it is a leap year; i.e. "0" or "1"
m - month; i.e. "01" to "12"
M - month, textual, 3 letters; i.e. "Jan"
n - month without leading zeros; i.e. "1" to "12"
r - RFC 822 formatted date; i.e. "Thu, 21 Dec 2000 16:01:07 +0200" (added in PHP 4.0.4)
s - seconds; i.e. "00" to "59"
S - English ordinal suffix, textual, 2 characters; i.e. "th", "nd"
t - number of days in the given month; i.e. "28" to "31"
T - Timezone setting of this machine; i.e. "MDT"
U - seconds since the epoch
w - day of the week, numeric, i.e. "0" (Sunday) to "6" (Saturday)
Y - year, 4 digits; i.e. "1999"
y - year, 2 digits; i.e. "99"
z - day of the year; i.e. "0" to "365"
Z - timezone offset in seconds (i.e. "-43200" to "43200"). The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.

Unrecognized characters in the format string will be printed as-is.

}}}*/

# Default language (ISO code)
# We get this one from the default locale above
#$default_language = substr( $default_locale, 0, 2 );

# day at the start of the week: 0 for Sunday, 1 for Monday, 2 for Tuesday, etc
$start_of_week = 1;

# Set this to 1 if you are a translator and wish to use the .po extraction script in
# the /locales folder. Do not allow this on production servers as people could harm
# your operations by continuously recomputing your language files.
$allow_po_extraction = 1;

?>
