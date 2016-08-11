<?php
/**
 * This is the template that displays the help screen for a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=help
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog, $Skin, $Settings;

if( evo_version_compare( $app_version, '4.0.0-dev' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 4.0.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

if( $Skin->get_setting( 'width_switcher' ) )
{
	/**
	 * @var string Name of cookie for skin width
	 */
	$cookie_skin_width_name = 'skin_width';

	if( isset( $_COOKIE[ $cookie_skin_width_name ] ) )
	{ // Get skin width from $_COOKIE through param function
		$cookie_skin_width_value = param_cookie( $cookie_skin_width_name, '/^\d+(px|%)$/i', NULL );
		if( empty( $cookie_skin_width_value ) )
		{ // Force illegal value of width to default
			$cookie_skin_width_value = '1140px';
		}
	}
}


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

<div class="header<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">
	<?php
		ob_start();
		// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'         => '',
				'block_end'           => '',
				'block_display_title' => false,
				'list_start'          => '',
				'list_end'            => '',
				'item_start'          => '',
				'item_end'            => '',
			) );
		// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		$page_top_skin_container = ob_get_clean();

		if( ! empty( $page_top_skin_container ) )
		{ // Display 'Page Top' widget container only if it contains something
	?>
	<div class="header_top">
		<div class="layout_width switched_width"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="max-width:'.$cookie_skin_width_value.'"' : ''; ?>>
			<?php echo $page_top_skin_container; ?>
		</div>
	</div>
	<?php } ?>
	<div class="header_bottom">
		<div class="layout_width switched_width"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="max-width:'.$cookie_skin_width_value.'"' : ''; ?>>
		<?php
			// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Header'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="widget $wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		?>
			<div class="clear"></div>
		</div>
	</div>
</div>

<div id="layout" class="layout_width switched_width"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="max-width:'.$cookie_skin_width_value.'"' : ''; ?>>
	<div>

<!-- =================================== START OF MAIN AREA =================================== -->
<div>

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
		request_title( array(
				'title_before'      => '<h2 class="page_title">',
				'title_after'       => '</h2>',
				'title_single_disp' => false,
				'title_page_disp'   => false,
				'format'            => 'htmlbody',
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$' );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

</div>

	</div><?php /** END OF <div id="wrapper"> **/?>
</div><?php /** END OF <div id="layout"> **/?>

<!-- =================================== START OF FOOTER =================================== -->
<div id="footer" class="layout_width switched_width"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="max-width:'.$cookie_skin_width_value.'"' : ''; ?>>
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
					'before' => '',
					'after'  => ' &bull; ',
				) );

		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
		?>

		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before' => '',
					'after'  => ' &bull; ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
			// Display a link to help page:
			$Blog->help_link( array(
					'before' => ' ',
					'after'  => ' &bull; ',
					'text'   => T_('Help'),
				) );

			// Display additional credits:
			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start' => '',
					'list_end'   => ' ',
					'separator'  => '&bull;',
					'item_start' => ' ',
					'item_end'   => ' ',
				) );
		?>
	</p>
</div>

	</div>
</div>
<?php
if( $Skin->get_setting( 'width_switcher' ) )
{ // ------------------------- WIDTH SWITCHER --------------------------

$width_switchers = array(
		'1140px' => 'width_decrease',
		'100%'  => 'width_increase',
	);
if( ! empty( $cookie_skin_width_value ) )
{ // Fix this cookie value because in the cookie we cannot store the width in percent values (See js function switch_width() to understand why)
	$cookie_skin_width_value_fixed = $cookie_skin_width_value != '1140px' ? '100%' : $cookie_skin_width_value;
}

$switcher_layout_top = is_logged_in() ? 29 : 3;
$switcher_layout_top += $Settings->get( 'site_skins_enabled' ) ? 103 : 3; // 153 -> 106

$switcher_top = is_logged_in() ? 29 : 0;
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
	echo '<a href="#" onclick="switch_width( this, \''.$ws_size.'\', \''.$cookie_skin_width_name.'\', \''.get_cookie_path().'\' ); return false;" class="'.$ws_class.'">';
	echo '<span class="ficon '.$ws_icon.'"></span>';
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

var switcher_min_width = 1255;
var switcher_width_type = 'full';

var $switcher = jQuery( '#width_switcher ');
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

} // ------------------------- END OF WIDTH SWITCHER --------------------------

// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>