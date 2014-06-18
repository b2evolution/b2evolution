<?php
/**
 * This is b2evolution's formatting config file.
 *
 * This file sets how b2evolution will parse and format texts (posts, comments, etc.)
 * Last significant changes to this file: version 1.6
 *
 * @todo Move as much as possible to $Settings / $UserSettings
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Automatically balance unmatched tags in posts and comments.
 *
 * Choose the formatting options for your posts:
 *  0 to disable
 *  1 to ensable
 *
 * @todo fp>This should be moved to the backoffice. Checkbox in group settings?
 *
 * @global integer 0|1
 */
$use_balanceTags = 1;


/*
 * Disabling XHTML checking for posts is now a Group permission in b2evo's user management
 */

/**
 * Set this to true if you want to enforce XHTML strict
 *
 * @todo fp> Should probably be an additional radio option in group permissions.
 */
$use_strict =  false;

/**
 * Do we want to use XHTML validation for comments?
 *
 * TODO: use this for anonymous comments only.
 */
$use_xhtmlvalidation_for_comments = true;

/**
 * set this to true to allow id && style as core attributes for comments
 * WARNING : This would allow spammers to post hidden content in comments
 *           enable it at your own risk !
 */
$comments_allow_css_tweaks = false;

/**
 * DEPRECATED!!!
 *
 * here is a list of the tags that are allowed in the comments.
 * If XHTML validation is disabled all tags not in this list will be filtered out anyway before we do any checking
 * @deprecated
 */
$comment_allowed_tags = '<p><ul><ol><li><dl><dt><dd><address><blockquote><ins><del><span><bdo><br><em><strong><dfn><code><samp><kdb><var><cite><abbr><acronym><q><sub><sup><tt><i><b><big><small>';



/**
 * Translation of HTML entities and special characters.
 *
 * You should not have to change this.
 *
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



?>
