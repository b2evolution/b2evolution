<?php
/**
 * This is the template that displays a page of terms & conditions
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $redirect_to, $UserSettings, $current_User;

// Default params:
$params = array_merge( array(
		// Classes for the <article> tag:
		'item_class'          => 'evo_post evo_content_block',
		'item_type_class'     => 'evo_post__ptyp_',
		'item_status_class'   => 'evo_post__',
		// Controlling the content:
		'content_mode'        => 'full', // Use only 'full' on disp=terms
		'image_class'         => 'img-responsive',
		'image_size'          => 'fit-1280x720',
		// Part with accept button:
		'terms_button_before' => '<p class="text-center">',
		'terms_button_after'  => '</p>',
		'terms_info_before'   => '<p class="alert alert-success">',
		'terms_info_after'    => '</p>',
	), $params );


// Display message if no post:
display_if_empty();

if( $Item = & mainlist_get_item() )
{	// If a post exists for page with terms & conditions:

	// ---------------------- ITEM BLOCK START ----------------------

	// Temporarily switch to post locale (useful for multilingual blogs):
	$Item->locale_temp_switch();

	?>
	<div class="evo_content_block">
		<article id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">
		<?php
		// this will create a <section>
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', $params );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		// this will end a </section>
		?>
		</article>
	</div>
	<?php

	// ----------------------- ITEM BLOCK END -----------------------

	// Display a button to accept the terms OR info text if current user already accepted them:
	if( is_logged_in() )
	{	// If user is logged in:

		if( $UserSettings->get( 'terms_accepted', $current_User->ID ) )
		{	// If current user already accepted:
			echo $params['terms_info_before'].T_('You already accepted these terms.').$params['terms_info_after'];
		}
		else
		{	// Otherwise display a button to accept:
			$Form = new Form( get_htsrv_url().'accept_terms.php' );
			$Form->begin_form();
			$Form->hidden( 'redirect_to', $redirect_to );

			echo $params['terms_button_before'];
			$Form->button( array( 'submit', '', T_('I accept these terms'), 'btn-success btn-lg' ) );
			echo $params['terms_button_after'];

			$Form->end_form();
		}
	}

	// Restore previous locale (Blog locale):
	locale_restore_previous();
}
?>