<?php
/*
 * b2evolution formatting config
 * Version of this file: 0.8.9
 *
 * This sets how posts and comments are formatted
 */


// ** Formatting **

# Choose the formatting options for your posts:
# 0 to disable
# 1 to ensable
$use_balanceTags = 1;		// 0,1 automatically balance unmatched tags in posts and comments. 


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


/*
 * HTML Checker params:
 *
 * The params are defined twice: once for the posts and once for the comments.
 * Typically you'll be mre restrictive on comments.
 */

// DEFINITION of allowed XHTML code for POSTS (posted in the backoffice)

// Allowed Entity classes
define('E_SPECIAL_CONTENTS', 'br hr span bdo img');
define('E_MISC_CONTENTS', 'ins del');
define('E_PHRASE_CONTENTS', 'em strong i b dfn code q samp kbd var cite abbr acronym sub sup');
define('E_PURE_INLINE_CONTENTS', E_SPECIAL_CONTENTS.' '.E_PHRASE_CONTENTS.' a #PCDATA');
define('E_PURE_BLOCK_CONTENTS', 'div dl ul ol blockquote p table');
define('E_TABLE_CONTENTS', 'tr');
define('E_TR_CONTENTS', 'td');
define('E_INLINE_CONTENTS', E_PURE_INLINE_CONTENTS.' '.E_MISC_CONTENTS);
define('E_A_CONTENT_CONTENTS', E_SPECIAL_CONTENTS.' '.E_PHRASE_CONTENTS.' '.E_MISC_CONTENTS.' #PCDATA');
define('E_BLOCK_CONTENTS', E_PURE_BLOCK_CONTENTS.' '.E_MISC_CONTENTS);
define('E_FLOW_CONTENTS', E_PURE_BLOCK_CONTENTS.' '.E_PURE_INLINE_CONTENTS.' '.E_MISC_CONTENTS);

// Allowed Attribute classes
define('A_CORE_ATTRS', 'title');
define('A_I18N_ATTRS', 'xml:lang lang dir');
define('A_ATTRS', A_CORE_ATTRS.' '.A_I18N_ATTRS.' class');
define('A_IMG_ATTRS', A_ATTRS.' src alt longdesc height width border hspace vspace align'); 
define('A_CITE_ATTRS', A_ATTRS.' cite');
define('A_ANCHOR_ATTRS', A_ATTRS.' href hreflang target');
define('A_LIST_ATTRS', A_ATTRS.' type');
define('A_LISTITEM_ATTRS', A_LIST_ATTRS.' value');
define('A_TABLE_ELEMENT_ATTRS', A_ATTRS.' align bgcolor bordercolor bordercolordark bordercolorlight');
define('A_TABLE_ATTRS', A_TABLE_ELEMENT_ATTRS.' border cols rules summary cellpadding cellspacing');

// Array showing what tags are allowed and what their allowed subtags are.
$allowed_tags = array
(
	'body' => E_FLOW_CONTENTS,
	'div' => E_FLOW_CONTENTS,
	'table' => E_TABLE_CONTENTS,
	'tr' => E_TR_CONTENTS,
	'td' => E_FLOW_CONTENTS,
	'p' => E_INLINE_CONTENTS,
	'blockquote' => E_FLOW_CONTENTS,		// fp ? E_BLOCK_CONTENTS,
	'ins' => E_FLOW_CONTENTS,
	'del' => E_FLOW_CONTENTS,
	// Lists
	'ul' => 'li',
	'ol' => 'li',
	'li' => E_FLOW_CONTENTS,
	'dl' => 'dt dd',
	'dt' => E_INLINE_CONTENTS,
	'dd' => E_FLOW_CONTENTS,
	// Inline elements
	'br' => '',
	'hr' => '',
	'img' => '',
	'em' => E_INLINE_CONTENTS,
	'strong' => E_INLINE_CONTENTS,
	'i' => E_INLINE_CONTENTS,
	'b' => E_INLINE_CONTENTS,
	'dfn' => E_INLINE_CONTENTS,
	'code' => E_INLINE_CONTENTS,
	'q' => E_INLINE_CONTENTS,
	'samp' => E_INLINE_CONTENTS,
	'kbd' => E_INLINE_CONTENTS,
	'var' => E_INLINE_CONTENTS,
	'cite' => E_INLINE_CONTENTS,
	'abbr' => E_INLINE_CONTENTS,
	'acronym' => E_INLINE_CONTENTS,
	'sub' => E_INLINE_CONTENTS,
	'sup' => E_INLINE_CONTENTS,
	'a' => E_A_CONTENT_CONTENTS
);

// Array showing allowed attributes for tags
$allowed_attribues = array
(
	'br' => A_CORE_ATTRS,
	'hr' => A_CORE_ATTRS,
	'img' => A_IMG_ATTRS,
	'span' => A_ATTRS,
	'bdo' => A_ATTRS,
	'em' => A_ATTRS,
	'strong' => A_ATTRS,
	'i' => A_ATTRS,
	'b' => A_ATTRS,
	'dfn' => A_ATTRS,
	'code' => A_ATTRS,
	'q' => A_CITE_ATTRS,
	'abbr' => A_ATTRS,
	'acronym' => A_ATTRS,
	'sub' => A_ATTRS,
	'sup' => A_ATTRS,
	'a' => A_ANCHOR_ATTRS,
	'blockquote' => A_CITE_ATTRS,
	'ul' => A_LIST_ATTRS,
	'ol' => A_LIST_ATTRS,
	'dl' => A_ATTRS,
	'li' => A_LISTITEM_ATTRS,
	'dt' => A_ATTRS,
	'dd' => A_ATTRS,
	'p' => A_ATTRS,
	'table' => A_TABLE_ATTRS,
	'tr' => A_TABLE_ELEMENT_ATTRS,
	'td' => A_TABLE_ELEMENT_ATTRS,
	'div' => A_ATTRS
);

// Array showing URI attributes
$uri_attrs = array
(
	'href',
	'src',
	'cite',
	'longdesc'
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
$comment_allowed_tags = '<a><strong><em><b><i><del><ins><dfn><code><q><samp><kdb><var><cite><abbr><acronym><sub><sup><dl><ul><ol><li><p><br><bdo><dt><dd>';

// Allowed Entity classes
define('COM_E_SPECIAL_CONTENTS', 'br bdo');
define('COM_E_MISC_CONTENTS', 'ins del');
define('COM_E_PHRASE_CONTENTS', 'em strong i b dfn code q samp kbd var cite abbr acronym sub sup');
define('COM_E_PURE_INLINE_CONTENTS', COM_E_SPECIAL_CONTENTS.' '.COM_E_PHRASE_CONTENTS.' a #PCDATA');
define('COM_E_PURE_BLOCK_CONTENTS', 'dl ul ol p');
define('COM_E_INLINE_CONTENTS', COM_E_PURE_INLINE_CONTENTS.' '.COM_E_MISC_CONTENTS);
define('COM_E_A_CONTENT_CONTENTS', COM_E_SPECIAL_CONTENTS.' '.COM_E_PHRASE_CONTENTS.' '.COM_E_MISC_CONTENTS.' #PCDATA');
define('COM_E_BLOCK_CONTENTS', COM_E_PURE_BLOCK_CONTENTS.' '.COM_E_MISC_CONTENTS);
define('COM_E_FLOW_CONTENTS', COM_E_PURE_BLOCK_CONTENTS.' '.COM_E_PURE_INLINE_CONTENTS.' '.COM_E_MISC_CONTENTS);

// Allowed Attribute classes
define('COM_A_CORE_ATTRS', 'title');
define('COM_A_I18N_ATTRS', 'xml:lang lang dir');
define('COM_A_ATTRS', COM_A_CORE_ATTRS.' '.COM_A_I18N_ATTRS);
define('COM_A_CITE_ATTRS', COM_A_ATTRS.' cite');
define('COM_A_ANCHOR_ATTRS', COM_A_ATTRS.' href hreflang');
define('COM_A_LIST_ATTRS', COM_A_ATTRS.' type');
define('COM_A_LISTITEM_ATTRS', COM_A_LIST_ATTRS.' value');
define('COM_A_STYLE_ATTRS', 'class');
# Beware of the STYLE attribute: untrusted bloggers could use something like this:
# style="background-image:url(javascript:somethingevil())" ..
# this cannot be checked even with the current XHTML validating checker.

// Array showing what tags are allowed and what their allowed subtags are.
$comments_allowed_tags = array
(
	'body' => COM_E_FLOW_CONTENTS,
	'div' => COM_E_FLOW_CONTENTS,
	'p' => COM_E_INLINE_CONTENTS,
	'ins' => COM_E_FLOW_CONTENTS,
	'del' => COM_E_FLOW_CONTENTS,
	// Lists
	'ul' => 'li',
	'ol' => 'li',
	'li' => COM_E_FLOW_CONTENTS,
	'dl' => 'dt dd',
	'dt' => COM_E_INLINE_CONTENTS,
	'dd' => COM_E_FLOW_CONTENTS,
	// Inline elements
	'br' => '',
	'em' => COM_E_INLINE_CONTENTS,
	'strong' => COM_E_INLINE_CONTENTS,
	'i' => COM_E_INLINE_CONTENTS,
	'b' => COM_E_INLINE_CONTENTS,
	'dfn' => COM_E_INLINE_CONTENTS,
	'code' => COM_E_INLINE_CONTENTS,
	'q' => COM_E_INLINE_CONTENTS,
	'samp' => COM_E_INLINE_CONTENTS,
	'kbd' => COM_E_INLINE_CONTENTS,
	'var' => COM_E_INLINE_CONTENTS,
	'cite' => COM_E_INLINE_CONTENTS,
	'abbr' => COM_E_INLINE_CONTENTS,
	'acronym' => COM_E_INLINE_CONTENTS,
	'sub' => COM_E_INLINE_CONTENTS,
	'sup' => COM_E_INLINE_CONTENTS,
	'a' => COM_E_A_CONTENT_CONTENTS
);

// Array showing allowed attributes for tags
$comments_allowed_attribues = array
(
	'br' => COM_A_CORE_ATTRS,
	'bdo' => COM_A_ATTRS,
	'em' => COM_A_ATTRS,
	'strong' => COM_A_ATTRS,
	'i' => COM_A_ATTRS,
	'b' => COM_A_ATTRS,
	'dfn' => COM_A_ATTRS,
	'code' => COM_A_ATTRS,
	'q' => COM_A_CITE_ATTRS,
	'abbr' => COM_A_ATTRS,
	'acronym' => COM_A_ATTRS,
	'sub' => COM_A_ATTRS,
	'sup' => COM_A_ATTRS,
	'a' => COM_A_ANCHOR_ATTRS,
	'ul' => COM_A_LIST_ATTRS,
	'ol' => COM_A_LIST_ATTRS,
	'dl' => COM_A_ATTRS,
	'li' => COM_A_LISTITEM_ATTRS,
	'dt' => COM_A_ATTRS,
	'dd' => COM_A_ATTRS,
	'p' => COM_A_ATTRS,
);

// Array showing URI attributes
$comments_uri_attrs = array
(
	'href',
	'src',
	'cite',
	'longdesc'
);

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


# Translation of HTML entities and special characters
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

# Translation of invalid Unicode references range to valid range
# these are Windows CP1252 specific characters
# they would look weird on non-Windows browsers
# if you've ever pasted text from MSWord, you'll understand
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