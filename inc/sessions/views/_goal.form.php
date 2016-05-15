<?php
/**
 * This file implements the Goal form.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
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
	$Form->global_icon( T_('Delete this goal!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb( 'goal' ) ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New goal') : T_('Goal') );

	$Form->add_crumb( 'goal' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',goal_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$GoalCategoryCache = & get_GoalCategoryCache();
	$GoalCategoryCache->load_all();
	$Form->select_input_object( 'goal_gcat_ID', $edited_Goal->gcat_ID, $GoalCategoryCache, T_('Category'), array( 'required' => true ) );

	$Form->text_input( 'goal_name', $edited_Goal->name, 40, T_('Name'), '', array( 'maxlength'=> 50, 'required' => true ) );

	$Form->text_input( 'goal_key', $edited_Goal->key, 32, T_('Key'), T_('Should be URL friendly'), array( 'required' => true ) );

	$Form->text_input( 'goal_redir_url', $edited_Goal->redir_url, 60, T_('Normal Redirection URL'), '', array( 'maxlength' => 255, 'class' => 'large', 'required' => true ) );

	$Form->text_input( 'goal_temp_redir_url', $edited_Goal->temp_redir_url, 60, T_('Temporary Redirection URL'), '', array( 'maxlength' => 255, 'class' => 'large' ) );

	$Form->begin_line( T_('Temporary Start Date'), 'goal_temp_start_date' );
		$Form->date_input( 'goal_temp_start_date', is_int( $edited_Goal->temp_start_ts ) ? date2mysql( $edited_Goal->temp_start_ts ) : $edited_Goal->temp_start_ts, '' );
		$Form->time_input( 'goal_temp_start_time', is_int( $edited_Goal->temp_start_ts ) ? date2mysql( $edited_Goal->temp_start_ts ) : $edited_Goal->temp_start_ts, T_('at') );
	$Form->end_line();

	$Form->begin_line( T_('Temporary End Date'), 'goal_temp_end_date' );
		$Form->date_input( 'goal_temp_end_date', is_int( $edited_Goal->temp_end_ts ) ? date2mysql( $edited_Goal->temp_end_ts ) : $edited_Goal->temp_end_ts, '' );
		$Form->time_input( 'goal_temp_end_time', is_int( $edited_Goal->temp_end_ts ) ? date2mysql( $edited_Goal->temp_end_ts ) : $edited_Goal->temp_end_ts, T_('at') );
	$Form->end_line();

	$Form->text_input( 'goal_default_value', $edited_Goal->default_value, 15, T_('Default value'), '' );

	$Form->textarea( 'goal_notes', $edited_Goal->get( 'notes' ), 15, T_('Notes'), '', 50 );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
function check_goal_redir_url_required()
{
	if( jQuery( '#goal_temp_redir_url' ).val() == '' )
	{
		jQuery( '#ffield_goal_redir_url .label_field_required' ).hide();
	}
	else
	{
		jQuery( '#ffield_goal_redir_url .label_field_required' ).show();
	}
}

check_goal_redir_url_required();

jQuery( '#goal_temp_redir_url' ).keyup( function()
{
	check_goal_redir_url_required();
} );
</script>