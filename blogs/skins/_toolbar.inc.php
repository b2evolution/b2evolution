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
	user_logout_link( ' ', ' ' );
?>
</div>

<div class="actions_left">
<?php
	$evo_toolbar_title = '<strong>b2evolution</strong>';
	user_admin_link( '', '', '', $evo_toolbar_title, '#', $evo_toolbar_title );

	echo '<a href="'.$Blog->get( 'dynurl' ).'">'.T_('Blog').'</a>';

	user_admin_link( ' ', ' ' );
?>
</div>

</div>