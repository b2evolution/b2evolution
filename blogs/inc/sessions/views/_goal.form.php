<?php
/**
 * This file implements the Goal form.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Goal
 */
global $edited_Goal;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

// These params need to be memorized and passed through regenerated urls: (this allows to come back to the right list order & page)
param( 'results_goals_page', 'integer', '', true );
param( 'results_goals_order', 'string', '', true );

$Form = new Form( NULL, 'goal_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( T_('Delete this goal!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New goal') : T_('Goal') );

	$Form->add_crumb( 'goal' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',goal_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'goal_name', $edited_Goal->name, 40, T_('Name'), '', array( 'maxlength'=> 50, 'required'=>true ) );

	$Form->text_input( 'goal_key', $edited_Goal->key, 32, T_('Key'), T_('Should be URL friendly'), array( 'required'=>true ) );

	$Form->text_input( 'goal_redir_url', $edited_Goal->redir_url, 60, T_('Redirection URL'), '', array( 'maxlength'=> 255, 'class'=>'large' ) );

	$Form->text_input( 'goal_default_value', $edited_Goal->default_value, 15, T_('Default value'), '' );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


/*
 * $Log$
 * Revision 1.9  2010/01/30 18:55:33  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.8  2010/01/03 16:28:35  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.7  2009/09/19 20:49:51  fplanque
 * Cleaner way of implementing permissions.
 *
 * Revision 1.6  2009/08/31 14:22:10  tblue246
 * Better fix
 *
 * Revision 1.4  2009/08/30 20:58:10  tblue246
 * Goals ctrl: 1. Do not use localized messages to determine action. 2. Removed redundant "copy" action (always use "new" action with goal_ID).
 *
 * Revision 1.3  2009/05/16 00:28:04  fplanque
 * Return to the right list page & order after editing.
 *
 * Revision 1.2  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.1  2008/04/17 11:53:20  fplanque
 * Goal editing
 *
 */
?>
