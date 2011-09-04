<?php
/**
 * This is the template that displays a post attendees
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url;

// Default params:
$params = array_merge( array(
	'attending_start'   => '<div class="bComment">',
	'attending_end'     => '</div>',
	'attend_list_start' => '<table width="100%"><tbody align="center">',
	'attend_list_end'   => '</tbody></table>',
	'attend_line_start' => '<tr>',
	'attend_line_end'   => '</tr>',
	'attend_start'      => '<td>',
	'attend_end'        => '</td>',
	'attend_user_field' => 'login', // 'login' or 'prefered_name' or 'namefl' or 'namefl' or 'nickname'
	'Item'              => NULL, // This object MUST be passed as a param!
	), $params );

// current user is attending this event
$is_attendant = false;

/**
 * @var Item
 */
$Item = & $params['Item'];

$attendees = $Item->get_attendees();
$blog_url = $Blog->gen_blogurl();

echo $params['attending_start'];

if( empty( $attendees ) )
{ // no attendees
	echo T_( 'No attendees yet' );
}
else
{
	$disp_count = 0;
	$line_start_displayed = false;
	echo $params['attend_list_start'];
	foreach( $attendees as $attendant )
	{
		if( ( $disp_count % 3 ) == 0 )
		{
			if( $line_start_displayed )
			{
				echo $params['attend_line_end'];
			}
			echo $params['attend_line_start'];
			$line_start_displayed = true;
		}
		$disp_count++;
		$attending_User = new User( $attendant );
		if( empty( $link_text ) )
		{ // set login as default subscribe text
			$link_text = $attending_User->get( 'login' );
		}

		echo $params['attend_start'];
		echo '<a href="'.$blog_url.'?disp=user&user_ID='.$attending_User->ID.'">';
		$attending_User->disp( $params['attend_user_field'] );
		echo '</a>';
		echo $params['attend_end'];

		if( $attending_User->ID == $current_User->ID )
		{ // current user is already attending this event
			$is_attendant = true;
		}
	}
	if( $line_start_displayed )
	{
		echo $params['attend_line_end'];
	}
	echo $params['attend_list_end'];
}

echo '<div>';
if( $is_attendant )
{
	echo ' <a href="'.$htsrv_url.'isubs_update.php?p='.$Item->ID.'&amp;type=attend&amp;notify=0&amp;'.url_crumb( 'itemsubs' ).'">'.T_( 'Unregister from attending this event.' ).'</a></p>';
}
else
{
	echo ' <a href="'.$htsrv_url.'isubs_update.php?p='.$Item->ID.'&amp;type=attend&amp;notify=1&amp;'.url_crumb( 'itemsubs' ).'">'.T_( 'Register to attend this event.' ).'</a></p>';
}
echo '</div>';

echo $params['attending_end'];

/*
 * $Log$
 * Revision 1.4  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.3  2011/09/04 21:32:17  fplanque
 * minor MFB 4-1
 *
 * Revision 1.2  2011/05/30 13:35:30  efy-asimo
 * Use table to display item attendants
 *
 * Revision 1.1  2011/05/25 14:59:34  efy-asimo
 * Post attending
 *
 */
?>
