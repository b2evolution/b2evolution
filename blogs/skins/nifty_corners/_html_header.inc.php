<?php
/**
 * This is the header include template.
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 * @subpackage nifty_corners
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

skin_content_header();	// Sets charset!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		request_title( '', ' - ', ' - ', 'htmlhead', array(
		 ) );
		$Blog->disp('name', 'htmlhead');
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	<link rel="stylesheet" type="text/css" href="rsc/nifty_corners.css" />
	<link rel="stylesheet" type="text/css" href="rsc/nifty_print.css" media="print" />
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
	?>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/functions.js"></script>
	<script type="text/javascript" src="rsc/nifty_corners.js"></script>
	<script type="text/javascript">
		window.onload=function()
		{
			if(!NiftyCheck())
					return;
			Rounded("div.outerwrap","all","transparent","#fff","");
			Rounded("div.posts","all","transparent","#fff","");
			Rounded("div.bSideBar","all","transparent","#fff","");
			Rounded("div.bTitle","top","#fff","#06a3c4","smooth");
		}
	</script>
</head>

<body>

<?php
// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
require $skins_path.'_toolbar.inc.php';
// -------------------------------- END OF HEADER --------------------------------
?>