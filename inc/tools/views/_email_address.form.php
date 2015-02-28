<?php
/**
 * This file display the email address form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_EmailAddress;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'blocked_new';

$Form = new Form( NULL, 'slug_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,emadr_ID' ) );

$Form->begin_form( 'fform', $creating ?  T_('New email address') : T_('Email address') );

	$Form->add_crumb( 'email_blocked' );
	$Form->hidden( 'action', 'blocked_save' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

	$Form->text_input( 'emadr_address', $edited_EmailAddress->get( 'address' ), 50, T_('Email address'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$email_status_icon = '<div id="email_status_icon" class="status_icon">'.emadr_get_status_icon( $edited_EmailAddress->get( 'status' ) ).'</div>';
	$Form->select_input_array( 'emadr_status', $edited_EmailAddress->get( 'status' ), emadr_get_status_titles(), 'Status', '', array( 'force_keys_as_values' => true, 'background_color' => emadr_get_status_colors(), 'required' => true, 'field_suffix' => $email_status_icon ) );

	$Form->info( T_('Last sent date'), mysql2localedatetime_spans( $edited_EmailAddress->get( 'last_sent_ts' ), "M-d" ) );

	$Form->text_input( 'emadr_sent_count', $edited_EmailAddress->get( 'sent_count' ), 20, T_('Sent count'), '' );

	$Form->text_input( 'emadr_sent_last_returnerror', $edited_EmailAddress->get( 'sent_last_returnerror' ), 20, T_('Sent count since last error'), '' );

	$Form->info( T_('Last error date'), mysql2localedatetime_spans( $edited_EmailAddress->get( 'last_error_ts' ), "M-d" ) );

	$Form->text_input( 'emadr_prmerror_count', $edited_EmailAddress->get( 'prmerror_count' ), 20, T_('Permanent errors count'), '' );

	$Form->text_input( 'emadr_tmperror_count', $edited_EmailAddress->get( 'tmperror_count' ), 20, T_('Temporary errors count'), '' );

	$Form->text_input( 'emadr_spamerror_count', $edited_EmailAddress->get( 'spamerror_count' ), 20, T_('Spam errors count'), '' );

	$Form->text_input( 'emadr_othererror_count', $edited_EmailAddress->get( 'othererror_count' ), 20, T_('Other errors count'), '' );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

?>
<script type="text/javascript">
var email_status_icons = new Array;
<?php
$email_status_icons = emadr_get_status_icons();
foreach( $email_status_icons as $status => $icon )
{	// Init js array with email status icons
?>
email_status_icons['<?php echo $status; ?>'] = '<?php echo $icon; ?>';
<?php } ?>

jQuery( '#emadr_status' ).change( function()
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
