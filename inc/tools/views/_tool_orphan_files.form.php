<?php
/**
 * This file display the form to find and delete orphan files
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( NULL, 'delete_orphan_files', 'post', 'compact' );

$Form->global_icon( T_('Cancel').'!', 'close', regenerate_url( 'action,blog' ) );

$Form->begin_form( 'fform', T_('Find and delete all orphan File objects (with no matching file on disk) - DB only.') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action', 'delete_orphan_files' );
	$Form->add_crumb( 'tools' );

	$Form->checklist( array(
			array( 'delete_files', 1, T_('Delete orphan File objects that have no Links'), 1 ),
			array( 'delete_linked', 1, T_( 'Also delete orphan File objects that have Links (delete Link objects then delete File objects)' ), 1 ),
		), 'delete_files', T_('Cleanup') );

$Form->end_form( array( array( 'submit', 'submit', T_('Delete'), 'ResetButton' ) ) );
?>
<script>
jQuery( 'input[name=delete_files]' ).click( function()
{
	jQuery( 'input[name=delete_linked]' ).prop( 'disabled', ! jQuery( this ).prop( 'checked' ) );
} );
</script>