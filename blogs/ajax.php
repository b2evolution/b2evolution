<?php

require_once dirname(__FILE__).'/conf/_config.php';
require_once $inc_path.'_main.inc.php';

global $DB;

$action = $_POST['action'];

switch( $action )
{
	case 'login_list':

		$text = trim( $_POST['login_list_value'] );
		if( !empty( $text ) )
		{
			$SQL = &new SQl();
			$SQL->SELECT( 'user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_login LIKE \''.$text.'%\'' );
			$SQL->LIMIT( '10' );

			$logins = array();
			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$logins[] = $row->user_login;
			}
			echo implode( ';', $logins );
		}

		break;
}

?>