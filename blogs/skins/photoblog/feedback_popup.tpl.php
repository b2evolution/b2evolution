<?php
/**
 * This is the comments-popup page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * It is used to display the blog when no specific page template is available.
 *
 * @package evoskins
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Note: even if we request the same post as $Item above, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_page') );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the
// _html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>

<div class="comments_popup">

<?php

// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
$Messages->disp( '<div class="action_messages">', '</div>' );
// --------------------------------- END OF MESSAGES ---------------------------------


// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
request_title( '<h2>', '</h2>', ' - ', 'htmlbody', array(
	 ), false, '<h2>&nbsp;</h2>' );
// ------------------------------ END OF REQUEST TITLE -----------------------------


// Normally, there should only be one item to display...
while( $Item = & $MainList->get_item() )
{
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php' );
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_feedback.inc.php file into the current skin folder.
	// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
}
?>

</div>

<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>