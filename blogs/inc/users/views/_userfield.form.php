<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
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

	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ufdf_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	if( $creating )
	{
		$Form->text_input( 'new_ufdf_ID', '', 8, T_('ID'), '', array( 'maxlength'=> 10, 'required'=>true ) );
	}
	else
	{
		$Form->hidden( 'ufdf_ID', $edited_Userfield->ID );
	}

	$Form->select_input_array( 'ufdf_type', $edited_Userfield->type, $edited_Userfield->get_types(),
		T_('Type'), '', array( 'required'=>true ) );

	$Form->text_input( 'ufdf_name', $edited_Userfield->name, 50, T_('Name'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	// Show this textarea only for field type with "Option list"
	echo '<div id="div_ufdf_options"'. ( $edited_Userfield->type != 'list' ? ' style="display:none"' : '' ) .'>';
	$Form->textarea_input( 'ufdf_options', $edited_Userfield->options, 10, T_('Options'), array( 'required' => true, 'note' => T_('Enter one option per line') ) );
	echo '</div>';

	if( $edited_Userfield->required )
	{
		$Form->radio_input( 'ufdf_required', $edited_Userfield->required, $edited_Userfield->get_requireds(), T_('Required?'), array( 'required'=>true ) );
	}
	else
	{
		$Form->radio_input( 'ufdf_required', 'optional', $edited_Userfield->get_requireds(), T_('Required?'), array( 'required'=>true ) );
	}

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
	} );
</script>
<?php

/*
 * $Log$
 * Revision 1.10  2011/10/18 12:28:13  efy-yurybakh
 * Info fields: select lists - give list of configurable options
 *
 * Revision 1.9  2011/09/10 22:48:41  fplanque
 * doc
 *
 * Revision 1.8  2011/08/29 08:51:14  efy-james
 * Default / mandatory additional fields
 *
 * Revision 1.7  2010/01/03 17:45:21  fplanque
 * crumbs & stuff
 *
 * Revision 1.6  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2009/09/17 00:55:24  fplanque
 * fix
 *
 * Revision 1.4  2009/09/16 18:19:05  fplanque
 * Readded with -kkv option
 *
 */
?>
