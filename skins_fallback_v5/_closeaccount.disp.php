<?php
/**
 * This is the template that displays the close account page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $account_closing_success, $account_close_reason;

if( ! empty( $account_closing_success ) )
{ // Display a bye message after user closed an account
	echo '<p>'.nl2br( $Settings->get( 'account_close_byemsg' ) ).'</p>';
}
else
{ // Display a form to close an account

	$Form = new Form( get_htsrv_url( true ).'login.php' );

	$Form->begin_form( 'inskin' );

	$Form->add_crumb( 'closeaccountform' );
	$Form->hidden( 'redirect_to', url_add_param( $Blog->gen_blogurl(), 'disp=closeaccount', '&' ) );
	$Form->hidden( 'action', 'closeaccount' );

	// Display intro message
	echo '<p>'.nl2br( $Settings->get( 'account_close_intro' ) ).'</p>'."\n";

	// Display the reasons
	$reasons = trim( $Settings->get( 'account_close_reasons' ) );
	if( ! empty( $reasons ) )
	{
		$reasons = explode( "\n", str_replace( array( "\r\n", "\n\n" ), "\n", $reasons ) );
		$reasons[] = NT_('Other').':';
		$reasons_options = array();
		foreach( $reasons as $reason )
		{
			$reasons_options[] = array( 'value' => $reason, 'label' => $reason );
		}
		$Form->radio_input( 'account_close_type', '', $reasons_options, '<b>'.T_('Reason').'</b>', array( 'lines' => true ) );
	}

	$Form->textarea_input( 'account_close_reason', $account_close_reason, 3, NULL, array( 'cols' => 40, 'maxlength' => 255 ) );
	$Form->info_field( NULL, '%s characters left', array( 'class' => 'section_requires_javascript dimmed', 'name' => 'character_counter' ) );
?>
	<script type="text/javascript">
		<?php echo 'var counter_text = "'.T_( '%s characters left' ).'";';?>
		var characterCounter = jQuery("#ffield_character_counter > div");
		var otherReason = jQuery("#account_close_reason");
		jQuery("#ffield_account_close_reason").css( { marginBottom: 0 } );
		jQuery("div.form-group.radio-group").css( { marginBottom: 0 } );
		characterCounter.html( counter_text.replace( "%s", 255 - otherReason[0].value.length ) );
		otherReason.bind( "keyup", function(event)
		{
			var char_left = 255 - this.value.length;
			if( char_left < 0 )
			{
				char_left = 0;
			}
			characterCounter.html( counter_text.replace( "%s", char_left ) );
		} );
	</script>
	<noscript>
		<?php echo T_( '255 characters max' ); ?>
	</noscript>
<?php
	echo '</div>';

	$Form->buttons( array( array( 'submit', 'submit', T_('Close my account now'), 'SaveButton' ) ) );

	$Form->info( '', '<a href="'.$Blog->get( 'userurl' ).'">'.T_( 'I changed my mind, don\'t close my account.' ).'</a>' );

	$Form->end_form();

} // End of form
?>