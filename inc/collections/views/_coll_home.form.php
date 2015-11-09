<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'home' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Front page').get_manual_link('collection-front-page-settings') );

	$front_disp_options = array(
			array( 'front',    T_('Special Front page') ),
			array( 'posts',    T_('Recent Posts') ),
			array( 'comments', T_('Latest Comments') ),
			array( 'arcdir',   T_('Archive Directory') ),
			array( 'catdir',   T_('Category Directory') ),
			array( 'tags',     T_('Tags') ),
			array( 'help',     T_('Help') ),
			array( 'mediaidx', T_('Photo Index') ),
			array( 'msgform',  T_('Sending a message') ),
			array( 'threads',  T_('Messages') ),
			array( 'contacts', T_('Contacts') ),
			array( 'postidx',  T_('Post Index') ),
			array( 'search',   T_('Search') ),
			array( 'sitemap',  T_('Site Map') ),
			array( 'users',    T_('Users') ),
			array( 'terms',    T_('Terms & Conditions') ),
			array( 'page',     T_('A specific page') ),
		);
	foreach( $front_disp_options as $i => $option )
	{ // Set a note for each disp
		$front_disp_options[$i][] = '(disp='.$option[0].')';
	}

	$Form->radio( 'front_disp', $edited_Blog->get_setting('front_disp'), $front_disp_options, T_('What do you want to display on the front page of this collection'), true );

	$fieldstart = $Form->fieldstart;
	if( $edited_Blog->get_setting('front_disp') != 'page' )
	{ // Hide input 'front_post_ID' if Front page is not a specific page
		$Form->fieldstart = str_replace( '>', ' style="display:none">', $Form->fieldstart );
	}
	$Form->text_input( 'front_post_ID', $edited_Blog->get_setting('front_post_ID'), 5, T_('Specific post ID'), '', array( 'required' => true ) );
	$Form->fieldstart = $fieldstart;

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>
<script type="text/javascript">
jQuery( 'input[name=front_disp]' ).click( function()
{
	console.log( jQuery( this ).val() );
	if( jQuery( this ).val() == 'page' )
	{
		jQuery( '[id$=front_post_ID]' ).show();
	}
	else
	{
		jQuery( '[id$=front_post_ID]' ).hide();
	}
} );
</script>