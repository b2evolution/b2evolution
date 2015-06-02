<?php
/**
 * This file implements the UI controller for link objects.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

global $Blog, $Session;

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
			$Blog = & $LinkOwner->get_Blog();
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

if( $action == 'edit_links' )
{ // set LinkOwner from params
	$link_type = param( 'link_type', 'string', 'item', true );
	$object_ID = param( 'link_object_ID', 'integer', 0, true );
	$LinkOwner = get_link_owner( $link_type, $object_ID );
	if( empty( $Blog ) )
	{ // Load the blog we're in:
		$Blog = & $LinkOwner->get_Blog();
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

		// Add JavaScript to handle links modifications.
		require_js( 'links.js' );
		break;

	case 'unlink':
		// Delete a link:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( "link" );

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		if( $link_File = & $edited_Link->get_File() )
		{
			syslog_insert( sprintf( 'File %s was unlinked from %s with ID=%s', '<b>'.$link_File->get_name().'</b>', $LinkOwner->type, $LinkOwner->link_Object->ID ), 'info', 'file', $link_File->ID );
		}
		// Unlink File from Item/Comment:
		$deleted_link_ID = $edited_Link->ID;
		$edited_Link->dbdelete();
		unset( $edited_Link );

		$LinkOwner->after_unlink_action( $deleted_link_ID );

		$Messages->add( $LinkOwner->translate( 'Link has been deleted from $xxx$.' ), 'success' );

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
			$edited_Link->dbupdate( true );
			$switch_Link->dbupdate( true );

			$edited_Link->set('order', $i);
			$edited_Link->dbupdate( true );


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