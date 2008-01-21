<?php
/**
 * This file displays the admin page footer.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Icon Legend
 */
global $IconLegend;
if( isset($IconLegend) )
{ // Display icon legend, if activated by user
	$IconLegend->display_legend();
}

echo $this->get_footer_contents();

// CALL PLUGINS NOW:
global $Plugins;
$Plugins->trigger_event( 'AdminAfterPageFooter', array() );


// Close open divs, etc...
echo $this->get_body_bottom();


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

<!-- End of skin_wrapper -->
</div>

</body>
</html>
<?php
/*
 * $Log$
 * Revision 1.5  2008/01/21 15:02:01  fplanque
 * fixed evobar
 *
 * Revision 1.4  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/09/17 01:36:39  fplanque
 * look 'ma: just spent 5 hours on a smooth sized footer logo :P
 *
 * Revision 1.2  2007/07/16 02:53:04  fplanque
 * checking in mods needed by the chicago adminskin,
 * so that incompatibilities with legacy & evo can be detected early.
 *
 * Revision 1.1  2007/06/25 11:02:34  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/05/02 18:28:05  fplanque
 * copyright credits logo
 *
 * Revision 1.8  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.7  2006/11/26 01:42:09  fplanque
 * doc
 */
?>
