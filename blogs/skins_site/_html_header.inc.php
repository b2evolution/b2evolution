<?php
/**
 * This is the HTML header include template.
 *
 * This will be included ONLY for sitewide pages (like a global 404).
 * When a blog is displayed, it is /skins/_html_header.inc.php that will be used
 *
 * @package siteskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $page_title;

// The following is temporary and should be moved to some SiteSkin class
siteskin_init();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		echo $page_title;
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
	<meta name="generator" content="b2evolution <?php app_version(); ?>" /> <!-- Please leave this for stats -->
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
</head>

<body<?php skin_body_attrs(); ?>>

<?php
/* In the future we probably want to include the toolbar here...

// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
require $skins_path.'_toolbar.inc.php';
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

*/
?>
<!-- Start of skin_wrapper -->
