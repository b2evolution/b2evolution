<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
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

$Form->begin_fieldset( TB_('Front page').get_manual_link('collection-front-page-settings') );

	$front_disp_options = array(
			array( 'front',    TB_('Special Front page') ),
			array( 'posts',    TB_('Recent Posts') ),
			array( 'comments', TB_('Latest Comments') ),
			array( 'arcdir',   TB_('Archive Directory') ),
			array( 'catdir',   TB_('Category Directory') ),
			array( 'tags',     TB_('Tags') ),
			array( 'help',     TB_('Help') ),
			array( 'mediaidx', TB_('Photo Index') ),
			array( 'msgform',  TB_('Contact') ),
			array( 'threads',  TB_('Messages') ),
			array( 'contacts', TB_('Contacts') ),
			array( 'postidx',  TB_('Post Index') ),
			array( 'search',   TB_('Search') ),
			array( 'sitemap',  TB_('Site Map') ),
			array( 'users',    TB_('Users') ),
			array( 'terms',    TB_('Terms & Conditions') ),
			array( 'flagged',  TB_('Flagged Items') ),
			array( 'mustread', TB_('Must Read Items'), '', ' '.get_pro_label() ),
			array( 'single',   TB_('First post') ),
			array( 'page',     TB_('A specific page') ),
		);
	foreach( $front_disp_options as $i => $option )
	{ // Set a note for each disp
		$front_disp_options[$i][2] = '(disp='.$option[0].')';
	}

	$Form->radio( 'front_disp', $edited_Blog->get_setting('front_disp'), $front_disp_options, TB_('What do you want to display on the front page of this collection'), true );

	$fieldstart = $Form->fieldstart;
	if( $edited_Blog->get_setting('front_disp') != 'page' )
	{ // Hide input 'front_post_ID' if Front page is not a specific page
		$Form->fieldstart = str_replace( '>', ' style="display:none">', $Form->fieldstart );
	}
	$Form->text_input( 'front_post_ID', $edited_Blog->get_setting('front_post_ID'), 5, TB_('Specific post ID'), '', array( 'required' => $edited_Blog->get_setting('front_disp') == 'page' ? 'required' : 'mark_only'  ) );
	$Form->fieldstart = $fieldstart;

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

?>
<script>
jQuery( 'input[name=front_disp]' ).click( function()
{
	if( jQuery( this ).val() == 'page' )
	{
		jQuery( '[id$=front_post_ID]' ).show();
		jQuery( '[id$=front_post_ID]' ).attr( 'required', 'required' );
	}
	else
	{
		jQuery( '[id$=front_post_ID]' ).hide();
		jQuery( '[id$=front_post_ID]' ).removeAttr( 'required' );
	}
} );
</script>