<?php
/**
 * This is the BODY footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $cookie_skin_width_name, $cookie_skin_width_value, $cookie_path;
global $Settings, $Session;
?>

	</div><?php /** END OF <div id="wrapper"> **/?>
</div><?php /** END OF <div id="layout"> **/?>

<div class="clear"></div>

<!-- =================================== START OF FOOTER =================================== -->
<div id="pageFooter">
	<?php
		// Display container and contents:
		skin_container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
	<p class="baseline">
		<?php
			// Display footer text (text can be edited in Blog Settings):
			$Blog->footer_text( array(
					'before'      => '',
					'after'       => ' &bull; ',
				) );

		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
		?>

		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' &bull; ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
			// Display a link to help page:
			$Blog->help_link( array(
					'before'      => ' ',
					'after'       => ' ',
					'text'        => T_('Help'),
				) );
		?>

		<?php
			// Display additional credits:
			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start'  => '&bull;',
					'list_end'    => ' ',
					'separator'   => '&bull;',
					'item_start'  => ' ',
					'item_end'    => ' ',
				) );
		?>
	</p>
</div>


<?php
// ------------------------- WIDTH SWITCHER --------------------------
$width_switchers = array(
		'960px' => 'width_decrease',
		'100%'  => 'width_increase',
	);
if( !empty( $cookie_skin_width_value ) )
{ // Fix this cookie value because in the cookie we cannot store the width in percent values (See js function switch_width() to understand why)
	$cookie_skin_width_value_fixed = $cookie_skin_width_value != '960px' ? '100%' : $cookie_skin_width_value;
}

$switcher_layout_top = is_logged_in() ? 26 : 3;
$switcher_layout_top += $Settings->get( 'site_skins_enabled' ) ? 153 : 3;

$switcher_top = is_logged_in() ? 26 : 0;
$switcher_top += $Settings->get( 'site_skins_enabled' ) ? 54 : 0;

$switcher_class = !$Settings->get( 'site_skins_enabled' ) ? ' fixed' : '';
?>
<div id="width_switcher_layout"<?php echo $switcher_layout_top ? ' style="top:'.$switcher_layout_top.'px"' : ''; ?>>
	<div id="width_switcher"<?php echo $switcher_top ? ' style="top:'.$switcher_top.'px"' : ''; ?> class="roundbutton_group<?php echo $switcher_class; ?>">
<?php
$ws = 0;
$ws_count = count( $width_switchers );
foreach( $width_switchers as $ws_size => $ws_icon )
{
	$ws_class = 'roundbutton';
	if( ( !empty( $cookie_skin_width_value ) && $cookie_skin_width_value_fixed == $ws_size ) ||
	    ( empty( $cookie_skin_width_value ) && $ws == 0 ) )
	{	// Mark this switcher as selected
		$ws_class .= ' roundbutton_selected';
	}
	echo '<a href="#" onclick="switch_width( this, \''.$ws_size.'\', \''.$cookie_skin_width_name.'\', \''.$cookie_path.'\' ); return false;" class="'.$ws_class.'">';
	echo get_icon( $ws_icon );
	echo '</a>';
	$ws++;
}
?>
	</div>
</div>
<?php
if( $Settings->get( 'site_skins_enabled' ) )
{ // Change position of width switcher only when Site Header is displayed
?>
<script type="text/javascript">
var has_touch_event;
window.addEventListener( 'touchstart', function set_has_touch_event ()
{
	has_touch_event = true;
	// Remove event listener once fired, otherwise it'll kill scrolling
	window.removeEventListener( 'touchstart', set_has_touch_event );
}, false );

var $switcher = jQuery( '#width_switcher' );
var switcher_size = $switcher.size();
var switcher_top = <?php echo $switcher_top ?>;
jQuery( window ).scroll( function ()
{
	if( has_touch_event )
	{ // Don't fix the objects on touch devices
		return;
	}

	if( switcher_size )
	{ // Width switcher exists
		if( !$switcher.hasClass( 'fixed' ) && jQuery( window ).scrollTop() > $switcher.offset().top - switcher_top )
		{ // Make switcher as fixed if we scroll down
			$switcher.addClass( 'fixed' );
		}
		else if( $switcher.hasClass( 'fixed' ) && jQuery( window ).scrollTop() < jQuery( '#width_switcher_layout' ).offset().top - switcher_top )
		{ // Remove 'fixed' class from switcher if we scroll to the top of page
			$switcher.removeClass( 'fixed' );
		}
	}
} );
</script>
<?php
}
// ------------------------- END OF WIDTH SWITCHER --------------------------
?>