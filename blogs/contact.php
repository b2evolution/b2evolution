<?php
/**
 * TODO: dh> desc...
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

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_main.inc.php';

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
	// fp> TODO: inject a flag into session to indicate mail has been successfully sent and do not display the form on success (it's confusing)
	// Daniel, if you could show me how you would do it, it would be nice (I did not look into naming conventions so far 'core.xxx' etc).
	// dh> should the user return to this page at all? At least redirect_to should be passed/used.
	// Then, a flag may be set in message_send.php, but it would fail, if the User sends a mail
	// to some user (e.g. through a Comment) and then clicks on the contact link.
	// Registering a shutdown function might help: check $success_mail and set your very own flag
	// (e.g. "core.contact.sent" with a decent timeout). But this might fail, if the browser gets
	// here again, while the shutdown is still in progress.
	// Another option:
	//  - if redirect_url is not empty, do not care (because we're not coming here again
	//  - if it's empty, explictly set it to the current URL (to work against wrong referer data),
	//    _and_ add a GET param like "msg_sent=1".
	// IMHO this is kinda trivial, but the most robust method.
	require $skins_path.'_msgform.php';
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
</body>
<!-- InstanceEnd --></html>