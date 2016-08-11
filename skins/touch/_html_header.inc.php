<?php
/**
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

global $xmlsrv_url;

$params = array_merge( array(
	'auto_pilot' => 'seo_title',
), $params );

init_bubbletip_js( 'blog' ); // Add jQuery bubbletip plugin
init_results_js( 'blog' ); // Add functions to work with Results tables
require_js( 'ajax.js', 'blog' );	// Functions to work with AJAX response data
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head profile="http://gmpg.org/xfn/11">
	<?php skin_content_meta(); /* Charset for static pages */ ?>
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
	$js_blog_id = "";
	if( ! empty( $Blog ) )
	{ // Set global js var "blog_id"
		$js_blog_id = "\r\n		var blog_id = '".$Blog->ID."';";
	}

	add_js_headline( "// Paths used by JS functions:
		var htsrv_url = '".get_htsrv_url()."';
		var restapi_url = '".get_restapi_url()."';"
		.$js_blog_id );
	?>
	<meta name="generator" content="b2evolution <?php app_version(); ?>" /> <!-- Please leave this for stats -->
	<?php
	if( $Blog->get_setting( 'feed_content' ) != 'none' )
	{ // auto-discovery urls
		?>
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
		<?php
	}
	?>
	<link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo $Blog->disp( 'rsd_url', 'raw' ) ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
		$Blog->disp_setting( 'head_includes', 'raw');
	?>
</head>

<body<?php skin_body_attrs( array( 'class' => 'low-contrast-linen-wptouch-bg' ) ); ?>>
<?php
$Blog->disp_setting( 'body_includes', 'raw');

// Call BeforeSkinWrapper to display additional info:
$Plugins->trigger_event( 'BeforeSkinWrapper' );

?>