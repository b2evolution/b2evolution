<?php
/**
 * This file implements the UI controller for link objects.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

global $Collection, $Blog, $Session;

/*
 * Initialize everything
 */
$action = param_action( 'list' );
$redirect_to = param( 'redirect_to', 'url', /*regenerate_url( '', '', '', '&' )*/NULL );
//$mode = 'iframe';

switch( $action )
{
	case 'set_link_position':
		param('link_position', 'string', true);
	case 'unlink':
	case 'delete':
	case 'link_move_up':
	case 'link_move_down':
		// Name of the iframe we want some action to come back to:
		param( 'iframe_name', 'string', '', true );

		// TODO fp> when moving an "after_more" above a "teaser" img, it should change to "teaser" too.
		// TODO fp> when moving a "teaser" below an "aftermore" img, it should change to "aftermore" too.

		param( 'link_ID', 'integer', true );
		$LinkCache = & get_LinkCache();
		if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) !== false )
		{	// We have a link, get the LinkOwner it is attached to:
			$LinkOwner = & $edited_Link->get_LinkOwner();

			// Load the blog we're in:
			$Collection = $Blog = & $LinkOwner->get_Blog();
			set_working_blog( $Blog->ID );
		}
		else
		{	// We could not find the link to edit:
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Link') ), 'error' );
			unset( $edited_Link );
			unset( $link_ID );
			if( $mode == 'iframe' )
			{
				$action = 'edit_links';
			}
			else
			{
				$action = 'nil';
			}
		}
		break;
}

if( $action == 'edit_links' || $action == 'sort_links' )
{ // set LinkOwner from params
	$link_type = param( 'link_type', 'string', 'item', true );
	$object_ID = param( 'link_object_ID', 'integer', 0, true );
	$LinkOwner = get_link_owner( $link_type, $object_ID );
	if( empty( $Blog ) )
	{ // Load the blog we're in:
		$Collection = $Blog = & $LinkOwner->get_Blog();
		set_working_blog( $Blog->ID );
	}
}

if( empty( $LinkOwner ) )
{ // If LinkOwner object is not set, we can't process any action
	$Messages->add( T_('Requested link owner object does not exist any longer.'), 'error' );
	header_redirect( $redirect_to );
}

switch( $action )
{
	case 'edit_links':
		// Display link owner attachments

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );
		break;

	case 'unlink': // Unlink a file from object:
	case 'delete': // Unlink and Delete a file from disk and DB completely:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		if( $link_File = & $edited_Link->get_File() )
		{
			syslog_insert( sprintf( 'File %s was unlinked from %s with ID=%s', '[['.$link_File->get_name().']]', $LinkOwner->type, $LinkOwner->get_ID() ), 'info', 'file', $link_File->ID );
		}

		if( $action == 'delete' && $edited_Link->can_be_file_deleted() )
		{	// Get a linked file to delete it after unlinking if it is allowed for current user:
			$linked_File = & $edited_Link->get_File();
		}

		// Unlink File from Item/Comment:
		$deleted_link_ID = $edited_Link->ID;
		$edited_Link->dbdelete();
		unset( $edited_Link );

		$LinkOwner->after_unlink_action( $deleted_link_ID );

		$Messages->add( $LinkOwner->translate( 'Link has been deleted from $xxx$.' ), 'success' );

		if( $action == 'delete' && ! empty( $linked_File ) )
		{	// Delete a linked file from disk and DB completely:
			$linked_File->unlink();
		}

		header_redirect( $redirect_to );
		break;

	case 'link_move_up':
	case 'link_move_down':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( "link" );

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		$ownerLinks = $LinkOwner->get_Links();

		// TODO fp> when moving an "after_more" above a "teaser" img, it should change to "teaser" too.
		// TODO fp> when moving a "teaser" below an "aftermore" img, it should change to "aftermore" too.

		// Switch order with the next/prev one
		if( $action == 'link_move_up' )
		{
			$switchcond = 'return ($loop_Link->get("order") > $i
				&& $loop_Link->get("order") < '.$edited_Link->get("order").');';
			$i = -1;
		}
		else
		{
			$switchcond = 'return ($loop_Link->get("order") < $i
				&& $loop_Link->get("order") > '.$edited_Link->get("order").');';
			$i = PHP_INT_MAX;
		}
		foreach( $ownerLinks as $loop_Link )
		{ // find nearest order
			if( $loop_Link == $edited_Link )
				continue;

			if( eval($switchcond) )
			{
				$i = $loop_Link->get('order');
				$switch_Link = $loop_Link;
			}
		}
		if( $i > -1 && $i < PHP_INT_MAX )
		{ // switch
			$switch_Link->set('order', $edited_Link->get('order'));

			// HACK: go through order=0 to avoid duplicate key conflict
			$edited_Link->set('order', 0);
			$edited_Link->dbupdate();
			$switch_Link->dbupdate();

			$edited_Link->set('order', $i);
			$edited_Link->dbupdate();


			if( $action == 'link_move_up' )
				$msg = T_('Link has been moved up.');
			else
				$msg = T_('Link has been moved down.');

			$Messages->add( $msg, 'success' );

			// Update last touched date of Owners
			$LinkOwner->update_last_touched_date();
		}
		else
		{
			$Messages->add( T_('Link order has not been changed.'), 'note' );
		}

		header_redirect( $redirect_to );
		break;

	case 'sort_links':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( "link" );

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		$ownerLinks = $LinkOwner->get_Links();
		usort( $ownerLinks, 'sort_links_by_filename' );

		$max_order = 0;
		$link_orders = array();
		$link_count = count( $ownerLinks );
		foreach( $ownerLinks as $link )
		{
			if( $link->order > $max_order )
			{
				$max_order = $link->order;
			}
			$link_orders[] = $link->order;
		}

		for( $i = 1; $i <= $link_count; $i++ )
		{
				$ownerLinks[$i - 1]->set( 'order', $i + $max_order );
				$ownerLinks[$i - 1]->dbupdate();
		}

		for( $i = 1; $i <= $link_count; $i++ )
		{
			if( $ownerLinks[$i -1]->get( 'order' ) != $i )
			{
				$ownerLinks[$i - 1]->set( 'order', $i );
				$ownerLinks[$i - 1]->dbupdate();
			}
		}

		$Messages->add( T_('The attachments have been sorted by file name.'), 'success' );

		// Need to specify where to redirect, otherwise referrer will be used:
		switch( $LinkOwner->type )
		{
			case 'item':
				$redirect_url = $admin_url.'?ctrl=items&action=edit&p='.$LinkOwner->get_ID();
				break;
			case 'comment':
				$redirect_url = $admin_url.'?ctrl=comments&action=edit&comment_ID='.$LinkOwner->get_ID();
				break;
			case 'emailcampaign':
				$redirect_url = $admin_url.'?ctrl=campaigns&action=edit&tab=compose&ecmp_ID='.$LinkOwner->get_ID();
				break;
			default:
				param( 'iframe_name', 'string', '', true );
				$redirect_url = $admin_url.'?ctrl=links&action=edit_links&link_type='.$LinkOwner->type.'&mode=iframe&iframe_name='.$iframe_name.'&link_object_ID='.$LinkOwner->get_ID();
				break;
		}
		header_redirect( $redirect_url );
		break;


	case 'set_link_position':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		if( $edited_Link->set( 'position', $link_position ) && $edited_Link->dbupdate() )
		{
			$Messages->add( T_('Link position has been changed.'), 'success' );

			// Update last touched date of Owners
			$LinkOwner->update_last_touched_date();
		}
		else
		{
			$Messages->add( T_('Link position has not been changed.'), 'note' );
		}

		$header_redirect( $redirect_to );
		break;
}

// require colorbox js
require_js_helper( 'colorbox' );
// require File Uploader js and css
require_js( 'multiupload/fileuploader.js' );
require_css( 'fileuploader.css' );
if( $action == 'edit_links' )
{ // Load JS files to make the links table sortable:
	require_js( '#jquery#' );
	require_js( 'jquery/jquery.sortable.min.js' );
}

$AdminUI->disp_html_head();
$AdminUI->disp_body_top( false );

switch( $action )
{
	case 'edit_links':
		// Memorize 'action' for prev/next links
		memorize_param( 'action', 'string', NULL );

		// Used to get FileRoot ID of the current Blog
		load_class( '/files/model/_fileroot.class.php', 'FileRoot' );

		// View attachments
		$AdminUI->disp_view( 'links/views/_link_list.view.php' );
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>