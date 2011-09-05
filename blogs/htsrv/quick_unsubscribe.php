<?php
/**
 * Initialize everything:
 */

require_once dirname(dirname(__FILE__)).'/conf/_config.php';

require_once dirname(dirname(__FILE__)).'/inc/_main.inc.php';

param( 'type', 'string', true );
param( 'user_ID', 'integer', true );
param( 'key', 'string', true );
param( 'coll_ID', 'integer', 0 );
param( 'post_ID', 'integer', 0 );

$UserCache = & get_UserCache();
$edited_User = $UserCache->get_by_ID( $user_ID, false, false );

// User not found
if( empty( $edited_User ) )
{
	echo T_( 'Invalid user!' );
	exit;
}

// Security check
if( $key != md5( $user_ID.$edited_User->get( 'unsubscribe_key' ) ) )
{
	echo T_( 'Invalid unsubscribe link1!' );
	exit;
}

switch( $type )
{
	case 'collection':
		// unsubscribe from blog
		if( $coll_ID == 0 )
		{
			echo T_( 'Invalid unsubscribe link!' );
			exit;
		}

		$DB->query( 'UPDATE T_subscriptions SET sub_comments = 0
						WHERE sub_user_ID = '.$user_ID.' AND sub_coll_ID = '.$coll_ID );
		break;

	case 'post':
		// unsubscribe from a specific post
		if( $post_ID == 0 )
		{
			echo T_( 'Invalid unsubscribe link!' );
			exit;
		}

		$DB->query( 'DELETE FROM T_items__subscriptions
						WHERE isub_user_ID = '.$user_ID.' AND isub_item_ID = '.$post_ID );
		break;

	case 'creator':
		// unsubscribe from the user own posts
		$edited_User->set( 'notify', 0 );
		$edited_User->dbupdate();
		break;
}

echo( T_( 'You have successfuly unsubscribed!' ) );
exit;

/*
 * $Log$
 * Revision 1.2  2011/09/05 15:54:33  sam2kb
 * minor
 *
 * Revision 1.1  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 */
?>