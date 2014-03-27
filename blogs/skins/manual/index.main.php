<?php
/**
 * This is the main/default page template for the "manual" skin.
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
 * @subpackage manual
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '3.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 3.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


/**
 * @var string Name of cookie for skin width
 */
$cookie_skin_width_name = 'skin_width';

if( isset( $_COOKIE[ $cookie_skin_width_name ] ) )
{ // Get skin width from $_COOKIE through param function
	$cookie_skin_width_value = param_cookie( $cookie_skin_width_name, '/^\d+(px|%)$/i', NULL );
	if( empty( $cookie_skin_width_value ) )
	{ // Force illegal value of width to default
		$cookie_skin_width_value = '960px';
	}
}

if( $disp == 'posts' && ! isset( $tag ) && isset( $cat ) )
{	// Display a list of categories instead of posts
	$cat_array = array( $cat ); // To mark selected category
	$disp = 'catdir';
}

/* yura> The following JS is used for home page and for category page where we have the intro Items.
For normal we need to check if current Item can be rated,
but in the disp == 'posts' or 'catdir' we don't have the object Item in the begining of this file,
we will have that only after calling of function get_featured_Item();

I understand that we should to initialize the js-functions
of star rating only if we really need in that but
I don't know how it possible when manual skin has a different structure. */
if( $disp == 'posts' || $disp == 'catdir' )
{	// Init the rating js functions, Used for intro posts
	init_ratings_js( 'blog', true );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

$catdir_text = '';
if( !empty( $cat ) )
{
	$ChapterCache = & get_ChapterCache();
	if( $Chapter = & $ChapterCache->get_by_ID( $cat, false ) )
	{
		$catdir_text = $Chapter->get( 'name' );
	}
}
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
		'catdir_text' => $catdir_text,
	) );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------

// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------


?>


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

<div class="pageHeader<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">
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

<div class="top_menu_bg"></div>

<div id="layout">
	<div id="wrapper"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="width:'.$cookie_skin_width_value.'"' : ''; ?>>

<div class="top_menu">
	<ul>
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
			) );
		// ----------------------------- END OF "Menu" CONTAINER -----------------------------
	?>
	</ul>
	&nbsp;
</div>


<?php
$main_area_class = '';
if( in_array( $disp, array( 'posts', 'single', 'catdir', 'search', 'edit', 'edit_comment', '404' ) ) )
{	// Display sidebar only on these pages
?>
<!-- =================================== START OF SIDEBAR =================================== -->
<div class="bSideBar">

	<?php
		// ------------------------- "Menu Top" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		skin_container( NT_('Menu Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_title_start'   => '',
				'block_title_end'     => '',
				'list_start'          => '',
				'list_end'            => '',
				'item_start'          => '',
				'item_end'            => '',
			) );
		// ----------------------------- END OF "Menu Top" CONTAINER -----------------------------

		// ------------------------- CATEGORIES -------------------------
		$Skin->display_chapters();
		// ------------------------- END OF CATEGORIES ------------------
	?>

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
<?php
}
else
{	// If the left sidebar is hidden we should make the width of main area to 100%
	$main_area_class = ' full_width';
}
?>

<!-- =================================== START OF MAIN AREA =================================== -->
<div class="bPosts<?php echo $main_area_class; ?>">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------

		if( !empty( $cat ) )
		{	// Display breadcrumbs if some category is selected
			$Skin->display_breadcrumbs( $cat );
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
				'edit_text_create'  => T_('Post a new topic'),
				'edit_text_update'  => T_('Edit post'),
				'category_text'     => '',
				'categories_text'   => '',
				'catdir_text'       => ''
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
	if( empty( $cat ) && $disp == 'posts' )
	{	// Home page, display full categories list

		// Go Grab the featured post:
		$intro_Item = & get_featured_Item(); // $intro_Item is used below for comments form
		$Item = $intro_Item;
		if( !empty( $Item ) )
		{	// We have a featured/intro post to display:
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'feature_block'     => true,
					'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
					'intro_mode'        => 'normal',	// Intro posts will be displayed in normal mode
					'item_class'        => 'featured_post',
					'image_size'        => 'fit-640x480',
					'disp_comment_form' => false,
					'item_link_type'    => 'none',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}

		echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
		$Skin->display_chapters( array(
				'display_blog_title' => false,
				'display_children'   => true,
				'class_selected'     => ''
			) );

		if( !empty( $intro_Item ) )
		{
			global $c, $ReqURI;
			$c = 1; // Display comments

			// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
			skin_include( '_item_feedback.inc.php', array(
					'before_section_title' => '<h2 class="comments_list_title">',
					'after_section_title'  => '</h2>',
					'form_title_start'     => '<h3 class="comments_form_title">',
					'form_title_end'       => '</h3>',
					'Item'                 => $intro_Item,
					'form_title_text'      => T_('Comment form'),
					'comments_title_text'  => T_('Comments on this chapter'),
					'form_comment_redirect_to' => $ReqURI,
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		}

	}
	else
	{
		// Display message if no post:
		display_if_empty();

		if( isset( $MainList ) && !empty( $MainList ) && ( $disp == 'posts' || $disp == 'single' ) )
		{
			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'prev_text' => '&lt;&lt;',
					'next_text' => '&gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

			// --------------------------------- START OF POSTS -------------------------------------
			if( $disp == 'posts' )
			{	// Display lists of the posts
				echo '<h4 style="margin-top:20px">'.T_('Pages in this chapter:').'</h4>';
				echo '<ul class="posts_list">';
				while( $Item = & mainlist_get_item() )
				{
					skin_include( '_item_list.inc.php' );
				}
				echo '</ul>';
			}
			else
			{	// disp == single
				if( $Item = & mainlist_get_item() )
				{	// For each blog post, do everything below up to the closing curly brace "}"
					// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
					skin_include( '_item_block.inc.php', array(
							'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
							'image_size'   => 'fit-640x480',
						) );
					// ----------------------------END ITEM BLOCK  ----------------------------
				}
			}
			// ---------------------------------- END OF POSTS ------------------------------------

			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'prev_text' => '&lt;&lt;',
					'next_text' => '&gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		}
	}
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>
</div>

	</div><?php /** END OF <div id="wrapper"> **/?>
</div><?php /** END OF <div id="layout"> **/?>

<div class="clear"></div>

<!-- =================================== START OF FOOTER =================================== -->
<div id="pageFooter">
	<?php
		// Display container and contents:
		skin_container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
	<p class="baseline">
		<?php
			// Display footer text (text can be edited in Blog Settings):
			$Blog->footer_text( array(
					'before'      => '',
					'after'       => ' &bull; ',
				) );

		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ptyp_ID though..?!
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
					'after'       => ' &bull; ',
					'text'        => T_('Help'),
				) );
		?>

		<?php display_param_link( $skin_links ) ?> by <?php display_param_link( $francois_links ) ?>

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
</div>


<?php
// ------------------------- WIDTH SWITCHER --------------------------
$width_switchers = array(
		'960px' => 'width_decrease',
		'100%'  => 'width_increase',
	);
if( !empty( $cookie_skin_width_value ) )
{ // Fix this cookie value because in the cookie we cannot store the width in percent values (See js function switch_width() to understand why)
	$cookie_skin_width_value_fixed = $cookie_skin_width_value != '960px' ? '100%' : $cookie_skin_width_value;
}

$switcher_layout_top = is_logged_in() ? 26 : 3;
$switcher_layout_top += $Settings->get( 'site_skins_enabled' ) ? 153 : 3;

$switcher_top = is_logged_in() ? 26 : 0;
$switcher_top += $Settings->get( 'site_skins_enabled' ) ? 54 : 0;

$switcher_class = !$Settings->get( 'site_skins_enabled' ) ? ' fixed' : '';
?>
<div id="width_switcher_layout"<?php echo $switcher_layout_top ? ' style="top:'.$switcher_layout_top.'px"' : ''; ?>>
	<div id="width_switcher"<?php echo $switcher_top ? ' style="top:'.$switcher_top.'px"' : ''; ?> class="roundbutton_group<?php echo $switcher_class; ?>">
<?php
$ws = 0;
$ws_count = count( $width_switchers );
foreach( $width_switchers as $ws_size => $ws_icon )
{
	$ws_class = 'roundbutton';
	if( ( !empty( $cookie_skin_width_value ) && $cookie_skin_width_value_fixed == $ws_size ) ||
	    ( empty( $cookie_skin_width_value ) && $ws == 0 ) )
	{	// Mark this switcher as selected
		$ws_class .= ' roundbutton_selected';
	}
	echo '<a href="#" onclick="switch_width( this, \''.$ws_size.'\', \''.$cookie_skin_width_name.'\', \''.$cookie_path.'\' ); return false;" class="'.$ws_class.'">';
	echo get_icon( $ws_icon );
	echo '</a>';
	$ws++;
}
?>
	</div>
</div>
<?php
if( $Settings->get( 'site_skins_enabled' ) )
{ // Change position of width switcher only when Site Header is displayed
?>
<script type="text/javascript">
var has_touch_event;
window.addEventListener( 'touchstart', function set_has_touch_event ()
{
	has_touch_event = true;
	// Remove event listener once fired, otherwise it'll kill scrolling
	window.removeEventListener( 'touchstart', set_has_touch_event );
}, false );

var $switcher = jQuery( '#width_switcher' );
var switcher_size = $switcher.size();
var switcher_top = <?php echo $switcher_top ?>;
jQuery( window ).scroll( function ()
{
	if( has_touch_event )
	{ // Don't fix the objects on touch devices
		return;
	}

	if( switcher_size )
	{ // Width switcher exists
		if( !$switcher.hasClass( 'fixed' ) && jQuery( window ).scrollTop() > $switcher.offset().top - switcher_top )
		{ // Make switcher as fixed if we scroll down
			$switcher.addClass( 'fixed' );
		}
		else if( $switcher.hasClass( 'fixed' ) && jQuery( window ).scrollTop() < jQuery( '#width_switcher_layout' ).offset().top - switcher_top )
		{ // Remove 'fixed' class from switcher if we scroll to the top of page
			$switcher.removeClass( 'fixed' );
		}
	}
} );
</script>
<?php
}
// ------------------------- END OF WIDTH SWITCHER --------------------------


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