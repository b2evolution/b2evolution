
<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Form = new Form( $admin_url, 'item_new_version_checkchanges', 'post' );

$Form->begin_form();

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $edited_Item->get_blog_ID() );
	$Form->hidden( 'p', $edited_Item->ID );

	$Form->select_input_options( 'post_locale', $edited_Item->get_locale_options( 'all' ), T_('Language'), '', array( 'style' => 'width:auto' ) );

	$Form->info( T_('Collection'), '<span id="evo_item_new_version_coll"></span>' );
	$Form->hidden( 'post_coll_ID', '' );

	$Form->checkbox( 'post_same_images', 1, T_('Same images'), T_('Link all attachments of current Item to new version.') );

	$ItemTypeCache = & get_ItemTypeCache();
	$Form->select_input_object( 'item_typ_ID', $edited_Item->get( 'ityp_ID' ), $ItemTypeCache, T_('Item Type') );

	if( $edited_Item->get_type_setting( 'use_parent' ) != 'never' )
	{	// If parent is allowed for the Item Type:
		$Form->checkbox( 'post_create_child', 1, T_('Create as child'), T_('Version will be a child and current Item will be parent.') );
	}

	$Form->buttons( array( array( 'submit', 'actionArray[new_version]', T_('Add version'), 'SaveButton' ) ) );

$Form->end_form();
?>
<script>
function evo_set_new_version_collection()
{
	var selected_locale = jQuery( '#item_new_version_checkchanges #post_locale' ).find( 'option:selected' );
	jQuery( '#evo_item_new_version_coll' ).html( selected_locale.data( 'coll-name' ) );
	jQuery( '#item_new_version_checkchanges input[name=post_coll_ID]' ).val( selected_locale.data( 'coll-id' ) );
}
evo_set_new_version_collection();
jQuery( '#item_new_version_checkchanges #post_locale' ).change( function()
{
	evo_set_new_version_collection();
} );
</script>