<?php
/**
 * This is a demo template displaying a form to contact the admin user of the site
 *
 * This template is designed to be used aside of your blog, in case you have a website containing other sections than your blog.
 * This lets you use b2evolution as a contact form handler outside of your blog per se.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles
require_css( 'blog_base.css', 'rsc_url' ); // Default styles for the blog navigation
require_css( 'item_base.css', 'rsc_url' ); // Default styles for the post CONTENT
require_css( 'fp02.css', 'rsc_url' );

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu
init_tokeninput_js();

headers_content_mightcache( 'text/html', 0 );		// Never even think about caching FORMs!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Contact Form Demo'); ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
 <!-- InstanceEndEditable -->
</head>
<body<?php skin_body_attrs(); ?>>
<!-- InstanceBeginEditable name="ToolBar" -->
<?php
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
	$has_errors = 'false';
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
			'has_errors'  => & $has_errors,
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
?>


<?php
	// ----------------------------- MESSAGE FORM ----------------------------
	if( empty( $return ) || $has_errors )
	{	// We are *not* coming back after sending a message:

		if( $has_errors )
		{ // There was some error, the message was not sent
			echo '<p>'.T_('Your message was not sent. You may try again.').'</p>';
		}

		if( empty( $redirect_to ) )
		{	// We haven't asked for a specific return URL, so we'll come back to here with a param.
			$redirect_to = empty( $return ) ? url_add_param( $ReqURI, 'return=1', '&' ) : $ReqURI;
		}

		// Check admin user allow message forms
		$UserCache = & get_UserCache();
		$recipient_User = $UserCache->get_by_ID( $recipient_id );
		$allow_msgform = $recipient_User->get_msgform_possibility();
		// The form, per se:
		switch( $allow_msgform )
		{
			case 'login':
				echo '<p>'.sprintf( T_('Sorry, but you must <a %s>log in</a> before you can contact to me.'), 'href="'.get_login_url( 'contact.php', regenerate_url() ).'"' ).'</p>';
				break;

			case 'PM':
				if( !check_create_thread_limit() )
				{ // the current User didn't reached the thread limit
					// Load classes
					load_class( 'messaging/model/_thread.class.php', 'Thread' );
					load_class( 'messaging/model/_message.class.php', 'Message' );

					// Set global variable to auto define the FB autocomplete plugin field
					$recipients_selected = array( array(
							'id'    => $recipient_User->ID,
							'title' => $recipient_User->login,
						) );
					$edited_Thread = new Thread();
					$edited_Message = new Message();
					$edited_Message->Thread = & $edited_Thread;
					$edited_Thread->recipients = $recipient_User->login;
					param( 'action', 'string', 'new', true );
					param( 'thrdtype', 'string', 'individual', true );
					$params = array(
							'redirect_to' => $redirect_to,
							'allow_select_recipients' => false,
						);

					require $skins_path.'_threads.disp.php';
					break;
				}
				// don't break, because we may let the user to send email

			case 'email':
				if( $recipient_User->accepts_email() )
				{
					require $skins_path.'_msgform.disp.php';
					break;
				}
				// don't break, display the message below

			default:
				echo '<p>'.T_('The administrator of this site has turned off all means of being contacted.').'</p>';
		}
	}
	else
	{	// We are coming back after sending a message:
		echo '<p>'.T_('Thank you for your message. We will reply as soon as possible.').'</p>';

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
