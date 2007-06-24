<?php
/**
 * This file implements evoSkins support functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Initializes internal states for the most common skin displays.
 *
 * For more specific skins, this function should not be called and
 * equivalent code should be customized within the skin.
 *
 * @param string What are we going to display. Most of the time the global $disp should be passed.
 */
function skin_init( $disp )
{
  /**
	 * @var Blog
	 */
	global $Blog;

	// This is the main template; it may be used to display very different things.
	// Do inits depending on current $disp:
	switch( $disp )
	{
		case 'posts':
		case 'single':
		case 'page':
		case 'feedback-popup':
			// We need to load posts for this display:

			// Note: even if we request the same post as $Item above, the following will do more restrictions (dates, etc.)
			// Init the MainList object:
			init_MainList( $Blog->get_setting('posts_per_page') );

			break;
	}
}


/**
 * Include a sub-template at the current position
 *
 * @todo plugin hook to handle a special disp
 */
function skin_include( $template_name, $params = array() )
{
	global $skins_path, $ads_current_skin_path, $disp;

	// Globals that may be needed by the template:
	global $Blog, $MainList, $Item;
	global $Plugins, $Skin;
	global $current_User, $Hit, $Session;
	global $skin_url, $htsrv_url;
	global $credit_links, $skin_links, $francois_links, $skinfaktory_links;

	if( $template_name == '$disp$' )
	{ // This is a special case.
		// We are going to include a template based on $disp:

		// Default display handlers:
		$disp_handlers = array_merge( array(
				'disp_posts'    => '_posts.disp.php',
				'disp_single'   => '_single.disp.php',
				'disp_page'     => '_page.disp.php',
				'disp_arcdir'   => '_arcdir.php',
				'disp_catdir'   => '_catdir.disp.php',
				'disp_comments' => '_lastcomments.php',
				'disp_msgform'  => '_msgform.php',
				'disp_profile'  => '_profile.php',
				'disp_subs'     => '_subscriptions.php',
			), $params );

		$template_name = $disp_handlers['disp_'.$disp];

		if( !isset( $template_name ) )
		{
			printf( '<div class="skin_error">'.T_('Unhandled disp type [%s]').'</div>', $disp );
			return;
		}

		if( empty( $template_name ) )
		{	// The caller asked not to display this handler
			return;
		}
	}

	if( file_exists( $ads_current_skin_path.$template_name ) )
	{	// The skin has a customized handler, use that one instead:
		require $ads_current_skin_path.$template_name;
	}
	elseif( file_exists( $skins_path.$template_name ) )
	{	// Use the default template:
		require $skins_path.$template_name;
	}
	else
	{
		printf( '<div class="skin_error">'.T_('Sub template [%s] not found.').'</div>', $template_name );
	}
}


/**
 * Template function: output HTML base tag to current skin
 */
function skin_base_tag()
{
	global $skins_url, $skin, $Blog, $disp;

	if( ! empty( $skin ) )
	{
		$base_href = $skins_url.$skin.'/';
	}
	else
	{ // No skin used:
		if( ! empty( $Blog ) )
		{
			$base_href = $Blog->gen_baseurl();
		}
		else
		{
			$base_href = $baseurl;
		}
	}

	$target = NULL;
	if( !empty($disp) && strpos( $disp, '-popup' ) )
	{	// We are (normally) displaying in a popup window, we need most links to open a new window!
		$target = '_blank';
	}

	base_tag( $base_href, $target );
}


/**
 * Output content-type header
 * 
 * We use this method when we are NOT generating a static page
 *
 * @see skin_content_meta()
 * 
 * @param string content-type; override for RSS feeds
 */
function skin_content_header( $type = 'text/html' )
{
	global $generating_static, $io_charset;

	if( empty($generating_static) )
	{	// We use this method when we are NOT generating a static page
		header( 'Content-type: '.$type.'; charset='.$io_charset );
	}
}


/**
 * Output content-type http_equiv meta tag
 * 
 * We use this method when we ARE generating a static page
 *
 * @see skin_content_header()
 *
 * @param string content-type; override for RSS feeds
 */
function skin_content_meta( $type = 'text/html' )
{
	global $generating_static, $io_charset;

	if( ! empty($generating_static) )
	{	// We use this method when we ARE generating a static page
		echo '<meta http-equiv="Content-Type" content="'.$type.'; charset='.$io_charset.'" />';
	}
}


/**
 * Display a Widget.
 *
 * This load the widget class, instantiates it, and displays it.
 *
 * @param array
 */
function skin_widget( $params )
{
	if( empty( $params['widget'] ) )
	{
		echo 'No widget code provided!';
		return false;
	}

	$widget_code = $params['widget'];
	unset( $params['widget'] );

	if( ! load_class( 'MODEL/widgets/_'.$widget_code.'.widget.php', false ) )
	{	// For some reason, that widget doesn't seem to exist... (any more?)
		echo "Invalid widget code provided [$widget_code]!";
		return false;
	}

	$widget_classname = $widget_code.'_Widget';

  /**
	 * @var ComponentWidget
	 */
	$Widget = new $widget_classname();	// COPY !!

	return $Widget->display( $params );
}


/**
 * Checks if a skin is provided by a plugin.
 *
 * @uses Plugin::GetProvidedSkins()
 * @return false|integer False in case no plugin provides the skin or ID of the first plugin that provides it.
 */
function skin_provided_by_plugin( $name )
{
	static $plugin_skins;
	if( ! isset($plugin_skins) || ! isset($plugin_skins[$name]) )
	{
		global $Plugins;

		$plugin_r = $Plugins->trigger_event_first_return('GetProvidedSkins', NULL, array('in_array'=>$name));
		if( $plugin_r )
		{
			$plugin_skins[$name] = $plugin_r['plugin_ID'];
		}
		else
		{
			$plugin_skins[$name] = false;
		}
	}

	return $plugin_skins[$name];
}


/**
 * Checks if a skin exists. This can either be a regular skin directory
 * or can be in the list {@link Plugin::GetProvidedSkins()}.
 *
 * @param skin name (directory name)
 * @return boolean true is exists, false if not
 */
function skin_exists( $name, $filename = 'main.tpl.php' )
{
	global $skins_path;

	if( is_readable( $skins_path.$name.'/'.$filename ) )
	{
		return true;
	}

	// Check list provided by plugins:
	if( skin_provided_by_plugin($name) )
	{
		return true;
	}

	return false;
}


/**
 * Install a skin
 *
 * @todo do not install if skin doesn't exist. Important for upgrade. Need to NOT fail if ZERO skins installed though :/
 *
 * @param string
 * @param string NULL for default
 * @return Skin
 */
function & skin_install( $skin_folder, $name = NULL )
{
	load_class( 'MODEL/skins/_skin.class.php' );
	$edited_Skin = new Skin(); // COPY (FUNC)

	$edited_Skin->set( 'name', empty( $name) ? $skin_folder : $name );
	$edited_Skin->set( 'folder', $skin_folder );
	$edited_Skin->set( 'type', substr($skin_folder,0,1) == '_' ? 'feed' : 'normal' );

	// Look for containers in skin file:
	$edited_Skin->discover_containers();

	// INSERT NEW SKIN INTO DB:
	$edited_Skin->dbinsert();

	return $edited_Skin;
}



function app_version()
{
	global $app_version;
	echo $app_version;
}


/*
 * $Log$
 * Revision 1.23  2007/06/24 01:05:31  fplanque
 * skin_include() now does all the template magic for skins 2.0.
 * .disp.php templates still need to be cleaned up.
 *
 * Revision 1.22  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.21  2007/05/28 15:18:31  fplanque
 * cleanup
 *
 * Revision 1.20  2007/05/07 18:59:45  fplanque
 * renamed skin .page.php files to .tpl.php
 *
 * Revision 1.19  2007/05/07 18:03:28  fplanque
 * cleaned up skin code a little
 *
 * Revision 1.18  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.17  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.16  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.15  2007/01/14 01:33:34  fplanque
 * losely restrict to *installed* XML feed skins
 *
 * Revision 1.14  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.13  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.12  2006/11/13 17:00:02  fplanque
 * doc
 *
 * Revision 1.11  2006/10/30 12:57:33  blueyed
 * Fix for XHTML
 *
 * Revision 1.10  2006/10/08 22:59:31  blueyed
 * Added GetProvidedSkins and DisplaySkin hooks. Allow for optimization in Plugins::trigger_event_first_return()
 *
 * Revision 1.9  2006/09/05 22:29:21  fplanque
 * fixed content types (I hope)
 *
 * Revision 1.8  2006/08/18 17:23:58  fplanque
 * Visual skin selector
 *
 * Revision 1.7  2006/08/02 13:00:51  fplanque
 * detect incomplete upgrade of conf file
 *
 * Revision 1.6  2006/07/25 18:38:38  fplanque
 * fixed skin list
 *
 * Revision 1.5  2006/07/24 00:05:44  fplanque
 * cleaned up skins
 *
 * Revision 1.4  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.3  2006/03/24 19:40:49  blueyed
 * Only use absolute URLs if necessary because of used <base/> tag. Added base_tag()/skin_base_tag(); deprecated skinbase()
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 */
?>