<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author yabbbs
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require dirname(__FILE__).'/_header.php';

$AdminUI->setPath( 'options', 'registration' );

param( 'action', 'string' );

if( $action == 'update' )
{
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );

	// clear settings cache
	$cache_settings = '';

	// UPDATE registration settings:

	if ( ! $Settings->get('activation_key') ) 
			{	// no activation key so create one
				$Settings->set('activation_key' , md5( date('U') ) );
			}

	param( 'newusers_canregister', 'integer', 0 );
	$Settings->set( 'newusers_canregister', $newusers_canregister );

	param( 'newusers_grp_ID', 'integer', true );
	$Settings->set( 'newusers_grp_ID', $newusers_grp_ID );

	$Request->param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );
	$Settings->set( 'newusers_level', $newusers_level );

	param( 'use_rules' , 'integer' , 0 );
	$Settings->set( 'use_rules' , $use_rules );

	param( 'the_rules' , 'string' , '' );
	//	Moved to file - settings max length is 255
	fileWrite( $admin_dirout.$htsrv_subdir.'_rules.txt' , format_to_post( $the_rules ) );

	param( 'use_mail' , 'integer' , 0 );
	$Settings->set( 'use_mail' , $use_mail );

	param( 'confmail' , 'string' , '' );
	//	moved to file
	fileWrite( $admin_dirout.$htsrv_subdir.'_email.txt' , format_to_post( $confmail ) );

	if( ! $Messages->count('error') )
	{
		if( $Settings->updateDB() )
		{
			$Messages->add( T_('Registration settings updated.'), 'success' );
		}
	}

}


/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';


// Check permission:
$current_User->check_perm( 'options', 'view', true );

// Begin payload block:
$AdminUI->dispPayloadBegin();
?>
<h3>Info</h3>
<p>I didn't want to mess with the installer, so here's how to make it all happen for now</p>
<ol>
<li>Create a new blog name : = default stub = default</li>
<li>Create any categories for blog (not got sub cats working yet)</li>
<li>Create any "welcome" posts you want [name] will be replaced by the users name</li>
<li>Create a new user : name = default</li>
<li>Assign 'default user' rights to 'default blog' - this will be used to assign 'new user' rights to 'new blog'</li>
<li>Assign 'default user' rights to any other blogs - 'new user' will be given same rights to same blogs</li>
<li>Cross your fingers and register</li>
</ol>
<?php
require dirname(__FILE__).'/_set_reg.form.php';

// End payload block:
$AdminUI->dispPayloadEnd();

require dirname(__FILE__).'/_footer.php';

function fileWrite( $filename , $filecontents )
{
	global $Messages;
	
	if (!$handle = fopen($filename, 'w'))
	{
		$Messages->add( sprintf( T_( 'Unable to write %s , please check your permissions on this file' ) , $filename ) , 'error' );
		exit;
	}

	if ( fwrite( $handle , $filecontents) === FALSE)
	{
		$Messages->add( sprintf( T_( 'Unable to write %s , please check your permissions on this file' ) , $filename ) , 'error' );
		exit;
	}

	$Messages->add( sprintf( T_( '%s updated' ) , $filename ) , 'success' );

	fclose($handle);
}

?>