<?php
/**
 * This file implements the Comment form for mass deleting.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _comment_mass.form.php 1273 2012-04-20 13:23:46Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $tab3;

$Form = new Form( regenerate_url( 'action', '', '', '&' ), 'comment_massdelete' );

$Form->global_icon( T_('Cancel deleting!'), 'close', '?ctrl=comments&blog='.$Blog->ID.'&tab3='.$tab3, T_('cancel'), 4, 1 );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'comment' );
$Form->hidden( 'ctrl', 'comments' );

	$Form->begin_fieldset( T_('Mass deleting') );

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
			'style' => 'margin-left:25%'
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