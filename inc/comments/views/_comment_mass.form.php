<?php
/**
 * This file implements the Comment form for mass deleting.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $tab3, $admin_url;

$Form = new Form( regenerate_url( 'action', '', '', '&' ), 'comment_massdelete' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'comment' );
$Form->hidden( 'ctrl', 'comments' );

	$Form->begin_fieldset( T_('Mass deleting').get_manual_link( 'comment-mass-deletion' ).' '.action_icon( T_('Cancel deleting!'), 'close', $admin_url.'?ctrl=comments&blog='.$Blog->ID.'&tab3='.$tab3, '', 4, 1, array( 'class' => 'action_icon pull-right btn btn-default btn-sm' ) ) );

	$mass_type_value = 'delete';
	$mass_types = array();
	if( !$CommentList->is_trashfilter( false ) )
	{	// Don't display this option if trashed comments are also displayed
		$mass_types[] = array( 'value' => 'recycle', 'label' => T_('Move to Recycle bin') );
		$mass_type_value = 'recycle';
	}
	$mass_types[] = array( 'value' => 'delete', 'label' => T_('Delete permanently') );

	$Form->labelstart = '<b>';
	$Form->labelend = '</b>';
	$Form->radio_input( 'mass_type', $mass_type_value, $mass_types, sprintf( T_('Are you sure you want to mass delete %s comments?'), $CommentList->get_total_rows() ), array( 'lines' => true ) );

	$Form->submit_input( array(
			'id' => 'mass_submit',
			'name' => 'actionArray[mass_delete]',
			'value' => $mass_type_value == 'recycle' ? T_('Recycle Now!') : T_('Delete Now!'),
			'style' => 'margin-left:25%',
			'class' => 'btn-danger'
		) );

	$Form->end_fieldset();

$Form->end_form();
?>
<script type="text/javascript">
jQuery( 'input[name=mass_type]' ).click( function()
{
	if( jQuery( this ).val() == 'delete' )
	{
		jQuery( '#mass_submit' ).val( '<?php echo TS_('Delete Now!') ?>' );
	}
	else
	{
		jQuery( '#mass_submit' ).val( '<?php echo TS_('Recycle Now!') ?>' );
	}
} );
</script>