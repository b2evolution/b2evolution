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
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '3.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 3.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

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
</div>

<div class="pageHeader">

	<div class="top_menu floatright">
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
					'item_start'          => ' <span class="menu_link">',
					'item_end'            => '</span> ',
					'item_selected_start' => ' <span class="menu_link">',
					'item_selected_end'   => '</span>',
					'item_title_before'   => '',
					'item_title_after'    => '',
				) );
			// ----------------------------- END OF "Menu" CONTAINER -----------------------------
		?>
	</div>

	<h1 id="pageTitle"><a href="<?php $Blog->disp( 'url', 'raw' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>

</div>
<div class="bPosts">

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
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="nav_right">',
				'block_end' => '</div>',
				'links_format' => '$next$ $prev$',
				'prev_text' => '<span class="pb_icon prev" title="'.T_('Previous').'"></span>',
				'next_text' => '<span class="pb_icon next" title="'.T_('Next').'"></span>',
				'no_prev_text' => '',
				'no_next_text' => get_icon( 'pixel', 'imgtag', array( 'size' => array( 29, 29 ), 'xy' => array( 13, 13 ), 'class' => 'no_nav' ) ),
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>

	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'template' => '$next$$prev$',
				'block_start' => '<div class="nav_right">',
				'next_start'  => '',
				'next_text' => '<span class="pb_icon next" title="'.T_('Next').'"></span>',
				'next_no_item' => get_icon( 'pixel', 'imgtag', array( 'size' => array( 29, 29 ), 'xy' => array( 13, 13 ), 'class' => 'no_nav' ) ),
				'next_end'    => ' ',
				'prev_start'  => '',
				'prev_text' => '<span class="pb_icon prev" title="'.T_('Previous').'"></span>',
				'prev_no_item' => '',
				'prev_end'    => '',
				'block_end'   => '</div>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

	<?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'      => '<h2>',
				'title_after'       => '</h2>',
				'title_none'        => '<h2>&nbsp;</h2>',
				'glue'              => ' - ',
				'title_single_disp' => false,
				'format'            => 'htmlbody',
				'arcdir_text'       => T_('Index'),
				'catdir_text'       => T_('Galleries'),
				'category_text'     => T_('Gallery').': ',
				'categories_text'   => T_('Galleries').': ',
				'user_text'         => '',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<?php
	// Go Grab the featured post:
	if( $Item = & get_featured_Item() )
	{	// We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block' => true,
				'content_mode' => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
				'intro_mode'   => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'   => 'featured_post',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
	?>

	<?php
	if( $disp != 'front' && $disp != 'download' )
	{
		// ------------------------------------ START OF POSTS ----------------------------------------
		// Display message if no post:
		display_if_empty();

		echo '<div id="styled_content_block">'; // Beginning of posts display
		while( $Item = & mainlist_get_item() )
		{	// For each blog post:
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'content_mode'  => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		} // ---------------------------------- END OF POSTS ------------------------------------
		echo '</div>'; // End of posts display
	}
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
				'disp_page'   => '',		// We already handled this case above
				'mediaidx_thumb_size' => $Skin->get_setting( 'mediaidx_thumb_size' ),
				'author_link_text' => 'preferredname',
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>
	<div class="bPost"></div>

</div>


<?php
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------


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