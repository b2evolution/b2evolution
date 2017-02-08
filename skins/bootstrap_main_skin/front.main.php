<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage bootstrap_main
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
	'body_class' => 'pictured',
) );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

// Display a picture from skin setting as background image
global $media_path, $media_url;
$bg_image = $Skin->get_setting( 'front_bg_image' );
echo '<div class="evo_pictured_layout">';
if( ! empty( $bg_image ) && file_exists( $media_path.$bg_image ) )
{ // If it exists in media folder
	echo '<img class="evo_pictured__image" src="'.$media_url.$bg_image.'" />';
}

?>


<div class="container main_page_wrapper">

<div class="row">

	<div class="col-md-12 front_main_area">

		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- ================================= START OF MAIN AREA ================================== -->

		<?php
		if( ! in_array( $disp, array( 'login', 'lostpassword', 'register', 'activateinfo', 'access_requires_login' ) ) )
		{ // Don't display the messages here because they are displayed inside wrapper to have the same width as form
			// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
			messages( array(
					'block_start' => '<div class="action_messages">',
					'block_end'   => '</div>',
				) );
			// --------------------------------- END OF MESSAGES ---------------------------------
		}

		// Start of wrapper for front page area, in order to have the $Messages outside this block
		echo '<div class="front_main_content">';
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
					'title_before'      => '<h2>',
					'title_after'       => '</h2>',
					'title_none'        => '',
					'glue'              => ' - ',
					'title_single_disp' => false,
					'title_page_disp'   => false,
					'format'            => 'htmlbody',
					'register_text'     => '',
					'login_text'        => '',
					'lostpassword_text' => '',
					'account_activation' => '',
					'msgform_text'      => '',
					'user_text'         => '',
					'users_text'        => '',
				) );
			// ----------------------------- END OF REQUEST TITLE ----------------------------
		?>

		<?php
		// Go Grab the featured post:
		if( $Item = & get_featured_Item() )
		{ // We have a featured/intro post to display:
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			echo '<div class="panel panel-default"><div class="panel-body">';
			skin_include( '_item_block.inc.php', array(
					'feature_block' => true,
					'content_mode'  => 'auto',		// 'auto' will auto select depending on $disp-detail
					'intro_mode'    => 'normal',	// Intro posts will be displayed in normal mode
					'Item'          => $Item,
				) );
			echo '</div></div>';
			// ----------------------------END ITEM BLOCK  ----------------------------
		}
		?>

		<?php
			// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
			skin_include( '$disp$', array(
					'author_link_text' => 'auto',
					// Profile tabs to switch between user edit forms
					'profile_tabs' => array(
						'block_start'         => '<nav><ul class="nav nav-tabs profile_tabs">',
						'item_start'          => '<li>',
						'item_end'            => '</li>',
						'item_selected_start' => '<li class="active">',
						'item_selected_end'   => '</li>',
						'block_end'           => '</ul></nav>',
					),
					// Pagination
					'pagination' => array(
						'block_start'           => '<div class="center"><ul class="pagination">',
						'block_end'             => '</ul></div>',
						'page_current_template' => '<span>$page_num$</span>',
						'page_item_before'      => '<li>',
						'page_item_after'       => '</li>',
						'page_item_current_before' => '<li class="active">',
						'page_item_current_after'  => '</li>',
						'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
						'next_text'             => '<i class="fa fa-angle-double-right"></i>',
					),
					// Form params for the forms below: login, register, lostpassword, activateinfo and msgform
					'skin_form_before'      => '<div class="panel panel-default skin-form">'
																				.'<div class="panel-heading">'
																					.'<h3 class="panel-title">$form_title$</h3>'
																				.'</div>'
																				.'<div class="panel-body">',
					'skin_form_after'       => '</div></div>',
					// Login
					'display_form_messages' => true,
					'form_title_login'      => T_('Log in to your account').'$form_links$',
					'form_title_lostpass'   => get_request_title().'$form_links$',
					'lostpass_page_class'   => 'evo_panel__lostpass',
					'login_form_inskin'     => false,
					'login_page_class'      => 'evo_panel__login',
					'login_page_before'     => '<div class="$form_class$">',
					'login_page_after'      => '</div>',
					'display_reg_link'      => true,
					'abort_link_position'   => 'form_title',
					'abort_link_text'       => '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
					// Register
					'register_page_before'      => '<div class="evo_panel__register">',
					'register_page_after'       => '</div>',
					'register_form_title'       => T_('Register'),
					'register_links_attrs'      => '',
					'register_use_placeholders' => true,
					'register_field_width'      => 252,
					'register_disabled_page_before' => '<div class="evo_panel__register register-disabled">',
					'register_disabled_page_after'  => '</div>',
					// Activate form
					'activate_form_title'  => T_('Account activation'),
					'activate_page_before' => '<div class="evo_panel__activation">',
					'activate_page_after'  => '</div>',
					// Search
					'search_input_before'  => '<div class="input-group">',
					'search_input_after'   => '',
					'search_submit_before' => '<span class="input-group-btn">',
					'search_submit_after'  => '</span></div>',
					// Front page
					'front_block_first_title_start' => '<h1>',
					'front_block_first_title_end'   => '</h1>',
					'front_block_title_start'       => '<h2>',
					'front_block_title_end'         => '</h2>',
					// Form "Sending a message"
					'msgform_form_title' => T_('Sending a message'),
				) );
			// Note: you can customize any of the sub templates included here by
			// copying the matching php file into your skin directory.
			// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>

		<?php
			// End of wrapper for front page area, in order to have the $Messages outside this block
			echo '</div>';// END OF <div class="front_main_content">
		?>

		</main>

	</div><!-- .col -->

	<!-- "Slide down" button -->
	<div class="slide_button_wrap"><a href="#" id="slide_button"><i class="fa fa-angle-down" ></i></a></div>

</div><!-- .row -->

</div><!-- .container -->

</div><!-- .evo_pictured_layout -->


<!-- =================================== START OF SECONDARY AREA =================================== -->
<section class="secondary_area" id="slide_destination"><!-- white background, ID is used to slide here from "slide_button" -->
<div class="container">

	<div class="row">

		<div class="col-md-12">
			<div class="evo_container evo_container__front_page_secondary">
			<?php
				// ------------------------- "Front Page Secondary Area" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Front Page Secondary Area'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start'       => '<div class="widget $wi_class$">',
						'block_end'         => '</div>',
						'block_title_start' => '<h2 class="page-header">',
						'block_title_end'   => '</h2>',
					) );
				// ----------------------------- END OF "Front Page Secondary Area" CONTAINER -----------------------------
			?>
			</div>
		</div><!-- .col -->

		<footer class="col-md-12 center">

			<div class="evo_container evo_container__footer">
			<?php
				// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Footer'), array(
						// The following params will be used as defaults for widgets included in this container:
					) );
				// ----------------------------- END OF "Footer" CONTAINER -----------------------------
			?>
			</div>

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
						'after'  => ' ',
						'text'   => T_('Help'),
					) );
			?>

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

			<?php
				// Please help us promote b2evolution and leave this logo on your blog:
				powered_by( array(
						'block_start' => '<div class="powered_by">',
						'block_end'   => '</div>',
						// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
						'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
						'img_width'   => 120,
						'img_height'  => 32,
					) );
			?>

		</footer><!-- .col -->

	</div><!-- .row -->

</div><!-- .container -->

</section><!-- .secondary_area -->

<script>
// Scroll Down to content
// ======================================================================== /	
$slide_down = $( "#slide_button" );
// Smooth scroll to top
$slide_down.on( "click", function(event) {
	event.preventDefault();
	$( "body, html, #skin_wrapper" ).animate({
		scrollTop: $("#slide_destination").offset().top +26
	}, 1000);
});
</script>

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>