<?php
/**
 * This file displays the admin page footer.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( empty($mode) )
{
	/**
	 * Icon Legend
	 */
	if( $IconLegend = & get_IconLegend() )
	{ // Display icon legend, if activated by user
		$IconLegend->display_legend();
	}

	echo $this->get_footer_contents();
}

// CALL PLUGINS NOW:
global $Plugins;
$Plugins->trigger_event( 'AdminAfterPageFooter', array() );

if( empty($mode) )
{ // Close open divs, etc...
	echo $this->get_body_bottom();
}

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

// fp TODO: check if this makes sense here
include_footerlines(); // enables translation strings for js

?>

<!-- End of skin_wrapper -->
</div>

</body>
</html>