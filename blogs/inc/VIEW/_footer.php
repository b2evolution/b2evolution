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
global $UserSettings, $IconLegend;
if( $UserSettings->get('display_icon_legend') <> 0 )
{	// Display icons legend
	$IconLegend->display_legend();
}

// Close open divs, etc...
echo $this->get_body_bottom();

global $app_footer_text;
echo '<p class="footer">'.$app_footer_text."</p>\n\n";

// CALL PLUGINS NOW:
global $Plugins;
$Plugins->trigger_event( 'AdminAfterPageFooter', array() );


if( $this->get_path(0) == 'files'
	|| $this->get_path_range(0,1) == array( 'blogs', 'perm' )
	|| $this->get_path_range(0,1) == array( 'blogs', 'permgroup' ) )
{ // init checkall JS functions
	?>
	<script type="text/javascript">
		initcheckall();
		<?php
		if( $this->get_path(0) == 'files' )
		{
			global $checkall;
			?> setcheckallspan(0<?php if( isset($checkall) ) echo ', '.(int)$checkall; ?>); <?php
		}
		?>
	</script>
	<?php
}

debug_info();

?>

</body>
</html>