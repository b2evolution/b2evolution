<?php
/**
 * This file is the template that displays "login" page for non logged-in users.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage bootstrap_main
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Collection, $Blog;

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
skin_include( '_body_header.inc.access.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

?>

<div class="evo_pictured_layout restricted_access_disps">

<div class="container main_page_wrapper">

<header class="row">

	<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
		<?php
		if( $Skin->show_container_when_access_denied( 'page_top' ) )
		{ // Display 'Page Top' widget container
			// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			widget_container( 'page_top', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => false, // If no widget, don't display container at all
					'container_start'     => '<div class="evo_container $wico_class$">',
					'container_end'       => '</div>',
					'block_start'         => '<div class="evo_widget $wi_class$">',
					'block_end'           => '</div>',
					'block_display_title' => false,
					'list_start'          => '<ul>',
					'list_end'            => '</ul>',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
					// Widget 'Search form':
					'search_input_before'  => '<div class="input-group">',
					'search_input_after'   => '',
					'search_submit_before' => '<span class="input-group-btn">',
					'search_submit_after'  => '</span></div>',  
				) );
			// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		} ?>
	</div><!-- .col -->

</header><!-- .row -->


<div class="row">
	<div class="col-md-12">
		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- ================================= START OF MAIN AREA ================================== -->

		<?php
			// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
			skin_include( '$disp$', array(
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
				) );
			// Note: you can customize any of the sub templates included here by
			// copying the matching php file into your skin directory.
			// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>

		</main>

	</div><!-- .col -->

</div><!-- .row -->

</div><!-- .container -->

</div><!-- .evo_pictured_layout -->


<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
skin_include( '_body_footer.inc.access.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>