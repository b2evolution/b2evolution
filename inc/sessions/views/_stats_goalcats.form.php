<?php
/**
 * This file implements the Goal category form.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var GoalCategory
 */
global $edited_GoalCategory;

// Determine if we are creating or updating...
global $action;
$creating = in_array( $action, array( 'cat_new', 'cat_copy', 'cat_create', 'cat_create_new', 'cat_create_copy' ) );

$Form = new Form( NULL, 'goalcat_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( TB_('Delete this goal category...'), 'delete', regenerate_url( 'action', 'action=cat_delete&amp;'.url_crumb( 'goalcat' ) ) );
}
$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action,gcat_ID' ) );

$Form->begin_form( 'fform', $creating ?  TB_('New goal category') : TB_('Goal category') );

	$Form->add_crumb( 'goalcat' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',gcat_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'gcat_name', $edited_GoalCategory->name, 40, TB_('Name'), '', array( 'maxlength'=> 50, 'required'=>true ) );

	$Form->color_input( 'gcat_color', $edited_GoalCategory->color, TB_('Color'), '', array( 'required'=>true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[cat_create]', TB_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[cat_create_new]', TB_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[cat_create_copy]', TB_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[cat_update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>