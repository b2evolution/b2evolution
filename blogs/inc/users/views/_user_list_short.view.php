<?php
/**
 * This file implements the UI view for the user list for user viewing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var DB
 */
global $DB;

global $collections_Module;

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

/*
 * Query user list:
 */
$keywords = param( 'keywords', 'string', '', true );
// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
// $usr_unconfirmed = param( 'usr_unconfirmed', 'boolean', false, true );

$where_clause = '';
$left_join_clause = '';

if( !empty( $keywords ) )
{
	$kw_array = split( ' ', $keywords );
	foreach( $kw_array as $kw )
	{
		// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
		$where_clause .= 'CONCAT_WS( " ", user_login ) LIKE "%'.$DB->escape($kw).'%" AND ';
	}
}

if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
{	// Filter by gender
	$gender_men = param( 'gender_men', 'boolean', false );
	$gender_women = param( 'gender_women', 'boolean', false );
	if( ( $gender_men && ! $gender_women ) || ( ! $gender_men && $gender_women ) )
	{	// Find men OR women
		$where_clause .= 'user_gender = "'.( $gender_men ? 'M' : 'F' ).'" AND ';
	}
	else if( $gender_men && $gender_women )
	{	// Find men AND women
		$where_clause .= 'user_gender IN ( "M", "F" ) AND ';
	}
}

$criteria_type = param( 'criteria_type', 'integer', 0 );
$criteria_value = param( 'criteria_value', 'string' );
if( $criteria_type > 0 && $criteria_value != '' )
{	// Filter by Specific criteria
	$criteria_words = explode( ' ', $criteria_value );
	if( count( $criteria_words ) > 0 )
	{
		$left_join_clause .= ' LEFT JOIN T_users__fields ON uf_user_ID = user_ID AND uf_ufdf_ID = "'.$DB->escape($criteria_type).'"';
		foreach( $criteria_words as $word )
		{
			$where_clause .= 'uf_varchar LIKE "%'.$DB->escape($word).'%" AND ';
		}
	}
}

// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
/*
if( $usr_unconfirmed )
{
	$where_clause .= 'user_validated = 0 AND ';
}
*/
$SQL = new SQL();
$SQL->SELECT( 'T_users.*, ctry_name, IF( IFNULL(user_avatar_file_ID,0), 1, 0 ) as has_picture' );
$SQL->FROM( 'T_users LEFT JOIN T_country ON user_ctry_ID = ctry_ID'.$left_join_clause );
$SQL->WHERE( $where_clause.' 1' );
$SQL->GROUP_BY( 'user_ID' );

if( isset($collections_Module) )
{	// We are handling blogs:
	$SQL->SELECT_add( ', COUNT(DISTINCT blog_ID) AS nb_blogs' );
	$SQL->FROM_add( ' LEFT JOIN T_blogs on user_ID = blog_owner_user_ID' );
}
else
{
	$SQL->SELECT_add( ', 0 AS nb_blogs' );
}

$count_sql = 'SELECT COUNT(*)
							 	FROM T_users'.$left_join_clause.'
							 WHERE '.$where_clause.' 1';

if( $Settings->get('allow_avatars') )
{ // Sort by login
	$default_sort = '-A';
}
else
{ // Sort by login (if pictures are not allowed )
	$default_sort = 'A';
}

$Results = new Results( $SQL->get(), 'user_', $default_sort, NULL, $count_sql );


$Results->filter_area = array(
	'callback' => 'callback_filter_userlist',
	'url_ignore' => 'results_user_page,keywords',
	'presets' => array(
		'all' => array( T_('All users'), get_messaging_url( 'users' ) ),
// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
//		'unconfirmed' => array( T_('Unconfirmed email'), '?ctrl=users&amp;usr_unconfirmed=1' ),
		)
	);


/*
 * Grouping params:
 */
$Results->ID_col = 'user_ID';


/*
 * Data columns:
 */

if( $Settings->get('allow_avatars') )
{
	function user_avatar( $user_ID )
	{
		global $Blog;
		
		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID );
		
		return $User->get_identity_link( array(
			'link_text' => 'only_avatar',
			'thumb_size' => $Blog->get_setting('image_size_user_list'),
			) );
	}
	$Results->cols[] = array(
							'th' => T_('Picture'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap center',
							'order' => 'has_picture',
							'default_dir' => 'D',
							'td' => '%user_avatar( #user_ID# )%',
						);
}

$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '%get_user_identity_link( #user_login#, #user_ID#, "profile", "text" )%',
					);

$Results->cols[] = array(
						'th' => T_('Country'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'ctry_name',
						'td' => '$ctry_name$',
					);

// Display result :
$Results->display( $display_params );


/*
 * $Log$
 * Revision 1.7  2011/10/20 15:22:50  efy-yurybakh
 * new filters for users list
 *
 * Revision 1.6  2011/10/07 17:22:52  efy-yurybakh
 * user avatar display default
 *
 * Revision 1.5  2011/10/07 02:55:38  fplanque
 * doc
 *
 * Revision 1.4  2011/10/05 17:58:54  efy-yurybakh
 * change the default profile picture sizes
 *
 * Revision 1.3  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.2  2011/10/05 07:54:51  efy-yurybakh
 * User directory (fix error if accessed anonymously)
 *
 * Revision 1.1  2011/10/03 13:37:50  efy-yurybakh
 * User directory
 */
?>
