<?php
/**
 * This file displays the admin page footer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @todo Move to {@link AdminUI_general AdminUI} object.
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Icon Legend
 */
if( $UserSettings->get('legend') <> 0 )
{	// Display icons legend
	$IconLegend->display_legend();
}

// Close open divs, etc...
echo $AdminUI->get_body_bottom();

echo '<p class="footer">'.$app_footer_text."</p>\n\n";

// CALL PLUGINS NOW:
$Plugins->trigger_event( 'AdminAfterPageFooter', array() );


if( $AdminUI->get_path(0) == 'files' || $AdminUI->get_path_range(0,1) == array( 'blogs', 'perm' ) || $AdminUI->get_path_range(0,1) == array( 'blogs', 'permgroup' ) )
{ // init checkall JS functions
	?>
	<script type="text/javascript">
		initcheckall();
		<?php
		if( $AdminUI->get_path(0) == 'files' )
		{
			?> setcheckallspan(0<?php if( isset($checkall) ) echo ', '.(int)$checkall; ?>); <?php
		}
		?>
	</script>
	<?php
}

// $Hit->log();	// log the hit on this page

debug_info();

?>

</body>
</html>