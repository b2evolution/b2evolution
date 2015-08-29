<?php
/**
 * ==========================================================
 * IMPORTANT: do NOT duplicate this file into a custom skin.
 * If you do, your skin may break at any future core upgrade.
 * ==========================================================
 *
 * This is the HTML header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $xmlsrv_url, $Session, $Hit, $Skin, $app_version;

$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'html_tag'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n"
	                  .'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.locale_lang( false ).'" lang="'.locale_lang( false ).'">'."\n",
	'viewport_tag'  => NULL,
	'generator_tag' => '<meta name="generator" content="b2evolution '.$app_version.'" /> <!-- Please leave this for stats -->'."\n",
	'body_class'    => NULL,
), $params );

// The following is temporary and should be moved to some SiteSkin class
siteskin_init();

init_bubbletip_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) ); // Add jQuery bubbletip plugin
require_js( 'ajax.js', 'blog' );	// Functions to work with AJAX response data
// CSS for IE9. NOTE: Don't use php checking here because of page caching!
add_headline( '<!--[if IE 9 ]>' );
require_css( 'ie9.css', 'blog' );
add_headline( '<![endif]-->' );

echo $params['html_tag'];
?>
<head>
	<?php
	 	// Add viewport tag if provided:
		if( $params['viewport_tag'] == '#responsive#' )
		{	// We requested a responsive viewport:
			echo '<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";
		}
		else
		{
			echo $params['viewport_tag'];
		}
	?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<?php skin_content_meta(); /* Charset in case we can't trust the HTTP header or the page is saved to an .html file */ ?>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( $params );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
	<?php skin_description_tag(); ?>
	<?php skin_keywords_tag(); ?>
	<?php skin_opengraph_tags(); ?>
	<?php robots_tag(); ?>
	<?php
	global $htsrv_url;
	$js_blog_id = "";
	if( ! empty( $Blog ) )
	{ // Set global js var "blog_id"
		$js_blog_id = "\r\n		var blog_id = '".$Blog->ID."';";
	}

	add_js_headline( "// Paths used by JS functions:
		var htsrv_url = '".get_samedomain_htsrv_url()."';"
		.$js_blog_id );

	// Meta tag with generator info (Please leave this for stats)
	echo $params['generator_tag'];

	if( $Blog->get_setting( 'feed_content' ) != 'none' )
	{ // auto-discovery urls
		?>
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
		<?php
	}
	?>
	<link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo $Blog->disp( 'rsd_url', 'raw' ) ?>" />
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
		$Blog->disp_setting( 'head_includes', 'raw');
	?>
</head>

<body<?php skin_body_attrs( array( 'class' => $params['body_class'] ) ); ?>>

<?php
// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
require skin_fallback_path( '_toolbar.inc.php' );
// ------------------------------- END OF TOOLBAR --------------------------------

echo "\n";
if( show_toolbar() )
{
	echo '<div id="skin_wrapper" class="skin_wrapper_loggedin">';
}
else
{
	echo '<div id="skin_wrapper" class="skin_wrapper_anonymous">';
}
echo "\n";
?>
<!-- Start of skin_wrapper -->
