<?php
/**
 * This is the template that displays the close account page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $secure_htsrv_url;

global $Blog;

$Form = new Form( $secure_htsrv_url.'login.php' );

$Form->begin_form( 'inskin' );

$Form->add_crumb( 'closeaccountform' );
$Form->hidden( 'redirect_to', $Blog->gen_blogurl() );
$Form->hidden( 'action', 'closeaccount');

echo '<p>'.T_( 'We are sorry to see you leave.' ).'</p>'."\n";
echo '<p>'.T_( 'We value your feedback. Please be so kind and tell us in a few words why you are leaving us. This will help us to improve the site for the future.' ).'</p>';

$Form->textarea_input( 'account_close_reason', '', 6, NULL, array( 'cols' => 40, 'class' => 'large', 'maxlength' => 255 ) );
echo '<div id="character_counter" class="section_requires_javascript">';
echo '<div id="characters_left_block"></div>';
?>
	<script type="text/javascript">
		<?php echo 'var counter_text = "'.T_( '%s characters left' ).'";';?>
		jQuery("#characters_left_block").html( counter_text.replace( "%s", 255 ) );
		jQuery("#account_close_reason").bind( "keyup", function(event)
		{
			var char_left = 255 - this.value.length;
			if( char_left < 0 )
			{
				char_left = 0;
			}
			jQuery("#characters_left_block").html( counter_text.replace( "%s", char_left ) );
		} );
	</script>
	<noscript>
		<?php echo T_( '255 characters max' ); ?>
	</noscript>
<?php
echo '</div>';

$Form->buttons( array( array( 'submit', 'submit', T_('Close my account now'), 'SaveButton' ) ) );

$Form->info( '', '<a href="'.url_add_param( $Blog->gen_blogurl(), 'disp=user' ).'">'.T_( 'I changed my mind, don\'t close my account.' ).'</a>' );

$Form->end_form();
?>