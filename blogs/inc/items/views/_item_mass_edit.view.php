<?php
/**
 * This file implements the UI for posts mass edit.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id$
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

global $redirect_to;

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
	
	$Form->text( 'mass_title_'.$Item->ID , $Item->get( 'title'), 70, T_('Title'), '', 255 );
	$Form->text( 'mass_urltitle_'.$Item->ID, $Item->get( 'urltitle'), 70, T_('URL title "slug"'), '', 255 );
	$Form->text( 'mass_titletag_'.$Item->ID, $Item->get( 'titletag'), 70, T_( htmlspecialchars('<title> tag') ), '', 255 );

	$Form->end_fieldset();
}

// Submit & reset buttons
$Form->buttons( array(array('submit', 'actionArray[mass_save]', T_('Save changes'), 'SaveButton' ),
					array('reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();


/*
 * $Log$
 * Revision 1.4  2011/09/04 22:13:17  fplanque
 * copyright 2011
 *
 * Revision 1.3  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>