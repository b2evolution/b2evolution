<?php
/**
 * Confirm new user registration
 *
 * @package htsrv
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.inc.php';


param( 'action', 'string', '' );
param( 'locale', 'string', $Settings->get('default_locale') );
param( 'yourname', 'string', '' );
param( 'email', 'string', '' );
param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
param( 'pass1', 'string', '' );
param( 'pass2', 'string', '' );
param( 'blogname', 'string', '' );
param( 'key', 'string', '' );
$next_action = 'create';

locale_activate( $locale );

if(!$Settings->get('newusers_canregister'))
{
	$action = 'disabled';
}

switch( $action )
{
	case 'create':
		/*
		 * complete the registration:
		 */

		profile_check_params( array( 'login' => $yourname,
																	'pass1' => $pass1,
																	'pass2' => $pass2,
																	'email' => $email,
																	'pass_required' => true ) );

		if( $UserCache->get_by_login( $yourname ) )
		{ // The login is already registered
			$Messages->add( sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $yourname ), 'error' );
			break;
		}

		/* I don't want to mess with your user cache so the following doesn't exists
		
		if ( $UserCache->get_by_email( $email ) )
		{ //	The email is already registered

			$Messages->add( sprintf( T_( 'The email &quote;%s&quote; is already registered, if you have forgotten your password use the link below.' ) , $email ), 'error' );
			break;
		}
		
		*/
		
		// Replicate above function
		global $DB;
		if( $row = $DB->get_row( 'SELECT *
																FROM T_users
																	WHERE user_email = "'.$DB->escape($email).'"', 0, 0, 'Get User email' ) )
		{ 
			$Messages->add( sprintf( T_( 'The email &quote;%s&quote; is already registered, if you have forgotten your password use the link below.' ) , $email ), 'error' );
			break;
		}
		
		if ( ! ( md5( $Settings->get('activation_key').$email == $key ) ) )
		{	//	email does not match activation key, possible spam attempt
			$Messages->add( sprintf( T_( 'The email &quote;%s&quote does not match the email that this activation key was sent to.' ) , $email ) , 'error' );
			break;
		}
		
		if ( !$blogname )
		{	//	Empty blog name !
			$Messages->add( T_( 'You must enter a name for your blog' ) , 'error' );
		}
		
		elseif( $blogname !=preg_replace( '/[^\w@]/' , '' , $blogname ) )
		{	//	Only allow letters a numbers
			$Messages->add( T_( 'Your blog name can only include letters and numbers' ) , 'error' );
		}
		
		elseif ( $BlogCache->get_by_urlname( $blogname , false ) )
		{ //	A blog of this name already exists
			//	Should this also be checked against the blacklist ?
			$Messages->add( sprintf( T_('A blog already exists called &quote;%s&quote, please choose another name.' ) , $blogname ) , 'error' );
		}
		
		if( !$Messages->count( 'error' ) )
		{	// We have a confirmed email and unique user / email / blog

			//	Create the new user

			$new_User = & new User();
			$new_User->set( 'login', $yourname );
			$new_User->set( 'pass', md5($pass1) ); // encrypted
			$new_User->set( 'nickname', $yourname );
			$new_User->set( 'email', $email );
			$new_User->set( 'ip', getIpList( true ) );
			$new_User->set( 'domain', $Hit->getRemoteHost() );
			$new_User->set( 'browser', $Hit->getUserAgent() );
			$new_User->set_datecreated( $localtimenow );
			$new_User->set( 'locale', $locale );
			$newusers_grp_ID = $Settings->get('newusers_grp_ID');
			$new_user_Group = $GroupCache->get_by_ID( $newusers_grp_ID );
			$new_User->setGroup( $new_user_Group );
			$new_User->dbinsert();

			$UserCache->add( $new_User );


			//	Create the blog , if default blog exists then use it's settings and posts

			if ( $default_Blog = $BlogCache->get_by_urlname( 'default' , false) )
			{	//	We have a default blog so base new blog on its settings
				$new_Blog = $default_Blog;
			}
			else
			{	//	We don't have a default blog so lets just create the basics
				$new_Blog = & new Blog();
			}
			$new_Blog->name = $blogname;
			$new_Blog->shortname = substr( $blogname , 0 , 12 );
			$new_Blog->siteurl = $blogname;
			$new_Blog->stub = $blogname;
			
			if ( ! isset( $new_Blog->links_blog_ID ) )
				$new_Blog->links_blog_ID = 'NULL';

			$new_Blog_ID = blog_create(
										$new_Blog->name ,
										$new_Blog->shortname ,
										$new_Blog->siteurl ,
										$new_Blog->stub ,
										$new_Blog->stub.'.html' ,
										$new_Blog->tagline ,
										$new_Blog->shortdesc ,
										$new_Blog->longdesc ,
										$new_Blog->locale ,
										$new_Blog->notes ,
										$new_Blog->keywords ,
										$new_Blog->links_blog_ID ,
										$new_Blog->UID ,
										$new_Blog->allowcomments ,
										$new_Blog->allowtrackbacks ,
										$new_Blog->allowpingbacks ,
										$new_Blog->pingb2evonet ,
										$new_Blog->pingtechnorati ,
										$new_Blog->pingweblogs ,
										$new_Blog->pingblodotgs ,
										$new_Blog->disp_bloglist ,
										$new_Blog->in_bloglist
										);
			if ( $default_Blog )
			{	//	Create the default categories

				//	Don't know if there's a function I can call, so I'll have to do it the hard way
				if ( $default_cats = $DB->get_results( "select * from T_categories where cat_blog_ID = '$default_Blog->ID'" , ARRAY_A ) )
				{
					$the_Cats='';
					foreach ($default_cats as $default_cat)
					{	//	Create each category in the new blog
						$new_cat_list[$default_cat['cat_ID']] = cat_create( $default_cat['cat_name'] , 'NULL' , $new_Blog_ID );
						$the_Cats .= "or post_main_cat_ID='".$default_cat['cat_ID']."'";
					}

					//	Create the default posts

					//	Don't know if there's a function I can call, so once again use a sledgehammer to crack a nut
					if ( $default_posts = $DB->get_results( "select * from T_posts where ".substr( $the_Cats , 3 ) , ARRAY_A ) )
					{
						foreach ( $default_posts as $default_post)
						{	//	don't forget to change the category , author and date
							$temp_item = new Item();
							$temp_item->creator_user_ID = $new_User->ID;
							$temp_item->lastedit_user_ID = $new_User-> ID;
							$temp_item->status = $default_post['post_status'];
							$temp_item->locale = $default_post['post_locale'];
							$temp_item->content = str_replace( '[name]' , $yourname , $default_post['post_content'] );
							$temp_item->title = $default_post['post_title'];
							$temp_item->main_cat_ID = $new_cat_list[$default_post['post_main_cat_ID']];
							$temp_item->flags = $default_post['post_flags'];
							$temp_item->wordcount = $default_post['post_wordcount'];
							$temp_item->renderers = $default_post['post_renderers'];
							$temp_item->dbinsert();
						}
					}
					//	Create the default permissions based on default user
					if ( $default_user = $UserCache->get_by_login('default') )
					{
						//	and use a sledgehammer for the final time
						if ( $all_perms = $DB->get_results( "select * from T_coll_user_perms where bloguser_user_ID = '$default_user->ID'" , ARRAY_A ) )
						{	//	Default user has permissions so lets copy them for the new user

							foreach ( $all_perms as $a_perm )
							{
								if ( $a_perm['bloguser_blog_ID'] == $default_Blog->ID )
									$a_perm['bloguser_blog_ID'] = $new_Blog_ID;

								$DB->query( "insert into T_coll_user_perms (bloguser_user_ID , bloguser_blog_ID , bloguser_ismember , bloguser_perm_poststatuses , bloguser_perm_delpost , bloguser_perm_comments , bloguser_perm_cats , bloguser_perm_properties , bloguser_perm_media_browse , bloguser_perm_media_change )
																								values( '".$new_User->ID."' ,
																												'".$a_perm['bloguser_blog_ID']."' ,
																												'".$a_perm['bloguser_ismember']."' ,
																												'".$a_perm['bloguser_perm_poststatuses']."' ,
																												'".$a_perm['bloguser_perm_delpost']."' ,
																												'".$a_perm['bloguser_perm_comments']."' ,
																												'".$a_perm['bloguser_perm_cats']."' ,
																												'".$a_perm['bloguser_perm_properties']."' ,
																												'".$a_perm['bloguser_perm_media_browse']."' ,
																												'".$a_perm['bloguser_perm_media_change']."')" );
							}
						}
					}
				}
			}
			// Display registration completed screen 
			require( dirname(__FILE__).'/_reg_complete.php' );
			exit();
		}
			break;

	case 'disabled':
		/*
		 * Registration disabled:
		 */
		require( dirname(__FILE__).'/_reg_disabled.php' );

		exit();
}


/*
 * Default: confirmation form:
 */
param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
// Display reg form:
require( dirname(__FILE__).'/_confirmation_form.php' );
?>