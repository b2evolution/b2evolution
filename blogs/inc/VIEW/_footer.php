<?php
/**
 * This file displays the admin page footer.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @todo Move to {@link AdminUI_general AdminUI} object.
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

// log the hit on this page (according to settings)
global $Hit;
$Hit->log();

debug_info();

?>

</body>
</html>