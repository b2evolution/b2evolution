<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _userfield.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfield.class.php', 'Userfield' );

/**
 * @var Userfield
 */
global $edited_Userfield;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'userfield_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this userfield!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('userfield') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New user field') : T_('User field') );

	$Form->add_crumb( 'userfield' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$Form->select_input_array( 'ufdf_ufgp_ID', $edited_Userfield->ufgp_ID, $edited_Userfield->get_groups(),
		T_('Group'), '', array( 'required' => true, 'force_keys_as_values' => true ) );

	$Form->text_input( 'ufdf_name', $edited_Userfield->name, 50, T_('Field name'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$Form->select_input_array( 'ufdf_type', $edited_Userfield->type, $edited_Userfield->get_types(),
		T_('Field type'), '', array( 'required' => true ) );

	// Show this textarea only for field type with "Option list"
	echo '<div id="div_ufdf_options"'. ( $edited_Userfield->type != 'list' ? ' style="display:none"' : '' ) .'>';
	$Form->textarea_input( 'ufdf_options', $edited_Userfield->options, 10, T_('Options'), array( 'required' => true, 'note' => T_('Enter one option per line') ) );
	echo '</div>';

	// Suggest values only for field type with "Single word"
	echo '<div id="div_ufdf_suggest"'. ( $edited_Userfield->type != 'word' ? ' style="display:none"' : '' ) .'>';
	$Form->checkbox_input( 'ufdf_suggest', $edited_Userfield->suggest, T_('Suggest values') );
	echo '</div>';

	$Form->radio_input( 'ufdf_duplicated', $edited_Userfield->duplicated, $edited_Userfield->get_duplicateds(), T_('Multiple values'), array( 'required'=>true, 'lines'=>true ) );

	$Form->radio_input( 'ufdf_required', $edited_Userfield->required, $edited_Userfield->get_requireds(), T_('Required?'), array( 'required'=>true ) );

	$Form->textarea_input( 'ufdf_bubbletip', $edited_Userfield->bubbletip, 5, T_('Bubbletip text') );

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
	jQuery( '#ufdf_type' ).change( function()
	{	// Show textarea input only for field type with "Option list"
		if( jQuery( this ).val() == 'list' )
		{
			jQuery( '#div_ufdf_options' ).show();
		}
		else
		{
			jQuery( '#div_ufdf_options' ).hide();
		}
		// Suggest values only for field type with "Single word"
		if( jQuery( this ).val() == 'word' )
		{
			jQuery( '#div_ufdf_suggest' ).show();
		}
		else
		{
			jQuery( '#div_ufdf_suggest' ).hide();
		}
	} );
</script>