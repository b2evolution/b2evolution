<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage kubrick
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>


<?php
// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY heder by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<div id="content" class="narrowcolumn">

<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	$Messages->disp( '<div class="action_messages">', '</div>' );
	// --------------------------------- END OF MESSAGES ---------------------------------
?>


<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( '<h2>', '</h2>' );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
?>


<?php
if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

if( isset($MainList) ) while( $Item = & $MainList->get_item() )
{
?>
	<div class="post post<?php $Item->status( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
		<?php
			locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
			$Item->anchor(); // Anchor for permalinks to refer to
		?>

		<h2><?php $Item->permanent_link( '#title#' ); ?></h2>
		<small><?php $Item->issue_time( 'F jS, Y' ); ?> <!-- by <?php $Item->author( '<strong>', '</strong>' ); ?> --></small>

		<?php
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'image_size'	=>	'fit-400x320',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<p class="postmetadata">Posted in <?php $Item->categories(); ?>
		| <?php $Item->edit_link( '', ' | '); ?>
		<?php $Item->feedback_link( 'feedbacks', '' ) ?></p>
	</div>

	<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
}
?>

<?php
	// Links to list pages:
	if( isset($MainList) ) $MainList->page_links( '<div class="navigation">', '</div>' );
?>


</div>


<?php
// ------------------------- SIDEBAR INCLUDED HERE --------------------------
skin_include( '_sidebar.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ----------------------------- END OF SIDEBAR -----------------------------
?>


<?php
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
