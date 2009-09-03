<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Country
 */
global $edited_Country;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'country_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this country!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New country') : T_('Country') );

	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ctry_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'ctry_code', $edited_Country->code, 2, T_('Code'), '', array( 'maxlength'=> 2, 'required'=>true ) );

	$Form->text_input( 'ctry_name', $edited_Country->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

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

?>