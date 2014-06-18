<?php
/**
 * This file implements the UI for posts mass edit.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _item_mass_edit.view.php 6225 2014-03-16 10:01:05Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var ItemList2
 */
global $ItemList;

global $redirect_to, $current_User, $admin_url;

$perm_slugs_view = $current_User->check_perm( 'slugs', 'view' );

$Form = new Form();

$redirect_to = regenerate_url( 'action', '', '', '&' );
$Form->global_icon( T_('Cancel editing!'), 'close', $redirect_to, 4, 2 );

$Form->begin_form( 'fform', T_('Mass edit the current post list') );

// hidden params
$Form->add_crumb( 'item' );
$Form->hidden( 'ctrl', 'items' );
$Form->hidden( 'blog', $Blog->ID );
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'filter', 'restore' );

// Run the query:
$ItemList->query();

if( $ItemList->get_num_rows() > 100 )
{
	$Form->info( '', sprintf( T_('There are %d posts in your selection, only the first 100 are displayed'), $ItemList->get_num_rows() ) );
}

/*
 * Display posts:
 */
while( $Item = & $ItemList->get_item() )
{
	if( $ItemList->current_idx > 100 )
	{
		break;
	}

	$Form->begin_fieldset( '', array( 'class' => 'fieldset clear' ));

	$edit_slug_link = '';
	if( $perm_slugs_view )
	{	// user has permission to view slugs:
		$edit_slug_link = '&nbsp;'.action_icon( T_('Edit slugs...'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$Item->ID );
	}

	$Form->text( 'mass_title_'.$Item->ID , htmlspecialchars_decode( $Item->get( 'title' ) ), 70, T_('Title'), '', 255 );
	$Form->text( 'mass_urltitle_'.$Item->ID, $Item->get_slugs(), 70, T_('URL slugs').$edit_slug_link, '', 255 );
	$Form->text( 'mass_titletag_'.$Item->ID, $Item->get( 'titletag' ), 70, evo_htmlspecialchars( T_('<title> tag') ), '', 255 );

	$Form->end_fieldset();
}

// Submit button
$Form->buttons( array( array( 'submit', 'actionArray[mass_save]', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>