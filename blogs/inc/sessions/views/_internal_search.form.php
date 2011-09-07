<?php
/**
 * This file implements the Internal search item form.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.com/}
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
global $edited_intsearch;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

// These params need to be memorized and passed through regenerated urls: (this allows to come back to the right list order & page)
param( 'results_internalsearches_page', 'integer', '', true );
param( 'results_internalsearches_order', 'string', '', true );

$Form = new Form( NULL, 'internalsearches_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( T_('Delete this search item!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New internal search item') : T_('Internal search item') );

	$Form->add_crumb( 'internalsearches' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',isrch_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'isrch_coll_ID', $edited_intsearch->coll_ID, 40, T_('Blog'), '', array( 'maxlength'=> 50, 'required'=>true ) );

	$Form->text_input( 'isrch_session_ID', $edited_intsearch->session_ID, 32, T_('Session'), '' , array( 'required'=>true ) );

	$Form->text_input( 'isrch_keywords', $edited_intsearch->keywords, 60, T_('Keywords'), '', array( 'maxlength'=> 255, 'class'=>'large' ) );


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
 * Revision 1.1  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.10  2011/09/05 Alexander
 * copyright 2009 -> 2010
 */
?>
