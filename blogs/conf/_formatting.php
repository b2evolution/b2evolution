<?php
/**
 * This is b2evolution's formatting config file.
 *
 * This file sets how b2evolution will parse and format texts (posts, comments, etc.)
 * Last significant changes to this file: version 1.6
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @todo Move as much as possible to $Settings / $UserSettings
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// ** Formatting **

/**
 * Automatically balance unmatched tags in posts and comments.
 *
 * Choose the formatting options for your posts:
 *  0 to disable
 *  1 to ensable
 * @global integer 0|1
 */
$use_balanceTags = 1;


# Choose formatting options for comments
# 'never'   : option will never be used
# 'opt-in'  : option will be used only if user explicitely asks for it
# 'opt-out' : option will be used by default, but user can refuse
# 'always'  : option will always be used
$comments_use_autobr = 'opt-out';	// automatically change line breaks to <br />


/*
 * Validity & Security Checking
 *
 * Posts and comments should be checked to see if they contain valid XHTML code
 * and no invalid code (javascript, styles, CSS, etc...)
 */
# Html checking will validate posts and comments to a subset of valid XHTML.
# This will also do much cleaner security checking than the next option.
# Note: This option requires the PHP XML module. If your PHP installation doesn't have it
# disable html_checker and use security_checker.
$use_html_checker = 1;
# Security checking will check for illegal javascript hacks in posts/comments
# and for CSS in comments. However, this may be a bit harsh on your posts :]
$use_security_checker = 0;
# WARNING: disabling both $use_html_checker and $use_security_checker is suicidal !

// Set this to true if you want to enforce XHTML strict
$use_strict =  false;


/*
 * HTML Checker params:
 *
 * The params are defined twice: once for the posts and once for the comments.
 * Typically you'll be more restrictive on comments.
 *
 * Adapted from XHTML-1.0-Transitional/Strict by fplanque
 * http://www.w3.org/TR/2002/REC-xhtml1-20020801/dtds.html#a_dtd_XHTML-1.0-Strict
 */

// DEFINITION of allowed XHTML code for POSTS (posted in the backoffice)

/**
 * Allowed Entity classes
 */
if( $use_strict )
{
	define('E_special_pre', 'br span bdo');			// Strict
	define('E_special', E_special_pre.' img');		// Strict
}
else
{
	define('E_special_extra', 'img' );							// Transitional
	define('E_special_basic', 'br span bdo' );			// Transitional
	define('E_special', E_special_basic.' '.E_special_extra );	// Transitional
}

if( $use_strict )
{
	define('E_fontstyle', 'tt i b big small');				// Strict
}
else
{
	define('E_fontstyle_extra', 'big small font' );			// Transitional
	define('E_fontstyle_basic', 'tt i b u s strike' );	// Transitional
	define('E_fontstyle', E_fontstyle_basic.' '.E_fontstyle_extra );	// Transitional
}

if( $use_strict )
{
	define('E_phrase', 'em strong dfn code q samp kbd var cite abbr acronym sub sup'); // Strict
}
else
{
	define('E_phrase_extra', 'sub sup'); 																							// Transitional
	define('E_phrase_basic', 'em strong dfn code q samp kbd var cite abbr acronym');	// Transitional
	define('E_phrase', E_phrase_basic.' '.E_phrase_extra ); 													// Transitional
}

define('E_misc_inline', 'ins del');
define('E_misc', E_misc_inline);
define('E_inline', 'a '.E_special.' '.E_fontstyle.' '.E_phrase );
define('E_Iinline', '#PCDATA '.E_inline.' '.E_misc_inline );
define('E_heading', 'h1 h2 h3 h4 h5 h6');

if( $use_strict )
{
	define('E_list', 'ul ol dl');				// Strict
}
else
{
	define('E_list', 'ul ol dl menu dir');	// Transitional
}

if( $use_strict )
{
	define('E_blocktext', 'pre hr blockquote address');			// Strict
}
else
{
	define('E_blocktext', 'pre hr blockquote address center');	// Transitional
}

define('E_block', 'p '.E_heading.' div '.E_list.' '.E_blocktext.' fieldset table');

if( $use_strict ) define('E_Bblock', E_block.' '.E_misc );			// Strict only

define('E_Flow', '#PCDATA '.E_block.' '.E_inline.' '.E_misc );
define('E_a_content', '#PCDATA '.E_special.' '.E_fontstyle.' '.E_phrase.' '.E_misc_inline );

if( $use_strict )
{
	define('E_pre_content', '#PCDATA a '.E_fontstyle.' '.E_phrase.' '.E_special_pre.' '.E_misc_inline ); // Strict
}
else
{
	define('E_pre_content', '#PCDATA a '.E_special_basic.' '.E_fontstyle_basic.' '.E_phrase_basic.' '.E_misc_inline ); // Transitional
}

// Allowed Attribute classes
define('A_coreattrs', 'class title');
define('A_i18n', 'lang xml:lang dir');
define('A_attrs', A_coreattrs.' '.A_i18n);

if( !$use_strict ) define('A_TextAlign', 'align');									// Transitional only

define('A_cellhalign', 'align char charoff');
define('A_cellvalign', 'valign');

// Array showing what tags are allowed and what their allowed subtags are.
$allowed_tags = array
(
	'body' => E_Flow, // Remember this is not a true body, just a post body
	'div' => E_Flow,
	'p' => E_Iinline,
	'h1' => E_Iinline,
	'h2' => E_Iinline,
	'h3' => E_Iinline,
	'h4' => E_Iinline,
	'h5' => E_Iinline,
	'h6' => E_Iinline,
	'ul' => 'li',
	'ol' => 'li',
);
if( !$use_strict )
{
	$allowed_tags += array
	(
		'menu' => 'li',		// Transitional only
		'dir' => 'li',		// Transitional only
	);
}

$allowed_tags += array
(
	'li' => E_Flow,
	'dl' => 'dt dd',
	'dt' => E_Iinline,
	'dd' => E_Flow,
);

if( $use_strict )
{
	$allowed_tags += array
	(
		'address' => E_Iinline,														// Strict
	);
}
else
{
	$allowed_tags += array
	(
		'address' => '#PCDATA '.E_inline.' '.E_misc_inline,		// Transitional
	);
}

$allowed_tags += array
	(
		'hr' => '',
		'pre' => E_pre_content,
	);

if( $use_strict )
{
	$allowed_tags += array
	(
		'blockquote' => E_Bblock,		// Strict
	);
}
else
{
	$allowed_tags += array
	(
		'blockquote' => E_Flow,					// Transitional
		'center' => E_Flow,					// Transitional only
	);
}

$allowed_tags += array
(
	'ins' => E_Flow,
	'del' => E_Flow,
	'a' => E_a_content,
	'span' => E_Iinline,
	'bdo' => E_Iinline,
	'br' => '',
	'em' => E_Iinline,
	'strong' => E_Iinline,
	'dfn' => E_Iinline,
	'code' => E_Iinline,
	'samp' => E_Iinline,
	'kbd' => E_Iinline,
	'var' => E_Iinline,
	'cite' => E_Iinline,
	'abbr' => E_Iinline,
	'acronym' => E_Iinline,
	'q' => E_Iinline,
	'sub' => E_Iinline,
	'sup' => E_Iinline,
	'tt' => E_Iinline,
	'i' => E_Iinline,
	'b' => E_Iinline,
	'big' => E_Iinline,
	'small' => E_Iinline,
);

if( !$use_strict )
{
	$allowed_tags += array
	(
		'u' => E_Iinline,						// Transitional only
		's' => E_Iinline,						// Transitional only
		'strike' => E_Iinline,			// Transitional only
		'font' => E_Iinline,				// Transitional only
	);
}

$allowed_tags += array
(
	'img' => '',
	'fieldset' => '#PCDATA legend '.E_block.' '.E_inline.' '.E_misc,
	'legend' => E_Iinline,
	'table' => 'caption col colgroup thead tfoot tbody tr',
	'caption' => E_Iinline,
	'thead' => 'tr',
	'tfoot' => 'tr',
	'tbody' => 'tr',
	'colgroup' => 'col',
	'tr' => 'th td',
	'th' => E_Flow,
	'td' => E_Flow,
);

// Array showing allowed attributes for tags
if( $use_strict )
{
	$allowed_attribues = array
	(
		'div' => A_attrs,								// Strict
		'p' => A_attrs,								// Strict
		'h1' => A_attrs,								// Strict
		'h2' => A_attrs,								// Strict
		'h3' => A_attrs,								// Strict
		'h4' => A_attrs,								// Strict
		'h5' => A_attrs,								// Strict
		'h6' => A_attrs,								// Strict
		'ul' => A_attrs,	// Strict
		'ol' => A_attrs,	// Strict
		'li' => A_attrs,							// Strict
		'dl' => A_attrs,					// Strict
		'hr' => A_attrs,															// Strict
		'pre' => A_attrs.' xml:space',								// Strict
		'a' => A_attrs.' charset type href hreflang rel rev shape coords',			// Strict
		'br' => A_coreattrs,				// Strict
		'img' => A_attrs.' src alt longdesc height width usemap ismap',	// Strict
		'legend' => A_attrs,							// Strict
		'table' => A_attrs.' summary width border frame rules cellspacing cellpadding',  // Strict
		'caption' => A_attrs,						// Strict
		'tr' => A_attrs.' '.A_cellhalign.' '.A_cellvalign,	// Strict
		'th' => A_attrs.' abbr axis headers scope rowspan colspan'.A_cellhalign.' '.A_cellvalign,	// Strict
		'td' => A_attrs.' abbr axis headers scope rowspan colspan'.A_cellhalign.' '.A_cellvalign,	// Strict
	);
}
else
{
	$allowed_attribues = array
	(
		'div' => A_attrs.' '.A_TextAlign,		// Transitional
		'p' => A_attrs.' '.A_TextAlign,		// Transitional
		'h1' => A_attrs.' '.A_TextAlign,		// Transitional
		'h2' => A_attrs.' '.A_TextAlign,		// Transitional
		'h3' => A_attrs.' '.A_TextAlign,		// Transitional
		'h4' => A_attrs.' '.A_TextAlign,		// Transitional
		'h5' => A_attrs.' '.A_TextAlign,		// Transitional
		'h6' => A_attrs.' '.A_TextAlign,		// Transitional
		'ul' => A_attrs.' type compact',	// Transitional
		'ol' => A_attrs.' type compact start',	// Transitional
		'menu' => A_attrs.' compact',	// Transitional only
		'dir' => A_attrs.' compact',	// Transitional only
		'li' => A_attrs.' type value',	// Transitional
		'dl' => A_attrs.' compact',	// Transitional
		'hr' => A_attrs.' align noshade size width',		// Transitional
		'pre' => A_attrs.' width xml:space',						// Transitional
		'center' => A_attrs,					// Transitional only
		'a' => A_attrs.' charset type href hreflang rel rev shape coords target',	// Transitional
		'br' => A_coreattrs.' clear',	// Transitional
		'u' => A_attrs,						// Transitional only
		's' => A_attrs,						// Transitional only
		'strike' => A_attrs,			// Transitional only
		'font' => A_coreattrs.' '.A_i18n.' size color face',	// Transitional only
		'img' => A_attrs.' src alt name longdesc height width usemap ismap align border hspace vspace',	// Transitional
		'legend' => A_attrs.' align',				// Transitional
		'table' => A_attrs.' summary width border frame rules cellspacing cellpadding align bgcolor',								// Transitional
		'caption' => A_attrs.' align',																								// Transitional
		'tr' => A_attrs.' '.A_cellhalign.' '.A_cellvalign.' bgcolor',																// Transitional
		'th' => A_attrs.' abbr axis headers scope rowspan colspan'.A_cellhalign.' '.A_cellvalign.' nowrap bgcolor width height',	// Transitional
		'td' => A_attrs.' abbr axis headers scope rowspan colspan'.A_cellhalign.' '.A_cellvalign.' nowrap bgcolor width height',	// Transitional
	);
}
$allowed_attribues += array
(
	'fieldset' => A_attrs,

	'ins' => A_attrs.' cite datetime',
	'del' => A_attrs.' cite datetime',
	'blockquote' => A_attrs.' cite',
	'span' => A_attrs,
	'bdo' => A_coreattrs.' lang xml:lang dir',
	'dt' => A_attrs,
	'dd' => A_attrs,

	'address' => A_attrs,

	'em' => A_attrs,
	'strong' => A_attrs,
	'dfn' => A_attrs,
	'code' => A_attrs,
	'samp' => A_attrs,
	'kbd' => A_attrs,
	'var' => A_attrs,
	'cite' => A_attrs,
	'abbr' => A_attrs,
	'acronym' => A_attrs,
	'q' => A_attrs.' cite',
	'sub' => A_attrs,
	'sup' => A_attrs,
	'tt' => A_attrs,
	'i' => A_attrs,
	'b' => A_attrs,
	'big' => A_attrs,
	'small' => A_attrs,
	'colgroup' => A_attrs.' span width cellhalign cellvalign',
	'col' => A_attrs.' span width cellhalign cellvalign',
	'thead' => A_attrs.' '.A_cellhalign.' '.A_cellvalign,
	'tfoot' => A_attrs.' '.A_cellhalign.' '.A_cellvalign,
	'tbody' => A_attrs.' '.A_cellhalign.' '.A_cellvalign,

);

$allowed_uri_scheme = array
(
	'http',
	'https',
	'ftp',
	'gopher',
	'nntp',
	'news',
	'mailto',
	'irc',
	'aim',
	'icq'
);


// DEFINITION of allowed XHTML code for COMMENTS (posted from the public blog pages)

# here is a list of the tags that are allowed in the comments.
# all tags not in this list will be filtered out anyway before we do any checking
$comment_allowed_tags = '<p><ul><ol><li><dl><dt><dd><address><blockquote><ins><del><span><bdo><br><em><strong><dfn><code><samp><kdb><var><cite><abbr><acronym><q><sub><sup><tt><i><b><big><small>';

// Allowed Entity classes
define('C_E_special_pre', 'br span bdo');
define('C_E_special', C_E_special_pre);
define('C_E_fontstyle', 'tt i b big small');
define('C_E_phrase', 'em strong dfn code q samp kbd var cite abbr acronym sub sup');
define('C_E_misc_inline', 'ins del');
define('C_E_misc', C_E_misc_inline);
define('C_E_inline', 'a '.C_E_special.' '.C_E_fontstyle.' '.C_E_phrase );
define('C_E_Iinline', '#PCDATA '.C_E_inline.' '.C_E_misc_inline );
define('C_E_heading', '');
define('C_E_list', 'ul ol dl');
define('C_E_blocktext', 'hr blockquote address');
define('C_E_block', 'p '.C_E_heading.' div '.C_E_list.' '.C_E_blocktext.' table');
define('C_E_Bblock', C_E_block.' '.C_E_misc );
define('C_E_Flow', '#PCDATA '.C_E_block.' '.C_E_inline.' '.C_E_misc );
define('C_E_a_content', '#PCDATA '.C_E_special.' '.C_E_fontstyle.' '.C_E_phrase.' '.C_E_misc_inline );
define('C_E_pre_content', '#PCDATA a '.C_E_fontstyle.' '.C_E_phrase.' '.C_E_special_pre.' '.C_E_misc_inline );

// Allowed Attribute classes
define('C_A_coreattrs', 'class title');
define('C_A_i18n', 'lang xml:lang dir');
define('C_A_attrs', C_A_coreattrs.' '.C_A_i18n);
define('C_A_cellhalign', 'align char charoff');
define('C_A_cellvalign', 'valign');

/**
 * Array showing what tags are allowed and what their allowed subtags are.
 * @global array
 */
$comments_allowed_tags = array
(
	'body' => E_Flow, // Remember this is not a true body, just a comment body
	'p' => C_E_Iinline,
	'ul' => 'li',
	'ol' => 'li',
	'li' => C_E_Flow,
	'dl' => 'dt dd',
	'dt' => C_E_Iinline,
	'dd' => C_E_Flow,
	'address' => C_E_Iinline,
	'hr' => '',
);
if( $use_strict )
{
	$comments_allowed_tags += array
	(
		'blockquote' => C_E_Bblock,		// XHTML-1.0-Strict
	);
}
else
{
	$comments_allowed_tags += array
	(
		'blockquote' => C_E_Flow,				// XHTML-1.0-Transitional
	);
}
$comments_allowed_tags += array
(
	'ins' => C_E_Flow,
	'del' => C_E_Flow,
//	'a' => C_E_a_content,  // Allowing this will call for a whole lot of comment spam!!!
	'span' => C_E_Iinline,
	'bdo' => C_E_Iinline,
	'br' => '',
	'em' => C_E_Iinline,
	'strong' => C_E_Iinline,
	'dfn' => C_E_Iinline,
	'code' => C_E_Iinline,
	'samp' => C_E_Iinline,
	'kbd' => C_E_Iinline,
	'var' => C_E_Iinline,
	'cite' => C_E_Iinline,
	'abbr' => C_E_Iinline,
	'acronym' => C_E_Iinline,
	'q' => C_E_Iinline,
	'sub' => C_E_Iinline,
	'sup' => C_E_Iinline,
	'tt' => C_E_Iinline,
	'i' => C_E_Iinline,
	'b' => C_E_Iinline,
	'big' => C_E_Iinline,
	'small' => C_E_Iinline,
);


/**
 * Array showing allowed attributes for tags.
 * @global array
 */
$comments_allowed_attribues = array
(
	'p' => C_A_attrs,
	'ul' => C_A_attrs,
	'ol' => C_A_attrs,
	'li' => C_A_attrs,
	'dl' => C_A_attrs,
	'dt' => C_A_attrs,
	'dd' => C_A_attrs,
	'address' => C_A_attrs,
	'blockquote' => C_A_attrs.' cite',
	'ins' => C_A_attrs.' cite datetime',
	'del' => C_A_attrs.' cite datetime',
	'a' => C_A_attrs.' charset type href hreflang rel rev shape coords',
	'span' => C_A_attrs,
	'bdo' => C_A_coreattrs.' lang xml:lang dir',
	'br' => C_A_coreattrs,
	'em' => C_A_attrs,
	'strong' => C_A_attrs,
	'dfn' => C_A_attrs,
	'code' => C_A_attrs,
	'samp' => C_A_attrs,
	'kbd' => C_A_attrs,
	'var' => C_A_attrs,
	'cite' => C_A_attrs,
	'abbr' => C_A_attrs,
	'acronym' => C_A_attrs,
	'q' => C_A_attrs.' cite',
	'sub' => C_A_attrs,
	'sup' => C_A_attrs,
	'tt' => C_A_attrs,
	'i' => C_A_attrs,
	'b' => C_A_attrs,
	'big' => C_A_attrs,
	'small' => C_A_attrs,
);


/**
 * URI schemes allowed for URLs in comments and user profiles:
 * @global array
 */
$comments_allowed_uri_scheme = array
(
	'http',
	'https',
	'ftp',
	'gopher',
	'nntp',
	'news',
	'mailto',
	'irc',
	'aim',
	'icq'
);


// Array showing URI attributes
$uri_attrs = array
(
	'xmlns',
	'profile',
	'href',
	'src',
	'cite',
	'classid',
	'codebase',
	'data',
	'archive',
	'usemap',
	'longdesc',
	'action'
);


/**
 * Translation of HTML entities and special characters.
 * @global array
 */
$b2_htmltrans = array(
	// '&#8211;' => ' ', '&#8212;' => ' ', '&#8216;' => ' ', '&#8217;' => ' ',
	// '&#8220;' => ' ', '&#8221;' => ' ', '&#8226;' => ' ', '&#8364;' => ' ',
	'&lt;' => '&#60;',	'&gt;' => '&#62;',
	'&sp;' => '&#32;', '&excl;' => '&#33;', '&quot;' => '&#34;', '&num;' => '&#35;',
	'&dollar;' =>  '&#36;', '&percnt;' => '&#37;', '&amp;' => '&#38;', '&apos;' => '&#39;',
	'&lpar;' => '&#40;', '&rpar;' => '&#41;',
	'&ast;' => '&#42;', '&plus;' => '&#43;', '&comma;' => '&#44;', '&hyphen;' => '&#45;',
	'&minus;' => '&#45;', '&period;' => '&#46;', '&sol;' => '&#47;', '&colon;' => '&#58;',
	'&semi;' => '&#59;', '&lt;' => '&#60;',
	'&equals;' => '&#61;', '&gt;' => '&#62;', '&quest;' => '&#63;', '&commat;' => '&#64;',
	'&lsqb;' => '&#91;', '&bsol;' => '&#92;', '&rsqb;' => '&#93;', '&circ;' => '&#94;',
	'&lowbar;' => '&#95;', '&horbar;' => '&#95;',
	'&grave;' => '&#96;', '&lcub;' => '&#123;', '&verbar;' => '&#124;', '&rcub;' => '&#125;',
	'&tilde;' => '&#126;', '&lsquor;' => '&#130;', '&ldquor;' => '&#132;',
	'&ldots;' => '&#133;', '&Scaron;' => '&#138;', '&lsaquo;' => '&#139;', '&OElig;' => '&#140;',
	'&lsquo;' => '&#145;', '&rsquor;' => '&#145;', '&rsquo;' => '&#146;',
	'&ldquo;' => '&#147;', '&rdquor;' => '&#147;', '&rdquo;' => '&#148;', '&bull;' => '&#149;',
	'&ndash;' => '&#150;', '&endash;' => '&#150;', '&mdash;' => '&#151;', '&emdash;' => '&#151;',
	'&tilde;' => '&#152;', '&trade;' => '&#153;',
	'&scaron;' => '&#154;', '&rsaquo;' => '&#155;', '&oelig;' => '&#156;', '&Yuml;' => '&#159;',
	'&nbsp;' => '&#160;', '&iexcl;' => '&#161;', '&cent;' => '&#162;', '&pound;' => '&#163;',
	'&curren;' => '&#164;', '&yen;' => '&#165;',
	'&brvbar;' => '&#166;', '&brkbar;' => '&#166;', '&sect;' => '&#167;', '&uml;' => '&#168;',
	'&die;' => '&#168;', '&copy;' => '&#169;', '&ordf;' => '&#170;', '&laquo;' => '&#171;',
	'&not;' => '&#172;', '&shy;' => '&#173;',
	'&reg;' => '&#174;', '&macr;' => '&#175;', '&hibar;' => '&#175;', '&deg;' => '&#176;',
	'&plusmn;' => '&#177;', '&sup2;' => '&#178;', '&sup3;' => '&#179;', '&acute;' => '&#180;',
	'&micro;' => '&#181;', '&para;' => '&#182;',
	'&middot;' => '&#183;', '&cedil;' => '&#184;', '&sup1;' => '&#185;', '&ordm;' => '&#186;',
	'&raquo;' => '&#187;', '&frac14;' => '&#188;', '&frac12;' => '&#189;', '&half;' => '&#189;',
	'&frac34;' => '&#190;', '&iquest;' => '&#191;',
	'&Agrave;' => '&#192;', '&Aacute;' => '&#193;', '&Acirc;' => '&#194;', '&Atilde;' => '&#195;',
	'&Auml;' => '&#196;', '&Aring;' => '&#197;', '&AElig;' => '&#198;', '&Ccedil;' => '&#199;',
	'&Egrave;' => '&#200;', '&Eacute;' => '&#201;',
	'&Ecirc;' => '&#202;', '&Euml;' => '&#203;', '&Igrave;' => '&#204;', '&Iacute;' => '&#205;',
	'&Icirc;' => '&#206;', '&Iuml;' => '&#207;', '&ETH;' => '&#208;', '&Ntilde;' => '&#209;',
	'&Ograve;' => '&#210;', '&Oacute;' => '&#211;',
	'&Ocirc;' => '&#212;', '&Otilde;' => '&#213;', '&Ouml;' => '&#214;', '&times;' => '&#215;',
	'&Oslash;' => '&#216;', '&Ugrave;' => '&#217;', '&Uacute;' => '&#218;', '&Ucirc;' => '&#219;',
	'&Uuml;' => '&#220;', '&Yacute;' => '&#221;',
	'&THORN;' => '&#222;', '&szlig;' => '&#223;', '&agrave;' => '&#224;', '&aacute;' => '&#225;',
	'&acirc;' => '&#226;', '&atilde;' => '&#227;', '&auml;' => '&#228;', '&aring;' => '&#229;',
	'&aelig;' => '&#230;', '&ccedil;' => '&#231;',
	'&egrave;' => '&#232;', '&eacute;' => '&#233;', '&ecirc;' => '&#234;', '&euml;' => '&#235;',
	'&igrave;' => '&#236;', '&iacute;' => '&#237;', '&icirc;' => '&#238;', '&iuml;' => '&#239;',
	'&eth;' => '&#240;', '&ntilde;' => '&#241;',
	'&ograve;' => '&#242;', '&oacute;' => '&#243;', '&ocirc;' => '&#244;', '&otilde;' => '&#245;',
	'&ouml;' => '&#246;', '&divide;' => '&#247;', '&oslash;' => '&#248;', '&ugrave;' => '&#249;',
	'&uacute;' => '&#250;', '&ucirc;' => '&#251;',
	'&uuml;' => '&#252;', '&yacute;' => '&#253;', '&thorn;' => '&#254;', '&yuml;' => '&#255;',
	'&OElig;' => '&#338;', '&oelig;' => '&#339;', '&Scaron;' => '&#352;', '&scaron;' => '&#353;',
	'&Yuml;' => '&#376;', '&fnof;' => '&#402;',
	'&circ;' => '&#710;', '&tilde;' => '&#732;', '&Alpha;' => '&#913;', '&Beta;' => '&#914;',
	'&Gamma;' => '&#915;', '&Delta;' => '&#916;', '&Epsilon;' => '&#917;', '&Zeta;' => '&#918;',
	'&Eta;' => '&#919;', '&Theta;' => '&#920;',
	'&Iota;' => '&#921;', '&Kappa;' => '&#922;', '&Lambda;' => '&#923;',
	'&Mu;' => '&#924;', '&Nu;' => '&#925;', '&Xi;' => '&#926;',
	'&Omicron;' => '&#927;', '&Pi;' => '&#928;', '&Rho;' => '&#929;', '&Sigma;' => '&#931;',
	'&Tau;' => '&#932;', '&Upsilon;' => '&#933;', '&Phi;' => '&#934;',
	'&Chi;' => '&#935;', '&Psi;' => '&#936;', '&Omega;' => '&#937;',
	'&alpha;' => '&#945;', '&beta;' => '&#946;', '&gamma;' => '&#947;', '&delta;' => '&#948;',
	'&epsilon;' => '&#949;', '&zeta;' => '&#950;', '&eta;' => '&#951;',
	'&theta;' => '&#952;', '&iota;' => '&#953;', '&kappa;' => '&#954;', '&lambda;' => '&#955;',
	'&mu;' => '&#956;', '&nu;' => '&#957;', '&xi;' => '&#958;',
	'&omicron;' => '&#959;', '&pi;' => '&#960;', '&rho;' => '&#961;', '&sigmaf;' => '&#962;',
	'&sigma;' => '&#963;', '&tau;' => '&#964;', '&upsilon;' => '&#965;', '&phi;' => '&#966;',
	'&chi;' => '&#967;', '&psi;' => '&#968;',
	'&omega;' => '&#969;', '&thetasym;' => '&#977;', '&upsih;' => '&#978;', '&piv;' => '&#982;',
	'&ensp;' => '&#8194;', '&emsp;' => '&#8195;', '&thinsp;' => '&#8201;', '&zwnj;' => '&#8204;',
	'&zwj;' => '&#8205;', '&lrm;' => '&#8206;',
	'&rlm;' => '&#8207;', '&ndash;' => '&#8211;', '&mdash;' => '&#8212;', '&lsquo;' => '&#8216;',
	'&rsquo;' => '&#8217;', '&sbquo;' => '&#8218;', '&ldquo;' => '&#8220;', '&rdquo;' => '&#8221;',
	'&bdquo;' => '&#8222;', '&dagger;' => '&#8224;',
	'&Dagger;' => '&#8225;', '&bull;' => '&#8226;', '&hellip;' => '&#8230;', '&permil;' => '&#8240;',
	'&prime;' => '&#8242;', '&Prime;' => '&#8243;', '&lsaquo;' => '&#8249;', '&rsaquo;' => '&#8250;',
	'&oline;' => '&#8254;', '&frasl;' => '&#8260;',
	'&euro;' => '&#8364;', '&image;' => '&#8465;', '&weierp;' => '&#8472;', '&real;' => '&#8476;',
	'&trade;' => '&#8482;', '&alefsym;' => '&#8501;', '&larr;' => '&#8592;', '&uarr;' => '&#8593;',
	'&rarr;' => '&#8594;', '&darr;' => '&#8595;',
	'&harr;' => '&#8596;', '&crarr;' => '&#8629;', '&lArr;' => '&#8656;', '&uArr;' => '&#8657;',
	'&rArr;' => '&#8658;', '&dArr;' => '&#8659;', '&hArr;' => '&#8660;', '&forall;' => '&#8704;',
	'&part;' => '&#8706;', '&exist;' => '&#8707;',
	'&empty;' => '&#8709;', '&nabla;' => '&#8711;', '&isin;' => '&#8712;', '&notin;' => '&#8713;',
	'&ni;' => '&#8715;', '&prod;' => '&#8719;', '&sum;' => '&#8721;', '&minus;' => '&#8722;',
	'&lowast;' => '&#8727;', '&radic;' => '&#8730;',
	'&prop;' => '&#8733;', '&infin;' => '&#8734;', '&ang;' => '&#8736;', '&and;' => '&#8743;',
	'&or;' => '&#8744;', '&cap;' => '&#8745;', '&cup;' => '&#8746;', '&int;' => '&#8747;',
	'&there4;' => '&#8756;', '&sim;' => '&#8764;',
	'&cong;' => '&#8773;', '&asymp;' => '&#8776;', '&ne;' => '&#8800;', '&equiv;' => '&#8801;',
	'&le;' => '&#8804;', '&ge;' => '&#8805;', '&sub;' => '&#8834;', '&sup;' => '&#8835;',
	'&nsub;' => '&#8836;', '&sube;' => '&#8838;',
	'&supe;' => '&#8839;', '&oplus;' => '&#8853;', '&otimes;' => '&#8855;', '&perp;' => '&#8869;',
	'&sdot;' => '&#8901;', '&lceil;' => '&#8968;', '&rceil;' => '&#8969;', '&lfloor;' => '&#8970;',
	'&rfloor;' => '&#8971;', '&lang;' => '&#9001;',
	'&rang;' => '&#9002;', '&loz;' => '&#9674;', '&spades;' => '&#9824;', '&clubs;' => '&#9827;',
	'&hearts;' => '&#9829;', '&diams;' => '&#9830;'
);


/**
 * Translation of invalid Unicode references range to valid range.
 * These are Windows CP1252 specific characters.
 * They would look weird on non-Windows browsers.
 * If you've ever pasted text from MSWord, you'll understand.
 * @global array
 */
$b2_htmltranswinuni = array(
	'&#128;' => '&#8364;', // the Euro sign
	'&#130;' => '&#8218;',
	'&#131;' => '&#402;',
	'&#132;' => '&#8222;',
	'&#133;' => '&#8230;',
	'&#134;' => '&#8224;',
	'&#135;' => '&#8225;',
	'&#136;' => '&#710;',
	'&#137;' => '&#8240;',
	'&#138;' => '&#352;',
	'&#139;' => '&#8249;',
	'&#140;' => '&#338;',
	'&#142;' => '&#382;',
	'&#145;' => '&#8216;',
	'&#146;' => '&#8217;',
	'&#147;' => '&#8220;',
	'&#148;' => '&#8221;',
	'&#149;' => '&#8226;',
	'&#150;' => '&#8211;',
	'&#151;' => '&#8212;',
	'&#152;' => '&#732;',
	'&#153;' => '&#8482;',
	'&#154;' => '&#353;',
	'&#155;' => '&#8250;',
	'&#156;' => '&#339;',
	'&#158;' => '&#382;',
	'&#159;' => '&#376;'
);


# ** RSS syndication options **
# these options are used by rdf.php (1.0), rss.php (0.92), and rss2.php (2.0)
# length (in words) of excerpts in the RSS feed? 0=unlimited
# Note: this will not apply to html content!
$rss_excerpt_length = 0;
?>