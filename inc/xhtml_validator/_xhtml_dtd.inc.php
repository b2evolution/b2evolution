<?php
/**
 * This file implements the XHTML "DTD" for the validator.
 *
 * Checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $use_strict;
global $comments_allow_css_tweaks;

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
if( $allow_javascript )
{
	$E_script_tags = 'script noscript';
}
else
{
	$E_script_tags = '';
}

if( $use_strict )
{ // Strict
	$E_special_pre = 'br span bdo';
	$E_special = $E_special_pre.' img '.$E_script_tags;
}
else
{ // Transitional
	$E_special_extra = 'img';
	$E_special_basic = 'br span bdo';
	$E_special = $E_special_basic.' '.$E_special_extra.' '.$E_script_tags;
}

if( $use_strict )
{
	$E_fontstyle = 'tt i b big small';				// Strict
}
else
{
	$E_fontstyle_extra = 'big small font';			// Transitional
	$E_fontstyle_basic = 'tt i b u s strike';	// Transitional
	$E_fontstyle = $E_fontstyle_basic.' '.$E_fontstyle_extra;	// Transitional
}

if( $use_strict )
{
	$E_phrase = 'em strong dfn code q samp kbd var cite abbr acronym sub sup'; // Strict
}
else
{
	$E_phrase_extra = 'sub sup'; 																							// Transitional
	$E_phrase_basic = 'em strong dfn code q samp kbd var cite abbr acronym';	// Transitional
	$E_phrase = $E_phrase_basic.' '.$E_phrase_extra; 													// Transitional
}

$E_misc_inline = 'ins del';
$E_misc = $E_misc_inline;
$E_inline = 'a '.$E_special.' '.$E_fontstyle.' '.$E_phrase;
$E_Iinline = '#PCDATA '.$E_inline.' '.$E_misc_inline;
$E_heading = 'h1 h2 h3 h4 h5 h6';

if( $use_strict )
{
	$E_list = 'ul ol dl';				// Strict
}
else
{
	$E_list = 'ul ol dl menu dir';	// Transitional
}

if( $use_strict )
{
	$E_blocktext = 'pre hr blockquote address';			// Strict
}
else
{
	$E_blocktext = 'pre hr blockquote address center';	// Transitional
}

if( $allow_iframes )
{
	$E_block = 'p '.$E_heading.' div '.$E_list.' '.$E_blocktext.' fieldset table iframe';
}
else
{
	$E_block = 'p '.$E_heading.' div '.$E_list.' '.$E_blocktext.' fieldset table';
}

if( $use_strict )
{
	$E_Bblock = $E_block.' '.$E_misc;			// Strict only
}

if( $allow_objects )
{
	$E_Flow = '#PCDATA '.$E_block.' '.$E_inline.' '.$E_misc.' object embed';
}
else
{
	$E_Flow = '#PCDATA '.$E_block.' '.$E_inline.' '.$E_misc ;
}
$E_a_content = '#PCDATA '.$E_special.' '.$E_fontstyle.' '.$E_phrase.' '.$E_misc_inline;

if( $use_strict )
{
	$E_pre_content = '#PCDATA a '.$E_fontstyle.' '.$E_phrase.' '.$E_special_pre.' '.$E_misc_inline; // Strict
}
else
{
	$E_pre_content = '#PCDATA a '.$E_special_basic.' '.$E_fontstyle_basic.' '.$E_phrase_basic.' '.$E_misc_inline; // Transitional
}

// Allowed Attribute classes
// TODO: individual checkboxes for class / style / id
$A_coreattrs = 'class title'.( $allow_css_tweaks ? ' style' : '' )					// 'id' is really nasty
					.( $allow_javascript ? ' onmouseover onmouseout onclick' : '' );
$A_i18n = 'lang xml:lang dir';
$A_attrs = $A_coreattrs.' '.$A_i18n;

if( !$use_strict )
{
	$A_TextAlign = 'align';									// Transitional only
}
else
{
	$A_TextAlign = '';
}

$A_cellhalign = 'align char charoff';
$A_cellvalign = 'valign';

// Array showing what tags are allowed and what their allowed subtags are.
$allowed_tags = array
(
	'body' => $E_Flow, // Remember this is not a true body, just a post body
	'div' => $E_Flow,
	'p' => $E_Iinline,
	'h1' => $E_Iinline,
	'h2' => $E_Iinline,
	'h3' => $E_Iinline,
	'h4' => $E_Iinline,
	'h5' => $E_Iinline,
	'h6' => $E_Iinline,
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
	'li' => $E_Flow,
	'dl' => 'dt dd',
	'dt' => $E_Iinline,
	'dd' => $E_Flow,
);

if( $use_strict )
{
	$allowed_tags += array
	(
		'address' => $E_Iinline,														// Strict
	);
}
else
{
	$allowed_tags += array
	(
		'address' => '#PCDATA '.$E_inline.' '.$E_misc_inline,		// Transitional
	);
}

$allowed_tags += array
	(
		'hr' => '',
		'pre' => $E_pre_content,
	);

if( $use_strict )
{
	$allowed_tags += array
	(
		'blockquote' => $E_Bblock,		// Strict
	);
}
else
{
	$allowed_tags += array
	(
		'blockquote' => $E_Flow,					// Transitional
		'center' => $E_Flow,					// Transitional only
	);
}

$allowed_tags += array
(
	'ins' => $E_Flow,
	'del' => $E_Flow,
	'a' => $E_a_content,
	'span' => $E_Iinline,
	'bdo' => $E_Iinline,
	'br' => '',
	'em' => $E_Iinline,
	'strong' => $E_Iinline,
	'dfn' => $E_Iinline,
	'code' => $E_Iinline,
	'samp' => $E_Iinline,
	'kbd' => $E_Iinline,
	'var' => $E_Iinline,
	'cite' => $E_Iinline,
	'abbr' => $E_Iinline,
	'acronym' => $E_Iinline,
	'q' => $E_Iinline,
	'sub' => $E_Iinline,
	'sup' => $E_Iinline,
	'tt' => $E_Iinline,
	'i' => $E_Iinline,
	'b' => $E_Iinline,
	'big' => $E_Iinline,
	'small' => $E_Iinline,
);

if( !$use_strict )
{
	$allowed_tags += array
	(
		'u' => $E_Iinline,						// Transitional only
		's' => $E_Iinline,						// Transitional only
		'strike' => $E_Iinline,			// Transitional only
		'font' => $E_Iinline,				// Transitional only
	);
}

$allowed_tags += array
(
	'img' => '',
	'fieldset' => '#PCDATA legend '.$E_block.' '.$E_inline.' '.$E_misc,
	'legend' => $E_Iinline,
	'table' => 'caption col colgroup thead tfoot tbody tr',
	'caption' => $E_Iinline,
	'thead' => 'tr',
	'tfoot' => 'tr',
	'tbody' => 'tr',
	'colgroup' => 'col',
	'tr' => 'th td',
	'th' => $E_Flow,
	'td' => $E_Flow,
);

if( $allow_javascript )
{
	$allowed_tags += array
	(
		'script' => '#PCDATA',
		'noscript' => $E_Flow,
	);
}

// Array showing allowed attributes for tags
if( $use_strict )
{
	$allowed_attributes = array
	(	// Strict
		'div' => $A_attrs,
		'p' => $A_attrs,
		'h1' => $A_attrs,
		'h2' => $A_attrs,
		'h3' => $A_attrs,
		'h4' => $A_attrs,
		'h5' => $A_attrs,
		'h6' => $A_attrs,
		'ul' => $A_attrs,
		'ol' => $A_attrs,
		'li' => $A_attrs,
		'dl' => $A_attrs,
		'hr' => $A_attrs,
		'pre' => $A_attrs.' xml:space',
		'a' => $A_attrs.' charset type href hreflang rel rev shape coords name',
		'br' => $A_coreattrs,
		'img' => $A_attrs.' src alt longdesc height width usemap ismap',
		'legend' => $A_attrs,
		'table' => $A_attrs.' summary width border frame rules cellspacing cellpadding',
		'caption' => $A_attrs,
		'tr' => $A_attrs.' '.$A_cellhalign.' '.$A_cellvalign,
		'th' => $A_attrs.' abbr axis headers scope rowspan colspan '.$A_cellhalign.' '.$A_cellvalign,
		'td' => $A_attrs.' abbr axis headers scope rowspan colspan '.$A_cellhalign.' '.$A_cellvalign,
	);
}
else
{
	$allowed_attributes = array
	(	// Transitional
		'div' => $A_attrs.' '.$A_TextAlign,
		'p' => $A_attrs.' '.$A_TextAlign,
		'h1' => $A_attrs.' '.$A_TextAlign,
		'h2' => $A_attrs.' '.$A_TextAlign,
		'h3' => $A_attrs.' '.$A_TextAlign,
		'h4' => $A_attrs.' '.$A_TextAlign,
		'h5' => $A_attrs.' '.$A_TextAlign,
		'h6' => $A_attrs.' '.$A_TextAlign,
		'ul' => $A_attrs.' type compact',
		'ol' => $A_attrs.' type compact start',
		'menu' => $A_attrs.' compact',			// Transitional only
		'dir' => $A_attrs.' compact',			// Transitional only
		'li' => $A_attrs.' type value',
		'dl' => $A_attrs.' compact',
		'hr' => $A_attrs.' align noshade size width',
		'pre' => $A_attrs.' width xml:space',
		'center' => $A_attrs,					// Transitional only
		// sam2kb> TODO: 'name' is deprecated by 'id', we should allow 'id' in <a> tags without 'href' attribute
		'a' => $A_attrs.' charset type href hreflang rel rev shape coords target name',
		'br' => $A_coreattrs.' clear',
		'u' => $A_attrs,						// Transitional only
		's' => $A_attrs,						// Transitional only
		'strike' => $A_attrs,					// Transitional only
		'font' => $A_coreattrs.' '.$A_i18n.' size color face',	// Transitional only
		'img' => $A_attrs.' src alt name longdesc height width usemap ismap align border hspace vspace',
		'legend' => $A_attrs.' align',
		'table' => $A_attrs.' summary width border frame rules cellspacing cellpadding align bgcolor',
		'caption' => $A_attrs.' align',
		'tr' => $A_attrs.' '.$A_cellhalign.' '.$A_cellvalign.' bgcolor',
		'th' => $A_attrs.' abbr axis headers scope rowspan colspan '.$A_cellhalign.' '.$A_cellvalign.' nowrap bgcolor width height',
		'td' => $A_attrs.' abbr axis headers scope rowspan colspan '.$A_cellhalign.' '.$A_cellvalign.' nowrap bgcolor width height',
	);
}
$allowed_attributes += array
(
	'fieldset' => $A_attrs,

	'ins' => $A_attrs.' cite datetime',
	'del' => $A_attrs.' cite datetime',
	'blockquote' => $A_attrs.' cite',
	'span' => $A_attrs,
	'bdo' => $A_coreattrs.' lang xml:lang dir',
	'dt' => $A_attrs,
	'dd' => $A_attrs,

	'address' => $A_attrs,

	'em' => $A_attrs,
	'strong' => $A_attrs,
	'dfn' => $A_attrs,
	'code' => $A_attrs,
	'samp' => $A_attrs,
	'kbd' => $A_attrs,
	'var' => $A_attrs,
	'cite' => $A_attrs,
	'abbr' => $A_attrs,
	'acronym' => $A_attrs,
	'q' => $A_attrs.' cite',
	'sub' => $A_attrs,
	'sup' => $A_attrs,
	'tt' => $A_attrs,
	'i' => $A_attrs,
	'b' => $A_attrs,
	'big' => $A_attrs,
	'small' => $A_attrs,
	'colgroup' => $A_attrs.' span width cellhalign cellvalign',
	'col' => $A_attrs.' span width cellhalign cellvalign',
	'thead' => $A_attrs.' '.$A_cellhalign.' '.$A_cellvalign,
	'tfoot' => $A_attrs.' '.$A_cellhalign.' '.$A_cellvalign,
	'tbody' => $A_attrs.' '.$A_cellhalign.' '.$A_cellvalign,

);

if( $allow_javascript )
{
	$allowed_attributes += array
	(
		'script' => 'type charset src',
		'noscript' => '',
	);
}

if( $allow_iframes )
{
	$allowed_tags += array
	(
		'iframe' => '',
	);
	$allowed_attributes += array
	(
 		'iframe' => $A_attrs.' '.$A_TextAlign.' src width height frameborder marginwidth marginheight scrolling',		// Transitional
	);
}

if( $allow_objects )
{
	$allowed_tags += array
	(
		'object' => 'param embed',
	  'param' => '',
	  'embed' => '',
	);
	$allowed_attributes += array
	(
	  'object' => 'codebase classid id height width align type data wmode',
	  'param' => 'name value',
	  'embed' => 'src type height width wmode quality bgcolor name align pluginspage flashvars allowfullscreen allowscriptaccess',
	);
}



// -----------------------------------------------------------------------------

// DEFINITION of allowed XHTML code for COMMENTS (posted from the public blog pages)


// Allowed Entity classes
$C_E_special_pre = 'br span bdo';
$C_E_special = $C_E_special_pre;
$C_E_fontstyle = 'tt i b big small';
$C_E_phrase = 'em strong dfn code q samp kbd var cite abbr acronym sub sup';
$C_E_misc_inline = 'ins del';
$C_E_misc = $C_E_misc_inline;
$C_E_inline = 'a '.$C_E_special.' '.$C_E_fontstyle.' '.$C_E_phrase;
$C_E_Iinline = '#PCDATA '.$C_E_inline.' '.$C_E_misc_inline;
$C_E_heading = '';
$C_E_list = 'ul ol dl';
$C_E_blocktext = 'hr blockquote address';
$C_E_block = 'p '.$C_E_heading.' div '.$C_E_list.' '.$C_E_blocktext.' table';
$C_E_Bblock = $C_E_block.' '.$C_E_misc;
$C_E_Flow = '#PCDATA '.$C_E_block.' '.$C_E_inline.' '.$C_E_misc;
$C_E_a_content = '#PCDATA '.$C_E_special.' '.$C_E_fontstyle.' '.$C_E_phrase.' '.$C_E_misc_inline;
$C_E_pre_content = '#PCDATA a '.$C_E_fontstyle.' '.$C_E_phrase.' '.$C_E_special_pre.' '.$C_E_misc_inline;

// Allowed Attribute classes
$C_A_coreattrs = 'class title'.( $comments_allow_css_tweaks ? ' style' : '' );  // 'id' is really nasty
$C_A_i18n = 'lang xml:lang dir';
$C_A_attrs = $C_A_coreattrs.' '.$C_A_i18n;
$C_A_cellhalign = 'align char charoff';
$C_A_cellvalign = 'valign';

/**
 * Array showing what tags are allowed and what their allowed subtags are.
 * @global array
 */
$comments_allowed_tags = array
(
	'body' => $E_Flow, // Remember this is not a true body, just a comment body
	'p' => $C_E_Iinline,
	'ul' => 'li',
	'ol' => 'li',
	'li' => $C_E_Flow,
	'dl' => 'dt dd',
	'dt' => $C_E_Iinline,
	'dd' => $C_E_Flow,
	'address' => $C_E_Iinline,
	'hr' => '',
);
if( $use_strict )
{
	$comments_allowed_tags += array
	(
		'blockquote' => $C_E_Bblock,		// XHTML-1.0-Strict
	);
}
else
{
	$comments_allowed_tags += array
	(
		'blockquote' => $C_E_Flow,				// XHTML-1.0-Transitional
	);
}
$comments_allowed_tags += array
(
	'ins' => $C_E_Flow,
	'del' => $C_E_Flow,
//	'a' => $C_E_a_content,  // Allowing this will call for a whole lot of comment spam!!!
	'span' => $C_E_Iinline,
	'bdo' => $C_E_Iinline,
	'br' => '',
	'em' => $C_E_Iinline,
	'strong' => $C_E_Iinline,
	'dfn' => $C_E_Iinline,
	'code' => $C_E_Iinline,
	'samp' => $C_E_Iinline,
	'kbd' => $C_E_Iinline,
	'var' => $C_E_Iinline,
	'cite' => $C_E_Iinline,
	'abbr' => $C_E_Iinline,
	'acronym' => $C_E_Iinline,
	'q' => $C_E_Iinline,
	'sub' => $C_E_Iinline,
	'sup' => $C_E_Iinline,
	'tt' => $C_E_Iinline,
	'i' => $C_E_Iinline,
	'b' => $C_E_Iinline,
	'big' => $C_E_Iinline,
	'small' => $C_E_Iinline,
);


/**
 * Array showing allowed attributes for tags.
 * @global array
 */
$comments_allowed_attributes = array
(
	'p' => $C_A_attrs,
	'ul' => $C_A_attrs,
	'ol' => $C_A_attrs,
	'li' => $C_A_attrs,
	'dl' => $C_A_attrs,
	'dt' => $C_A_attrs,
	'dd' => $C_A_attrs,
	'address' => $C_A_attrs,
	'blockquote' => $C_A_attrs.' cite',
	'ins' => $C_A_attrs.' cite datetime',
	'del' => $C_A_attrs.' cite datetime',
	'a' => $C_A_attrs.' charset type href hreflang rel rev shape coords',
	'span' => $C_A_attrs,
	'bdo' => $C_A_coreattrs.' lang xml:lang dir',
	'br' => $C_A_coreattrs,
	'em' => $C_A_attrs,
	'strong' => $C_A_attrs,
	'dfn' => $C_A_attrs,
	'code' => $C_A_attrs,
	'samp' => $C_A_attrs,
	'kbd' => $C_A_attrs,
	'var' => $C_A_attrs,
	'cite' => $C_A_attrs,
	'abbr' => $C_A_attrs,
	'acronym' => $C_A_attrs,
	'q' => $C_A_attrs.' cite',
	'sub' => $C_A_attrs,
	'sup' => $C_A_attrs,
	'tt' => $C_A_attrs,
	'i' => $C_A_attrs,
	'b' => $C_A_attrs,
	'big' => $C_A_attrs,
	'small' => $C_A_attrs,
);

?>