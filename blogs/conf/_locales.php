<?php
/*
 * b2evolution localization & language config
 * Version of this file: 0.8.6
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */
# Enable localization?
# set to 0 to disable localization
# set to 1 to enable gettext localization if supported (not recommended)
# set to 2 to enable b2evo advanced localization (recommended)
$use_l10n = 2;

# To be used for m17n support:
$dbcharset = 'iso-8859-1';		// If you don't know, don't change this setting.

// Load locale related functions: (ne need NT_() here)
require_once (dirname(__FILE__)."/$conf_dirout/$core_subdir/_functions_locale.php"); 

# Supported languages for posts:
$languages = array(
	'nl' => NT_('Dutch'),
	'en' => NT_('English'),
	'fr' => NT_('French'),
	'de' => NT_('German'),
	'ja' => NT_('Japanese'),
	'lt' => NT_('Lithuanian'),
	'nb' => NT_('Norwegian Bokm&aring;l'), 
	'pt' => NT_('Portuguese'),
	'es' => NT_('Spanish'),
	'sv' => NT_('Swedish'),
	'zh' => NT_('Simplified Chinese'),
	);


# Default locale used for backoffice and notification messages
# These use an ISO 639 language code, a '_' and an ISO 3166 country code
# This MUST BE in the list below
$default_locale = 'en_US';


$default_locale = 'fr_FR';			// For testing :))
// $default_locale = 'de_DE';			// For testing :))
// $default_locale = 'ja_JP';			// For testing :))
// $default_locale = 'nl_NL';			// For testing :))
// $default_locale = 'sv_SE';			// For testing :))


#  and localization:
# Add what you need and comment what you don't need
$locales = array(
	'de_DE' => array( // German, Germany
									'charset' => 'iso-8859-1',
									'datefmt' => 'd.m.y',
									'timefmt' => 'H:i:s',
								),
	'en_US' => array( // English, USA
									'charset' => 'iso-8859-1',	// gettext will convert to this
									'datefmt' => 'm/d/y',	
									'timefmt' => 'h:i:s a',	
								),
	'es_ES' => array(	// Spanish, SPAIN
									'charset' => 'iso-8859-1',
									'datefmt' => 'd.m.y',
									'timefmt' => 'H:i:s',	
								),
	'fr_FR' => array( // French, FRANCE	
									'charset' => 'iso-8859-1',
									'datefmt' => 'd.m.y',
									'timefmt' => 'H:i:s',	
								),
	'ja_JP' => array(	// Japanese, JAPAN
									'charset' => 'utf-8',
									'datefmt' => 'Y/m/d',
									'timefmt' => 'H:i:s',
								),
	'lt_LT' => array( // Lithuanian	
									'charset' => 'Windows-1257',
									'datefmt' => 'Y-m-d',
									'timefmt' => 'H:i:s',
										),
	'nb_NO' => array( // Bokmål, NORWAY    
									'charset' => 'iso-8859-1', 
									'datefmt' => 'd.m.y', 
									'timefmt' => 'H:i:s',    
                ), 
	'nl_NL' => array( // Dutch, NETHERLANDS
									'charset' => 'iso-8859-1',
									'datefmt' => 'd-m-y',
									'timefmt' => 'H:i:s',	
								),
	'pt_BR' => array( // Portuguese, BRAZIL	
									'charset' => 'iso-8859-1',
									'datefmt' => 'd.m.y',
									'timefmt' => 'H:i:s',	
								),
	'sv_SE' => array( // Sweedish, SWEDEN	
									'charset' => 'iso-8859-1',
									'datefmt' => 'y-m-d',
									'timefmt' => 'H:i:s',	
								),
	'zh_CN' => array( // Simplified Chinese, CHINA
									'charset' => 'gb2312',
									'datefmt' => 'y-m-d',
									'timefmt' => 'H:i:s',
								),
);

/* How to format the dates and times:
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
 */

# Default language (ISO code)
# We get this one from the default locale above
$default_language = substr( $default_locale, 0, 2 );

# day at the start of the week: 0 for Sunday, 1 for Monday, 2 for Tuesday, etc
$start_of_week = 1;

# Set this to 1 if you are a translator and wish to use the .po extraction script in 
# the /locales folder. Do not allow this on production servers as people could harm
# your operations by continuously recomputing your language files.
$allow_po_extraction = 1;
?>
