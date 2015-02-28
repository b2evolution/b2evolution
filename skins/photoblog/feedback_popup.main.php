<?php
/**
 * This is the comments-popup page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * It is used to display the blog when no specific page template is available.
 *
 * @package evoskins
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Note: even if we request the same post as $Item above, the following will do more restrictions (dates, etc.)
// Do inits depending on current $disp:
skin_init( $disp );	// disp will normally be "feedback-popup" here


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the
// _html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>

<div class="comments_popup">

<?php

// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
// --------------------------------- END OF MESSAGES ---------------------------------


// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
request_title( array(
		'title_before'=> '<h2>',
		'title_after' => '</h2>',
		'title_none'  => '<h2>&nbsp;</h2>',
		'glue'        => ' - ',
		'title_single_disp' => false,
		'format'      => 'htmlbody',
	) );
// ------------------------------ END OF REQUEST TITLE -----------------------------


// Normally, there should only be one item to display...
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array(
			'before_section_title' => '<h4>',
			'after_section_title'  => '</h4>',
		) );
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