<?php
/**
 * This is the Evo Toolbar include template.
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( ! is_logged_in() )
{
	return;
}

?>

<div id="evo_toolbar">

<div class="actions_right">
<?php
 	user_profile_link( ' ', ' ', T_('User:').'%s' );
	user_subs_link( ' ', ' ' );
	user_login_link( ' ', ' ' );
	user_logout_link( ' ', ' ', T_('Logout').' '.get_icon('close'), '#', array('class'=>'rollover') );
?>
</div>

<div class="actions_left">

<ul>
	<li>
	<?php
		$evo_toolbar_title = '<strong>b2evolution</strong>';
		user_admin_link( '', '', $evo_toolbar_title, '#', $evo_toolbar_title );
	?>
	</li>

  <li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">
  	<?php echo '<a href="'.$Blog->get( 'dynurl' ).'">'.T_('Blog').' '.get_icon('dropdown').'</a>'; ?>
    <ul>
      <li><a href="">First Item</a></li>
      <li><a href="">Second Item</a></li>
		</ul>
	</li>

  <li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">
  	<?php user_admin_link( ' ', ' ', T_('Admin').' '.get_icon('dropdown') ); ?>
    <ul>
      <li><a href="">First Item</a></li>
      <li><a href="">Second Item</a></li>
		</ul>
	</li>
</ul>

</div>

</div>
<script type="text/javascript">
	function evo_menu_show( elt )
	{
		x = elt.offsetLeft;
		y = elt.offsetTop + elt.offsetHeight;
		child = elt.firstChild;
		while( child.nodeName != 'UL' )
		{
			child = child.nextSibling;
			if( child == null )
			{
				break;
			}
		}
		child.style.left = x + 'px';
		child.style.top = y + 'px';
		elt.className = 'menu_open'; // This is for IE6 which has no li:hover support
	}
	function evo_menu_hide( elt )
	{
		elt.className = 'menu_close'; // This is for IE6 which has no li:hover support
	}
</script>
