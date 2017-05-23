<?php
/**
 * This is the main dispatcher for the customize interface, to edit collection settings from front-office
 *
 * ---------------------------------------------------------------------------------------------------------------
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT YOU DID NOT LOAD THIS FILE THROUGH A PHP WEB SERVER. 
 * TO GET STARTED, GO TO THIS PAGE: http://b2evolution.net/man/getting-started
 * ---------------------------------------------------------------------------------------------------------------
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package main
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/conf/_config.php';

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once $inc_path.'_main.inc.php';

$Timer->start( 'customize.php' );

// Enable customizer mode:
set_param( 'customizer_mode', 'enable' );

param( 'customizing_url', 'url', NULL, true );
param( 'blog', 'integer', true, true );
param( 'view', 'string', true, true );

// Getting current blog info:
$BlogCache = & get_BlogCache();
/**
 * @var Blog
 */
$Collection = $Blog = & $BlogCache->get_by_ID( $blog, false, false );
if( empty( $Blog ) )
{
	load_funcs( 'skins/_skin.funcs.php' );
	siteskin_init();
	siteskin_include( '_404_blog_not_found.main.php' ); // error
	exit(0);
	// EXIT.
}

// Initialize modes to debug collection settings:
initialize_debug_modes();

load_funcs( 'skins/_skin.funcs.php' );

// Initialize font-awesome icons and use them as a priority over the glyphicons, @see get_icon()
init_fontawesome_icons( 'fontawesome-glyphicons' );

add_js_headline( 'var customizer_url = "'.$customizer_url.'";' );
require_css( 'bootstrap-b2evo_base.bmin.css' );
require_js( '#jquery#' );
require_js( 'src/evo_customizer.js' );

// Send the predefined cookies:
evo_sendcookies();

headers_content_mightcache( 'text/html' );		// In most situations, you do NOT want to cache dynamic content!
?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>" class="evo_customizer__html">
<head>
	<base href="<?php echo $baseurl; ?>">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, follow" />
	<title><?php printf( T_('Customizing Collection: %s'), $Blog->dget( 'shortname', 'htmlhead' ) ); ?></title>
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
</head>
<body<?php skin_body_attrs(); ?>>
	<?php
	// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
	require skin_fallback_path( '_toolbar.inc.php' );
	// ------------------------------- END OF TOOLBAR --------------------------------
	?>
	<div class="evo_customizer__wrapper">
		<div class="evo_customizer__left">
			<iframe id="evo_customizer__backoffice" src="<?php echo $admin_url.'?ctrl=customize&amp;view='.$view.'&amp;blog='.$blog; ?>" data-coll-id="<?php echo $Blog->ID; ?>"></iframe>
		</div>
		<div class="evo_customizer__right">
			<iframe id="evo_customizer__frontoffice" src="<?php echo url_add_param( $customizing_url, 'show_evo_toolbar=0&amp;redir=no' ); ?>" data-coll-url="<?php echo format_to_output( $Blog->get( 'url' ), 'htmlattr' ); ?>"></iframe>
		</div>
		<iframe id="evo_customizer__updater" name="evo_customizer__updater" style="display:none"></iframe>
	</div>
</body>
</html><?php
$Timer->stop( 'customize.php' );
?>