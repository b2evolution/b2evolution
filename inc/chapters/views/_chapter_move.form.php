<?php
/**
 * This file implements the Chapter form
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

/**
 * @var Chapter
 */
global $edited_Chapter;

/**
 * @var BlogCache
 */
global $BlogCache;

global $action;

$Form = new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel move!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Move category') );

$Form->add_crumb( 'element' );
$Form->hidden( 'action', 'update_move' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Properties') );

	$Form->info( T_('Name'), $edited_Chapter->name );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();

	$Form->select_input_options( $edited_Chapter->dbprefix.'coll_ID', $BlogCache->get_option_list( $edited_Blog->ID ), T_('Attached to blog'), T_('If you select a new blog, you will be able to choose a position within this blog on the next screen.') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>