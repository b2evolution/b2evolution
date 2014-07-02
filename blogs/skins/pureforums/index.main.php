<?php
/**
 * This is the main/default page template for the "forums" skin.
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
 * @subpackage pureforums
 *
 * @version $Id: index.main.php 6626 2014-05-07 10:05:49Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '4.0.0-dev' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 4.0.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

global $cat;
$posts_text = T_('Forum');
if( $disp == 'posts' )
{
	if( !empty( $cat ) && ( $cat > 0 ) )
	{ // Set category name when some forum is opened
		$ChapterCache = & get_ChapterCache();
		if( $Chapter = $ChapterCache->get_by_ID( $cat ) )
		{
			$posts_text .= ': '.$Chapter->get( 'name' );
		}
	}
	else
	{ // Set title for ?disp=posts
		$posts_text = T_('Latest topics');
	}
}

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
	'edit_text_create'  => T_('Start a new topic'),
	'edit_text_update'  => T_('Edit post'),
	'catdir_text'       => T_('Forum'),
	'category_text'     => T_('Forum').': ',
	'comments_text'     => T_('Latest Replies'),
	'front_text'        => T_('Forum'),
	'posts_text'        => $posts_text,
	'useritems_text'    => T_('User\'s topics'),
	'usercomments_text' => T_('User\'s replies'),
	'body_class'        => $Skin->get_setting( 'avatar_style' ) == 'round' ? 'round_avatars' : NULL,
) );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

<div class="header<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">
	<?php
		ob_start();
		// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'         => '',
				'block_end'           => '',
				'block_display_title' => false,
				'list_start'          => '',
				'list_end'            => '',
				'item_start'          => '<li>',
				'item_end'            => '</li>',
			) );
		// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		$page_top_skin_container = ob_get_clean();

		if( ! empty( $page_top_skin_container ) )
		{ // Display 'Page Top' widget container only if it contains something
	?>
	<div class="header_top">
		<div class="layout_width">
			<ul><?php echo $page_top_skin_container; ?></ul>
		</div>
	</div>
	<?php } ?>
	<div class="header_bottom">
		<div class="layout_width">
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
			<div class="header_right">
				<ul class="top_menu">
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

			// ------------------------- "Menu Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			// Note: this container is designed to be a single <ul> list
			skin_container( NT_('Menu Top'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start' => '<li><div class="$wi_class$">',
					'block_end' => '</div></li>',
					'block_display_title' => false,
					'list_start'          => '',
					'list_end'            => '',
					'item_start'          => '',
					'item_end'            => '',
				) );
			// ----------------------------- END OF "Menu Top" CONTAINER -----------------------------
		?>
				</ul>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

<div class="top_menu_bg"></div>

<div id="layout" class="layout_width">
	<div id="wrapper">

<!-- =================================== START OF MAIN AREA =================================== -->
<div>

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		if( $disp == 'edit' )
		{	// Add or Edit a post
			$p = param( 'p', 'integer', 0 ); // Edit post from Front-office
		}
		// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
		request_title( array(
				'title_before'      => '<h2 class="page_title">',
				'title_after'       => '</h2>',
				'title_single_disp' => false,
				'title_page_disp'   => false,
				'format'            => 'htmlbody',
				'edit_text_create'  => T_('Post a new topic'),
				'edit_text_update'  => T_('Edit post'),
				'category_text'     => '',
				'categories_text'   => '',
				'catdir_text'       => '',
				'comments_text'     => T_('Latest Replies'),
				'front_text'        => '',
				'posts_text'        => '',
				'useritems_text'    => T_('User\'s topics'),
				'usercomments_text' => T_('User\'s replies'),
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
			'profile_avatar_before' => '<div class="profile_avatar">',
			'profile_avatar_after'  => '</div>',
			'disp_edit_categories'  => false,
			'skin_form_params'      => array(
					'formstart'      => '<table class="forums_table" cellspacing="0" cellpadding="0">',
					'formend'        => '</table>',
					'fieldset_begin' => '<tr><th colspan="3" $fieldset_attribs$>$fieldset_title$</th></tr>',
					'fieldset_end'   => '',
					'fieldstart'     => '<tr $ID$>',
					'fieldend'       => '</tr>',
					'labelstart'     => '<td class="form_label">',
					'labelend'       => '</td>',
					'inputstart'     => '<td class="form_input">',
					'inputend'       => '</td>',
					'infostart'      => '<td class="form_info" colspan="2">',
					'infoend'        => '</td>',
					'buttonsstart'   => '<tr><td colspan="2" class="buttons">',
					'buttonsend'     => '</td></tr>',
					'inline_labelstart' => '<td class="form_label_inline" colspan="2">',
					'inline_labelend'   => '</td>',
					'inline_inputstart' => '',
					'inline_inputend'   => '',
					'customstart'       => '<tr><td colspan="2" class="form_custom_content">',
					'customend'         => '</td></tr>',
				),
			'notify_my_text'              => T_( 'Notify me by email whenever a reply is published on one of <strong>my</strong> topics.' ),
			'notify_moderator_text'       => T_( 'Notify me by email whenever a reply is posted in a forum where I am a moderator.' ),
			'user_itemlist_title'         => T_('Topics created by %s'),
			'user_itemlist_no_results'    => T_('User has not created any topics'),
			'user_commentlist_title'      => T_('Replies posted by %s'),
			'user_commentlist_no_results' => T_('User has not posted any replies'),
			'user_commentlist_col_post'   => T_('Reply on:'),
		) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

</div>

<?php skin_include( '_legend.inc.php' ); ?>

	</div><?php /** END OF <div id="wrapper"> **/?>
</div><?php /** END OF <div id="layout"> **/?>

<!-- =================================== START OF FOOTER =================================== -->
<div id="footer" class="layout_width">
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

			// Display additional credits:
			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start' => '',
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
