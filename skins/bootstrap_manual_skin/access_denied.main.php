<?php
/**
 * This file is the template that displays "access denied" for non-members.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Skin, $Settings, $Collection, $Blog;

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------

// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div class="container">

<header id="header" class="row<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">

	<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
		<?php
		if( $Skin->show_container_when_access_denied( 'page_top' ) )
		{ // Display 'Page Top' widget container
		?>
		<div class="evo_container evo_container__page_top">
		<?php
			// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Page Top'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'         => '<div class="evo_widget $wi_class$">',
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
		<?php } ?>
	</div><!-- .col -->

	<div class="coll-xs-12 col-sm-12 col-md-8 col-md-pull-4">
		<?php
		if( $Skin->show_container_when_access_denied( 'header' ) )
		{ // Display 'Header' widget container
		?>
		<div class="evo_container evo_container__header">
		<?php
			// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Header'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="evo_widget $wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		?>
		</div>
		<?php } ?>
	</div><!-- .col -->

</header><!-- .row -->

<?php
if( $Skin->show_container_when_access_denied( 'menu' ) )
{ // Display this widget container only when it is not disabled
?>
<nav class="row">

	<div class="col-md-12">
		<ul class="nav nav-tabs evo_container evo_container__menu">
		<?php
			// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			// Note: this container is designed to be a single <ul> list
			skin_container( NT_('Menu'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'         => '',
					'block_end'           => '',
					'block_display_title' => false,
					'list_start'          => '',
					'list_end'            => '',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
					'item_selected_start' => '<li class="active">',
					'item_selected_end'   => '</li>',
					'item_title_before'   => '',
					'item_title_after'    => '',
				) );
			// ----------------------------- END OF "Menu" CONTAINER -----------------------------
		?>
		</ul>
	</div><!-- .col -->

</nav><!-- .row -->
<?php } ?>

<div class="row">

	<div class="<?php echo $Skin->is_left_navigation_visible() ? 'col-xs-12 col-md-9 pull-right' : 'col-md-12' ?>">

		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- =================================== START OF MAIN AREA =================================== -->
		<?php
			if( ! in_array( $disp, array( 'login', 'lostpassword', 'register', 'activateinfo' ) ) )
			{ // Don't display the messages here because they are displayed inside wrapper to have the same width as form
				// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
				messages( array(
						'block_start' => '<div class="action_messages">',
						'block_end'   => '</div>',
					) );
				// --------------------------------- END OF MESSAGES ---------------------------------
			}	

			if( ! empty( $cat ) )
			{ // Display breadcrumbs if some category is selected
				skin_widget( array(
						// CODE for the widget:
						'widget' => 'breadcrumb_path',
						// Optional display params
						'block_start'      => '<nav><ol class="breadcrumb">',
						'block_end'        => '</ol></nav>',
						'separator'        => '',
						'item_mask'        => '<li><a href="$url$">$title$</a></li>',
						'item_active_mask' => '<li class="active">$title$</li>',
					) );
			}
		?>

		<?php
			// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
			request_title( array(
					'title_before'      => '<h1 class="page_title">',
					'title_after'       => '</h1>',
					'title_single_disp' => false,
					'title_page_disp'   => false,
					'format'            => 'htmlbody',
					'category_text'     => '',
					'categories_text'   => '',
					'catdir_text'       => '',
					'front_text'        => '',
					'posts_text'        => '',
					'register_text'     => '',
					'login_text'        => '',
					'lostpassword_text' => '',
					'account_activation' => '',
					'msgform_text'      => '',
					'user_text'         => '',
					'users_text'        => '',
					'display_edit_links'=> false,
				) );
			// ----------------------------- END OF REQUEST TITLE ----------------------------
?>


<?php
// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
skin_include( '_access_denied.disp.php' );
// Note: you can customize any of the sub templates included here by
// copying the matching php file into your skin directory.
// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
?>


		</main>

	</div><!-- .col -->

	<?php
	if( $Skin->is_left_navigation_visible() )
	{ // Display a left column with navigation only for several pages
	?>
		<!-- =================================== START OF SIDEBAR =================================== -->
		<aside class="col-xs-12 col-md-3 pull-left">

			<div id="evo_container__sidebar">

				<?php
				if( $Skin->show_container_when_access_denied( 'sidebar' ) )
				{ // Display 'Sidebar' widget container
				?>
				<div class="evo_container evo_container__sidebar">
				<?php
					// <div data-spy="affix" data-offset-top="165" class="affix_block">
					// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					skin_container( NT_('Sidebar'), array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							// This will enclose each widget in a block:
							'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
							'block_end'   => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
							'block_title_end'   => '</h4></div>',
							// This will enclose the body of each widget:
							'block_body_start' => '<div class="panel-body">',
							'block_body_end'   => '</div>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="small text-muted">',
							'notes_end'   => '</div>',
							// Widget 'Search form':
							'search_class'         => 'compact_search_form',
							'search_input_before'  => '<div class="input-group">',
							'search_input_after'   => '',
							'search_submit_before' => '<span class="input-group-btn">',
							'search_submit_after'  => '</span></div>',
							// Widget 'Content Hierarchy':
							'item_before_opened'   => get_icon( 'collapse' ),
							'item_before_closed'   => get_icon( 'expand' ),
							'item_before_post'     => get_icon( 'file_message' ),
							'expand_all'           => false,
							'sorted'               => true
						) );
					// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
				?>
				</div>
				<?php } ?>

				<?php
				if( $Skin->show_container_when_access_denied( 'sidebar2' ) )
				{ // Display 'Sidebar 2' widget container
				?>
				<div class="evo_container evo_container__sidebar2">
				<?php
					// <div data-spy="affix" data-offset-top="165" class="affix_block">
					// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					skin_container( NT_('Sidebar 2'), array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							// This will enclose each widget in a block:
							'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
							'block_end'   => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
							'block_title_end'   => '</h4></div>',
							// This will enclose the body of each widget:
							'block_body_start' => '<div class="panel-body">',
							'block_body_end'   => '</div>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="small text-muted">',
							'notes_end'   => '</div>',
							// Widget 'Search form':
							'search_class'         => 'compact_search_form',
							'search_input_before'  => '<div class="input-group">',
							'search_input_after'   => '',
							'search_submit_before' => '<span class="input-group-btn">',
							'search_submit_after'  => '</span></div>',
							// Widget 'Content Hierarchy':
							'item_before_opened'   => get_icon( 'collapse' ),
							'item_before_closed'   => get_icon( 'expand' ),
							'item_before_post'     => get_icon( 'file_message' ),
							'expand_all'           => false,
							'sorted'               => true
						) );
					// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
				?>
				</div>
				<?php } ?>

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

			</div><!-- DO WE NEED THIS DIV? -->

		</aside><!-- .col -->
	<?php } ?>

</div><!-- .row -->


<footer class="row">

	<!-- =================================== START OF FOOTER =================================== -->
	<div class="col-md-12 center">

		<?php
		if( $Skin->show_container_when_access_denied( 'footer' ) )
		{ // Display 'Footer' widget container
		?>
		<div class="evo_container evo_container__footer">
		<?php
			// Display container and contents:
			skin_container( NT_('Footer'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="evo_widget $wi_class$">',
					'block_end'         => '</div>',
				) );
			// Note: Double quotes have been used around "Footer" only for test purposes.
		?>
		</div>
		<?php } ?>

		<p>
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );
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
						'before'      => ' ',
						'after'       => ' ',
						'text'        => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start'  => '&bull;',
						'list_end'    => ' ',
						'separator'   => '&bull;',
						'item_start'  => ' ',
						'item_end'    => ' ',
					) );
			?>
		</p>

	</div><!-- .col -->
	
</footer><!-- .row -->


</div><!-- .container -->

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>