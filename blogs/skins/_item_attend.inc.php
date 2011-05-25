<?php
/**
 * This is the template that displays a post attendants
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url;

// Default params:
$params = array_merge( array(
	'attending_start'   => '<div class="bComment">',
	'attending_end'     => '</div>',
	'attend_list_start' => '<ul>',
	'attend_list_end'   => '</ul>',
    'attend_start'      => '<li>',
    'attend_end'        => '</li>',
	'attend_user_field' => 'login', // 'login' or 'prefered_name' or 'namefl' or 'namefl' or 'nickname'
    'Item'              => NULL, // This object MUST be passed as a param!
	), $params );

// current user is attending this event
$is_attendant = false;

/**
 * @var Item
 */
$Item = & $params['Item'];

$attendants = $Item->get_attendants();
$blog_url = $Blog->gen_blogurl();

echo $params['attending_start'];

if( empty( $attendants ) )
{ // no attendants
	echo T_( 'No attendants yet' );
}
else
{
	echo $params['attend_list_start'];
	foreach( $attendants as $attendant )
	{
		$attending_User = new User( $attendant );
		switch( $params['attend_user_field'] )
		{
			case 'prefered_name':
				$link_text = $attending_User->get_preferred_name();
				break;
			case 'namefl':
				$link_text = trim( $attending_User->get( 'firstname' ).' '.$attending_User->get( 'lastname' ) );
				break;
			case 'namelf':
				$link_text = trim( $attending_User->get( 'lastname' ).' '.$attending_User->get( 'firstname' ) );
				break;
			case 'nickname':
				$link_text = $attending_User->get( 'nickname' );
				break;
			case 'login':
			default:
				$link_text = $attending_User->get( 'login' );
				break;
		}
		if( empty( $link_text ) )
		{ // set login as default subscribe text
			$link_text = $attending_User->get( 'login' );
		}

		echo $params['attend_start'];
		echo '<a href="'.$blog_url.'?disp=user&user_ID='.$attending_User->ID.'">'.$link_text.'</a>';
		echo $params['attend_end'];

		if( $attending_User->ID == $current_User->ID )
		{ // current user is already attending this event
			$is_attendant = true;
		}
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
 * Revision 1.1  2011/05/25 14:59:34  efy-asimo
 * Post attending
 *
 */
?>
