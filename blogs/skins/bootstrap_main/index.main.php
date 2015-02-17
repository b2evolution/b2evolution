<?php
/**
 * This is the main/default page template for the "custom" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage bootstrap_main
 *
	 * @version $Id: index.main.php 8273 2015-02-16 16:19:27Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '5.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 5.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// Check if current page has a big picture as background
$is_pictured_page = in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied' ) );

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
	'html_tag' => '<!DOCTYPE html>'."\r\n"
	             .'<html lang="'.locale_lang( false ).'">',
	'viewport_tag' => '#responsive#',
	'body_class' => ( $is_pictured_page ? 'pictured' : '' ),
) );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

if( $is_pictured_page )
{ // Display a picture from skin setting as background image
	global $media_path, $media_url;
	$bg_image = $Skin->get_setting( 'front_bg_image' );
	if( ! empty( $bg_image ) && file_exists( $media_path.$bg_image ) )
	{ // If it exists in media folder
		echo '<div id="bg_picture"><img src="'.$media_url.$bg_image.'" /></div>';
	}
}
?>

<div class="container body">
	<div class="row">
		<div class="col-md-12<?php echo $disp == 'front' ? ' col-half-width' : ''; ?>">

<div class="PageTop">
	<?php
		// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'         => '<div class="$wi_class$">',
				'block_end'           => '</div>',
				'block_display_title' => false,
				'list_start'          => '<ul>',
				'list_end'            => '</ul>',
				'item_start'          => '<li>',
				'item_end'            => '</li>',
			) );
		// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
	?>
</div>

<div class="pageHeader">
	<?php
		// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'       => '<div class="$wi_class$">',
				'block_end'         => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end'   => '</h1>',
			) );
		// ----------------------------- END OF "Header" CONTAINER -----------------------------
	?>
</div>

		</div>
	</div>

<!-- =================================== START OF MAIN AREA =================================== -->
	<div class="row">
		<div class="col-md-12<?php echo $disp == 'front' ? ' col-half-width' : ''; ?>">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'block_start' => '<ul class="pager">',
				'prev_start'  => '<li class="previous">',
				'prev_end'    => '</li>',
				'next_start'  => '<li class="next">',
				'next_end'    => '</li>',
				'block_end'   => '</ul>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

	<?php
		// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
		request_title( array(
				'title_before'=> '<h2>',
				'title_after' => '</h2>',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => true,
				'format'      => 'htmlbody',
				'login_text'  => '',
				'lostpassword_text' => '',
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
	// Go Grab the featured post:
	if( $Item = & get_featured_Item() )
	{ // We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block' => true,
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'   => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'   => 'featured_post',
				'image_size'	 =>	'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
	?>

	<?php
	if( $disp != 'front' && $disp != 'download' && $disp != 'search' )
	{
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="center"><ul class="pagination">',
				'block_end' => '</ul></div>',
				'page_current_template' => '<span><b>$page_num$</b></span>',
				'page_item_before' => '<li>',
				'page_item_after' => '</li>',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>


	<?php
		// --------------------------------- START OF POSTS -------------------------------------
		// Display message if no post:
		display_if_empty();

		while( $Item = & mainlist_get_item() )
		{ // For each blog post, do everything below up to the closing curly brace "}"

			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
					'image_size'   => 'fit-400x320',
					// Comment template
					'comment_start'         => '<div class="panel panel-default">',
					'comment_end'           => '</div>',
					'comment_title_before'  => '<div class="panel-heading">',
					'comment_title_after'   => '',
					'comment_rating_before' => '<div class="comment_rating floatright">',
					'comment_rating_after'  => '</div>',
					'comment_text_before'   => '</div><div class="panel-body">',
					'comment_text_after'    => '',
					'comment_info_before'   => '<div class="bCommentSmallPrint">',
					'comment_info_after'    => '</div></div>',
					'preview_start'         => '<div class="panel panel-warning" id="comment_preview">',
					'preview_end'           => '</div>',
					// Comment form
					'form_title_start'      => '<div class="panel '.( $Session->get('core.preview_Comment') ? 'panel-danger' : 'panel-default' )
					                           .' comment_form"><div class="panel-heading"><h3>',
					'form_title_end'        => '</h3></div>',
					'after_comment_form'    => '</div>',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------

		} // ---------------------------------- END OF POSTS ------------------------------------
	?>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="center"><ul class="pagination">',
				'block_end' => '</ul></div>',
				'page_current_template' => '<span><b>$page_num$</b></span>',
				'page_item_before' => '<li>',
				'page_item_after' => '</li>',
				'prev_text' => '&lt;&lt;',
				'next_text' => '&gt;&gt;',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	}
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
				'disp_page'   => '',		// We already handled this case above
				'author_link_text' => 'preferredname',
				'profile_tabs' => array(
					'block_start'         => '<ul class="nav nav-tabs profile_tabs">',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
					'item_selected_start' => '<li class="active">',
					'item_selected_end'   => '</li>',
					'block_end'           => '</ul>',
				),
				'pagination' => array(
					'block_start'           => '<div class="center"><ul class="pagination">',
					'block_end'             => '</ul></div>',
					'page_current_template' => '<span><b>$page_num$</b></span>',
					'page_item_before'      => '<li>',
					'page_item_after'       => '</li>',
					'prev_text'             => '&lt;&lt;',
					'next_text'             => '&gt;&gt;',
				),
				'form_title_login' => T_('Log in to your account'),
				'form_class_login' => 'wrap-form-login',
				'form_title_lostpass' => get_request_title(),
				'form_class_lostpass' => 'wrap-form-lostpass',
				'login_form_inskin' => false,
				'login_page_before' => '<div class="$form_class$">',
				'login_page_after'  => '</div>',
				'login_form_before' => '<div class="panel panel-default">'
																	.'<div class="panel-heading">'
																		.'<h3 class="panel-title">$form_title$</h3>'
																	.'</div>'
																	.'<div class="panel-body">',
				'login_form_after'  => '</div></div>',
				'login_form_class'  => 'form-login',
				'profile_avatar_before' => '<div class="panel panel-default profile_avatar">',
				'profile_avatar_after' => '</div>',
				'search_input_before'  => '<div class="input-group">',
				'search_input_after'   => '',
				'search_submit_before' => '<span class="input-group-btn">',
				'search_submit_after'  => '</span></div>',
				// Comment template
				'comment_avatar_position' => 'before_text',
				'comment_start'         => '<div class="panel panel-default">',
				'comment_end'           => '</div>',
				'comment_post_before'   => '<div class="panel-heading"><h4 class="bTitle floatleft">',
				'comment_post_after'    => '</h4>',
				'comment_title_before'  => '<div class="floatright">',
				'comment_title_after'   => '</div><div class="clear"></div></div><div class="panel-body">',
				'comment_rating_before' => '<div class="comment_rating floatright">',
				'comment_rating_after'  => '</div>',
				'comment_text_before'   => '',
				'comment_text_after'    => '',
				'comment_info_before'   => '<div class="bCommentSmallPrint">',
				'comment_info_after'    => '</div></div>',
				'preview_start'         => '<div class="panel panel-warning" id="comment_preview">',
				'preview_end'           => '</div>',
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

		</div>
	</div>
</div>

<!-- End of skin_wrapper -->
</div>

<!-- =================================== START OF FOOTER =================================== -->
<div class="footer">
	<div class="container">
		<div class="row">
			<div class="col-md-12 center">
	<?php
		// Display container and contents:
		skin_container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
	<p>
		<?php
			// Display footer text (text can be edited in Blog Settings):
			$Blog->footer_text( array(
					'before' => '',
					'after'  => ' &bull; ',
				) );

		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
		?>

		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before' => '',
					'after'  => ' &bull; ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
			// Display a link to help page:
			$Blog->help_link( array(
					'before' => ' ',
					'after'  => ' &bull; ',
					'text'   => T_('Help'),
				) );
		?>

		<?php display_param_link( $skin_links ) ?> by <?php display_param_link( $francois_links ) ?>

		<?php
			// Display additional credits:
			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start' => '&bull;',
					'list_end'   => ' ',
					'separator'  => '&bull;',
					'item_start' => ' ',
					'item_end'   => ' ',
				) );
		?>
	</p>
			</div>
		</div>
	</div>
</div>

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>