<?php
/**
 * This file display the email address form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_EmailBlocked;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'blocked_new';

$Form = new Form( NULL, 'slug_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,emblk_ID' ) );

$Form->begin_form( 'fform', $creating ?  T_('New email address') : T_('Email address') );

	$Form->add_crumb( 'email_blocked' );
	$Form->hidden( 'action', 'blocked_save' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

	$Form->text_input( 'emblk_address', $edited_EmailBlocked->get( 'address' ), 50, T_('Email address'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$email_status_icon = '<div id="email_status_icon">'.emblk_get_status_icon( $edited_EmailBlocked->get( 'status' ) ).'</div>';
	$Form->select_input_array( 'emblk_status', $edited_EmailBlocked->get( 'status' ), emblk_get_status_titles(), 'Status', '', array( 'force_keys_as_values' => true, 'background_color' => emblk_get_status_colors(), 'required' => true, 'field_suffix' => $email_status_icon ) );

	$Form->info( T_('Last sent date'), mysql2localedatetime_spans( $edited_EmailBlocked->get( 'last_sent_ts' ), "M-d" ) );

	$Form->text_input( 'emblk_sent_count', $edited_EmailBlocked->get( 'sent_count' ), 20, T_('Sent count'), '' );

	$Form->text_input( 'emblk_sent_last_returnerror', $edited_EmailBlocked->get( 'sent_last_returnerror' ), 20, T_('Sent count since last error'), '' );

	$Form->info( T_('Last error date'), mysql2localedatetime_spans( $edited_EmailBlocked->get( 'last_error_ts' ), "M-d" ) );

	$Form->text_input( 'emblk_prmerror_count', $edited_EmailBlocked->get( 'prmerror_count' ), 20, T_('Permanent errors count'), '' );

	$Form->text_input( 'emblk_tmperror_count', $edited_EmailBlocked->get( 'tmperror_count' ), 20, T_('Temporary errors count'), '' );

	$Form->text_input( 'emblk_spamerror_count', $edited_EmailBlocked->get( 'spamerror_count' ), 20, T_('Spam errors count'), '' );

	$Form->text_input( 'emblk_othererror_count', $edited_EmailBlocked->get( 'othererror_count' ), 20, T_('Other errors count'), '' );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
							array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
							array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

?>
<script type="text/javascript">
var email_status_icons = new Array;
<?php
$email_status_icons = emblk_get_status_icons();
foreach( $email_status_icons as $status => $icon )
{	// Init js array with email status icons
?>
email_status_icons['<?php echo $status; ?>'] = '<?php echo $icon; ?>';
<?php } ?>

jQuery( '#emblk_status' ).change( function()
{
	if( typeof email_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#email_status_icon' ).html( email_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#email_status_icon' ).html( '' );
	}
} );
</script>
<?php


/*
 * $Log$
 * Revision 1.2  2013/11/06 08:04:55  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>