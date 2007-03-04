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

global $Blog;

global $is_admin_page;

?>

<div id="evo_toolbar">

<div class="actions_right">
<?php
 	user_profile_link( ' ', ' ', T_('User:').'%s' );
	user_subs_link( ' ', ' ' );

	if( $is_admin_page )
	{
  	blog_home_link( T_('Blog').' '.get_icon('switch-to-blog'), T_('Home').' '.get_icon('switch-to-blog') );
	}
	else
	{
		user_admin_link( ' ', ' ', T_('Admin').' '.get_icon('switch-to-admin') );
	}
	user_logout_link( ' ', ' ', T_('Logout').' '.get_icon('close'), '#', array('class'=>'rollover') );
?>
</div>

<div class="actions_left">

<ul>
	<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">
		<strong><?php
			$evo_toolbar_title = 'b2evolution '.get_icon('dropdown');
			user_admin_link( '', '', $evo_toolbar_title, '#', $evo_toolbar_title );
			// Note: if <strong></strong> is inside of the link, rollover fails in IE7
		?></strong>
    <ul style="width:22ex;"><!-- size because of HR in IE7 -->
			<?php user_admin_link( '<li>', '</li>', T_('Admin interface'), '#' ) ?>
		  <li>
  			<?php blog_home_link( T_('Blog home'), T_('Home page') ); ?>
			</li>
			<li class="separator"><hr /></li>
      <li><a href="http://b2evolution.net/" target="_blank"><?php echo T_('Open b2evolution.net') ?></a></li>
      <li><a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Open Support forums') ?></a></li>
      <li><a href="http://manual.b2evolution.net/" target="_blank"><?php echo T_('Open Online manual') ?></a></li>
		</ul>
	</li>

  <li>
  	<?php
  		// this link will be removed
  		user_admin_link( ' ', ' ', T_('Admin') );

  		// Add:
  		// Write
  		// Edit / Browse (which word?)
  		// Upload
  		// Other?
  	?>
	</li>

	<?php
	if( $is_admin_page )
	{
		$admin_skins = get_admin_skins();
		if( count( $admin_skins ) > 1 )
		{	// We have several admin skins available: display switcher:
			echo '<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">';
			echo '<a href="#">'.T_('test').' '.get_icon('dropdown').'</a>';
			echo '<ul>';
			foreach( $admin_skins as $admin_skin )
			{
				echo '<li><a href="'.regenerate_url().'">'.$admin_skin.'</a></li>';
			}
			echo '</ul>';
			echo '</li>';
		}
	}
	?>
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
