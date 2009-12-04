<?php
/**
 * This is a demo template displaying a form to contact the admin user of the site
 *
 * This template is designed to be used aside of your blog, in case you have a website containing other sections than your blog.
 * This lets you use b2evolution as a contact form handler outside of your blog per se.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * @subpackage noskin
 */

// The User ID of the administrator:
$recipient_id = 1;

// Tie this to no blog in particular. (Do not include a link to any blog in the emails you will receive).
$blog = 0;

// This is the page where we want to go after sending an email. (This page should be able to display $Messages)
// If empty, we will default to return to the same page, but you could put any URL here.
$redirect_to = '';

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_main.inc.php';

load_funcs( 'skins/_skin.funcs.php' );

// Are we returning to this page?
param( 'return', 'integer', 0 );

// Note: This is an interactive page: not a good candidate for caching.

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu

headers_content_mightcache( 'text/html', 0 );		// Never even think about caching FORMs!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Contact Form Demo'); ?></title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="rsc/css/fp02.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
 <!-- InstanceEndEditable -->
</head>
<body>
<!-- InstanceBeginEditable name="ToolBar" -->
<?php
	// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
	require $skins_path.'_toolbar.inc.php';
	// ------------------------------- END OF TOOLBAR --------------------------------
	echo "\n";
	if( is_logged_in() )
	{
		echo '<div id="skin_wrapper" class="skin_wrapper_loggedin">';
	}
	else
	{
		echo '<div id="skin_wrapper" class="skin_wrapper_anonymous">';
	}
	echo "\n";
?>
<!-- InstanceEndEditable -->
<div class="pageHeader">
<!-- InstanceBeginEditable name="NavBar2" -->
<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => '<div class="NavBar">',
						'block_end' => '</div>',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'NavButton2',
						'link_default_class' => 'NavButton2',
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------
?>
<!-- InstanceEndEditable -->
<div class="pageTitle">
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Contact Form Demo') ?><!-- InstanceEndEditable --></h1>
</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo displays a form to contact the site admin.') ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->


<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
?>


<?php
	// ----------------------------- MESSAGE FORM ----------------------------
	if( empty( $return ) )
	{	// We are *not* coming back after sending a message:

		if( empty( $redirect_to ) )
		{	// We haven't asked for a specific return URL, so we'll come back to here with a param.
			$redirect_to = url_add_param( $ReqURI, 'return=1', '&' );
		}

		// The form, per se:
		require $skins_path.'_msgform.disp.php';
	}
	else
	{	// We are coming back after sending a message:

		echo '<p>'.T_('Thank you for your message. I will reply as soon as possible.').'</p>';

		// This is useful for testing but does not really make sense on production:
		echo '<p><a href="'.regenerate_url().'">'.T_('Send another message?').'</a></p>';
	}
	// ------------------------- END OF MESSAGE FORM -------------------------
?>





<!-- InstanceEndEditable --></div>
<div class="footer">
This is a demo page for <a href="http://b2evolution.net/">b2evolution</a>.
<!-- InstanceBeginEditable name="Baseline" -->
<?php echo '</div>' ?>
<!-- InstanceEndEditable --></div>
</body>
<!-- InstanceEnd --></html>