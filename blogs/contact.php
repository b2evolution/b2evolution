<?php
/**
 * This is a demo template displaying a form to contact the admin user of the site
 *
 * This template is designed to be used aside of your blog, in case you have a website containing other sections than your blog.
 * This lets you use b2evolution as a contact form handler outside of your blog per se.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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


// Are we returning to this page?
param( 'return', 'integer', 0 );
	

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Contact Form Demo'); ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
 <!-- InstanceEndEditable -->
<link rel="stylesheet" href="rsc/css/fp02.css" type="text/css" />
</head>
<body>
<div class="pageHeader">
<div class="pageHeaderContent">

<!-- InstanceBeginEditable name="NavBar2" -->
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------

	// This section is OPTIONAL. Delete it if you don't need it.

	$display_blog_list = 1; // forced

	# this is what will start and end your blog links
	$blog_list_start = '<div class="NavBar">';
	$blog_list_end = '</div>';
	# this is what will separate your blog links
	$blog_item_start = '';
	$blog_item_end = '';
	# This is the class of for the selected blog link:
	$blog_selected_link_class = 'NavButton2';
	# This is the class of for the other blog links:
	$blog_other_link_class = 'NavButton2';
	# This is additionnal markup before and after the selected blog name
	$blog_selected_name_before = '<span class="small">';
	$blog_selected_name_after = '</span>';
	# This is additionnal markup before and after the other blog names
	$blog_other_name_before = '<span class="small">';
	$blog_other_name_after = '</span>';
	// Include the bloglist
	require( $skins_path.'_bloglist.php');
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
<!-- InstanceEndEditable -->

<div class="NavBar">
<div class="pageTitle">
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Contact Form Demo') ?><!-- InstanceEndEditable --></h1>
</div>
</div>

<div class="pageHeaderEnd"></div>

</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo displays a form to contact the site admin') ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->


<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	if( empty( $preview ) ) $Messages->disp( );
	// fp>> TODO: I think we should rather forget the messages here so they don't get displayed again. (this TODO pertains to skins actually)
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
		require $skins_path.'_msgform.php';
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
<table cellspacing="3" class="wide">
  <tr>
  <td class="cartouche">Original page design by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> </td>

	<td class="cartouche" align="right"> <a href="http://b2evolution.net/" title="b2evolution home"><img src="rsc/img/b2evolution_button.png" alt="b2evolution" width="80" height="15" class="middle" /></a></td>
  </tr>
</table>
<p class="baseline"><!-- InstanceBeginEditable name="Baseline" -->

<!-- InstanceEndEditable --></p>
<?php 
	debug_info();
?>
</body>
<!-- InstanceEnd --></html>