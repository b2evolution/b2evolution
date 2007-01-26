<?php
/**
 * This is the comments-popup page template.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------- HEADER INCLUDED HERE -----------------------------
require dirname(__FILE__).'/_header.inc.php';
// ------------------------------- END OF HEADER --------------------------------

?>

<div class="comments_popup">

<?php

// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
$Messages->disp( );
// --------------------------------- END OF MESSAGES ---------------------------------


// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
request_title( '<h2>', '</h2>', ' - ', 'htmlbody', array(
	 ), false, '<h2>&nbsp;</h2>' );
// ------------------------------ END OF REQUEST TITLE -----------------------------


// Normally, there should only be one item to display...
while( $Item = & $MainList->get_item() )
{
	/**
	 * this includes the feedback and a form to add a new comment depending on request
	 */
	$disp_comments = 1;					// Display the comments if requested
	$disp_comment_form = 1;			// Display the comments form if comments requested
	$disp_trackbacks = 1;				// Display the trackbacks if requested
	$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
	$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
	require( dirname(__FILE__).'/_feedback.php' );
}
?>

</div>

<p class="center"><strong><a href="javascript:window.close()">close this window</a></strong></p>

<?php

// --------------------------- FOOTER INCLUDED HERE -----------------------------
require dirname(__FILE__).'/_footer.inc.php';
// ------------------------------- END OF FOOTER --------------------------------

?>