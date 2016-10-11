<?php
/**
 * This file display the tag form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var ItemTag
 */
global $edited_ItemTag;

global $action, $admin_url, $display_merge_tags_form;

if( ! empty( $edited_ItemTag->merge_tag_ID ) )
{ // Display a for to confirm merge the tag to other one
	$Form = new Form( NULL, 'itemtagmerge_checkchanges', 'post', 'compact' );

	$Form->begin_form( 'fform', T_('Merge tags?'), array( 'formstart_class' => 'panel-danger' ) );
	$Form->hidden( 'tag_ID', $edited_ItemTag->merge_tag_ID );
	$Form->hidden( 'old_tag_ID', $edited_ItemTag->ID );
	$Form->add_crumb( 'tag' );
	$Form->hiddens_by_key( get_memorized( 'action,tag_ID' ) );

	echo '<p>'.$edited_ItemTag->merge_message.'</p>';

	$Form->button( array( 'submit', 'actionArray[merge_confirm]', T_('Confirm'), 'SaveButton btn-danger' ) );
	$Form->button( array( 'submit', 'actionArray[merge_cancel]', T_('Cancel'), 'SaveButton btn-default' ) );

	$Form->end_form();
}

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemtag_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New Tag') : T_('Tag') ).get_manual_link( 'item-tag-form' ) );

	$Form->add_crumb( 'tag' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',tag_ID' : '' ) ) );

	$Form->text_input( 'tag_name', $edited_ItemTag->get( 'name' ), 50, T_('Tag'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );


// Item list with this tag:
if( $edited_ItemTag->ID > 0 )
{
	$SQL = new SQL();
	$SQL->SELECT( 'T_items__item.*, blog_shortname' );
	$SQL->FROM( 'T_items__itemtag' );
	$SQL->FROM_add( 'INNER JOIN T_items__item ON itag_itm_ID = post_ID' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
	$SQL->FROM_add( 'INNER JOIN T_blogs ON cat_blog_ID = blog_ID' );
	$SQL->WHERE( 'itag_tag_ID = '.$DB->quote( $edited_ItemTag->ID ) );

	// Create result set:
	$Results = new Results( $SQL->get(), 'tagitem_', 'A' );

	$Results->title = T_('Posts that have this tag').' ('.$Results->get_total_rows().')';
	$Results->Cache = get_ItemCache();

	$Results->cols[] = array(
			'th'       => T_('Post ID'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'order'    => 'post_ID',
			'td'       => '$post_ID$',
		);

	$Results->cols[] = array(
			'th'    => T_('Collection'),
			'order' => 'blog_shortname',
			'td'    => '$blog_shortname$',
		);

	$Results->cols[] = array(
			'th'    => T_('Post title'),
			'order' => 'post_title',
			'td'    => '<a href="@get_permanent_url()@">$post_title$</a>',
		);

	function tagitem_edit_actions( $Item )
	{
		global $current_User, $edited_ItemTag;

		// Display the edit icon if current user has the rights:
		$r = $Item->get_edit_link( array(
			'before' => '',
			'after'  => ' ',
			'text'   => get_icon( 'edit' ),
			'title'  => '#',
			'class'  => '' ) );

		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
		{ // Display the unlink icon if current user has the rights:
			$r .= action_icon( T_('Unlink this tag from post!'), 'unlink',
				regenerate_url( 'tag_ID,action,tag_filter', 'tag_ID='.$edited_ItemTag->ID.'&amp;item_ID='.$Item->ID.'&amp;action=unlink&amp;'.url_crumb( 'tag' ) ),
				NULL, NULL, NULL,
				array( 'onclick' => 'return confirm(\''.format_to_output( sprintf( TS_('Are you sure you want to remove the tag "%s" from "%s"?'),
						$edited_ItemTag->dget( 'name' ),
						$Item->dget( 'title' ) ).'\');', 'htmlattr' )
					) );
		}

		return $r;
	}
	$Results->cols[] = array(
			'th'       => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td'       => '%tagitem_edit_actions( {Obj} )%',
		);

	$Results->display();
}
?>