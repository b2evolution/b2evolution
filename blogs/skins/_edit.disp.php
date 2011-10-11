<?php
/**
 * This is the template that displays the edit item form. It gets POSTed to /htsrv/item_edit.php.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI grants Francois PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $Session, $Messages, $inc_path;
global $action, $edited_Item, $form_action, $mode;


/**
 * @var User
 */
global $current_User;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $Session;

global $mode;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $edited_Item, $item_title, $item_content;
global $redirect_to, $advanced_edit_link, $form_action;
global $admin_url;

$p = param( 'p', 'integer', 0 );

if( $p > 0 )
{	// Edit post
	$action = 'edit';
	global $post_ID;
	$post_ID = $p;
	$ItemCache = & get_ItemCache ();
	$edited_Item = $ItemCache->get_by_ID ( $post_ID );
	$item_title = $edited_Item->title;
	$item_content = $edited_Item->content;
	$item_tags = implode( ', ', $edited_Item->get_tags() );
}
else if( empty( $action ) )
{	// Create new post
	$action = 'new';
	$edited_Item = new Item();
	$edited_Item->set( 'status', 'published' );
}

$entries = get_item_edit_modes( $Blog->ID, $action, $admin_url, 'blog='.$Blog->ID );
$advanced_edit_link = $entries['simple'];

$form_action = get_samedomain_htsrv_url().'item_edit.php';

// Display form
switch( $disp )
{
	case 'edit':
		require $inc_path.'items/views/_item_inskin.form.php';
		break;
	default:
		debug_die( "Unknown user tab" );
}


/*
 * $Log$
 * Revision 1.1  2011/10/11 18:26:11  efy-yurybakh
 * In skin posting (beta)
 *
 * 
 */
?>