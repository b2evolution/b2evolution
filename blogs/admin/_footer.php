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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// Close open divs, etc...
echo $AdminUI->getBodyBottom();
?>

<p class="footer">
	<a href="http://www.b2evolution.net">
	<strong><span style="color:#333">b</span><sub><span style="color:#f90;margin-top:2ex;">2</span></sub><span style="color:#333">e</span><span style="color:#543">v</span><span style="color:#752">o</span><span style="color:#962">l</span><span style="color:#b72">u</span><span style="color:#c81">t</span><span style="color:#d91">i</span><span style="color:#e90">o</span><span style="color:#f90">n</span></strong>
		<?php echo $app_version; ?>
	</a>
	&ndash;
	<a href="http://b2evolution.net/about/license.html" class="nobr"><?php echo T_('GPL License') ?></a>
	&ndash;
	<span class="nobr">&copy;2001-2002 by <a href="http://cafelog.com/">Michel V</a> &amp; others</span>
	&ndash;
	<span class="nobr">&copy;2003-2005 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> &amp; others.</span>
</p>

<?php
// CALL PLUGINS NOW:
$Plugins->trigger_event( 'AdminAfterPageFooter', array() );


if( $AdminUI->getPath(0) == 'files' || $AdminUI->getPathRange(0,1) == array( 'blogs', 'perm' ) )
{ // init checkall JS functions
	?>
	<script type="text/javascript">
		initcheckall();
		<?php
		if( $AdminUI->getPath(0) == 'files' )
		{
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
