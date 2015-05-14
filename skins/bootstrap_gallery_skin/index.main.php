<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskins
 * @subpackage photoalbums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
if( version_compare( $app_version, '3.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 3.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}
global $Skin;
// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );
require_js( 'functions.js', 'blog' );	// for opening popup window (comments)
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
		'arcdir_text'     => T_('Index'),
		'catdir_text'     => T_('Galleries'),
		'category_text'   => T_('Gallery').': ',
		'categories_text' => T_('Galleries').': ',
	) );
// -------------------------------- END OF HEADER --------------------------------
// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

<div class="container">
	<header class="row">
		<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
			<div class="PageTop">
	<?php
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="widget $wi_class$">',
				'block_end' => '</div>',
				'block_display_title' => false,
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				'item_start' => '<li>',
				'item_end' => '</li>',
			) );
	?>
			</div><!-- ../PageTop -->
		</div>
		<div class="coll-xs-12 col-sm-12 col-md-8 col-md-pull-4">
			<div class="pageHeader">
	<?php
		// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'       => '<div class="widget $wi_class$">',
				'block_end'         => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end'   => '</h1>',
			) );
		// ----------------------------- END OF "Header" CONTAINER -----------------------------
	?>

	<?php
		skin_widget( array(
			// CODE for the widget:
			'widget' => 'member_count',
			// Optional display params
			'before' => '(',
			'after'  => ')',
		) );
	?>
			</div><!-- ../PageHeader -->
		</div>
	</header>

	<nav class="row">
		<div class="col-md-12">
			<ul class="nav nav-tabs">
		<?php
			// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
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
			</ul><!-- ../nav -->
		</div><!-- ../col-md-12 -->
	</nav><!-- ../row -->

	<?php
		if( $disp == 'single' )
		{ // ------------------- NAVIGATION BAR FOR ALBUM(POST) -------------------
			if( $single_Item = & mainlist_get_item() )
			{ // Get Item here, because it can be not defined yet, e.g. in Preview mode
	?>
	<div class="nav_album">

		<a href="<?php $Blog->disp( 'url', 'raw' ) ?>" title="<?php echo format_to_output( T_('All Albums'), 'htmlattr' ); ?>" class="all_albums btn btn-primary">All Albums</a>

		<span class="nav_album_title">
	<?php
		$single_Item->title( array(
				'link_type' => 'permalink',
				'before'    => '',
				'after'     => '',
			) );
	?>
				<div class="nav_album_number hidden-xs">
					<?php printf( T_('%s photos'), $single_Item->get_number_of_images() ); ?>
				</div>
	<?php 	
		if( $Skin->enabled_status_banner( $Item->status ) )
		{ // Status banner
			$single_Item->status( array(
					'before' => '<div class="post_status">',
					'after'  => '</div>',
					'format' => 'styled'
				) );
		}
		$single_Item->edit_link( array( // Link to backoffice for editing
				'before'    => '',
				'after'     => '',
				'text'      => get_icon( 'edit' ),
				'title'     => T_('Edit title/description...'),
			) );
	?>
		</span><!-- ../nav_album_title -->
	
	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'template' => '$prev$$next$',
				'block_start' => '<ul class="pager hidden-xs">',
				'next_class' => 'next',
				'next_start'  => '<li class="next">',
				'next_text' => 'Next',
				'next_no_item' => '',
				'next_end'    => '</li>',
				'prev_class' => 'previous',
				'prev_start'  => '<li class="previous">',
				'prev_text' => 'Previous',
				'prev_no_item' => '',
				'prev_end'    => '',
				'block_end'   => '</ul>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>
	
		<div class="clear"></div>
	</div><!-- ../nav_album -->

	<?php
		}
	} // ------------------- END OF NAVIGATION BAR FOR ALBUM(POST) ------------------- ?>

	<div class="row">	
		<main>	
		<div class="bPosts<?php echo in_array( $disp, array( 'catdir', 'posts', 'single', 'page', 'mediaidx' ) ) ? ' col-lg-12' : '' ?>">

<!-- =================================== START OF MAIN AREA =================================== -->

	<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
	?>
		
	<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( array(
			'title_before'=> '<h2>',
			'title_after' => '</h2>',
			'title_none'  => '',
			'glue'        => ' - ',
			'title_single_disp' => false,
			'format'      => 'htmlbody',
			'arcdir_text' => T_('Index'),
			'catdir_text' => '',
			'category_text' => T_('Gallery').': ',
			'categories_text' => T_('Galleries').': ',
		) );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>
	<div class="clear"></div>
	<?php
	// Go Grab the featured post:
	if( $Item = & get_featured_Item() )
	{	// We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block'=> true,
				'content_mode' => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
				'intro_mode'   => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'   => 'featured_post',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
	?>

	<?php
	if( $disp == 'single' || $disp == 'page' )
	{ // ------------------------------------ START OF A POST ----------------------------------------
		// Display message if no post:
		display_if_empty();
		if( isset( $single_Item ) )
		{ // Use Item that already is defined above
			$Item = & $single_Item;
		}
		else
		{ // Get next Item object
			$Item = & mainlist_get_item();
		}
		if( $Item )
		{
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'content_mode'  => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}
	} // ---------------------------------- END OF A POST ------------------------------------
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_single' => '', // We already handled this case above
				'disp_page'   => '', // We already handled this case above
				'mediaidx_thumb_size'  => $Skin->get_setting( 'mediaidx_thumb_size' ),
				'author_link_text'     => 'preferredname',
				'login_page_before'    => '<div class="login_block"><div class="bDetails">',
				'login_page_after'     => '</div></div>',
				'register_page_before' => '<div class="login_block"><div class="bDetails">',
				'register_page_after'  => '</div></div>',
				'display_abort_link'   => ( $Blog->get_setting( 'allow_access' ) == 'public' ), // Display link to abort login only when it is really possible
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>
		</div><!-- ../bPosts col-lg-12 -->
		</main>
	</div><!-- ../row -->

	<?php
	if( $disp != 'catdir' )
	{ // Don't display the pages on disp=catdir because we don't have a limit by page there
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="nav_pages">',
				'block_end' => '</div>',
				'prev_text' => '&lt;&lt;',
				'next_text' => '&gt;&gt;',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	}
	?>
</div><!-- ../container MAIN -->

<!-- =================================== START OF FOOTER =================================== -->
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
					'before'      => '',
					'after'       => ' &bull; ',
				) );
		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
		?>

		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' &bull; ',
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