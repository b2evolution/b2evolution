<?php
/**
 * This file displays the admin page footer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

// Close open divs, etc...
require dirname(__FILE__).'/'.$adminskins_subdir.$admin_skin.'/_body_bottom.php';
?>

<p class="footer">
<strong><span style="color:#333333">b</span><span style="color:#ff9900">2</span><span style="color:#333333">e</span><span style="color:#554433">v</span><span style="color:#775522">o</span><span style="color:#996622">l</span><span style="color:#bb7722">u</span><span style="color:#cc8811">t</span><span style="color:#dd9911">i</span><span style="color:#ee9900">o</span><span style="color:#ff9900">n</span></strong> <?php echo $app_version, ' '; ?>
-
<a href="http://b2evolution.net/about/license.html" class="nobr"><?php echo T_('GPL License') ?></a>
-
<span class="nobr">&copy;2001-2002 by <a href="http://cafelog.com/">Michel V</a></span>
-
<span class="nobr">&copy;2003-2004 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a></span>
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

// Close close body, html....
require dirname(__FILE__).'/'.$adminskins_subdir.$admin_skin.'/_page_footer.php';
?>
