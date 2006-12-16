<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author blueyed: Daniel HAHLER
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $Plugins;


$Form = & new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'advanced' );
$Form->hidden( 'blog',$edited_Blog->ID );

$Form->begin_fieldset( T_('Media library') );
global $basepath, $media_subdir;
$Form->radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
		array(
			array( 'none', T_('None') ),
			array( 'default', T_('Default'),
				sprintf( T_('subdirectory &quot;%s&quot; (URL blog name) of %s'), $edited_Blog->urlname, $basepath.$media_subdir ) ),
			array( 'subdir', T_('Subdirectory of media folder').':',
				'',
				' <span class="nobr"><code>'.$basepath.$media_subdir.'</code><input
					type="text" name="blog_media_subdir" size="20" maxlength="255"
					class="'.( param_has_error('blog_media_subdir') ? 'field_error' : '' ).'"
					value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
			array( 'custom',
				T_('Custom location').':',
				'',
				'<fieldset>'
				.'<div class="label">'.T_('directory').':</div><div class="input"><input
					type="text" name="blog_media_fullpath" size="50" maxlength="255"
					class="'.( param_has_error('blog_media_fullpath') ? 'field_error' : '' ).'"
					value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
				.'<div class="label">'.T_('URL').':</div><div class="input"><input
					type="text" name="blog_media_url" size="50" maxlength="255"
					class="'.( param_has_error('blog_media_url') ? 'field_error' : '' ).'"
					value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
		), T_('Media dir location'), true
	);
$Form->end_fieldset();

$Form->begin_fieldset( T_('After each new post...') );
	$ping_plugins = preg_split( '~\s*,\s*~', $edited_Blog->get_setting('ping_plugins'), -1, PREG_SPLIT_NO_EMPTY);

	$available_ping_plugins = $Plugins->get_list_by_event('ItemSendPing');
	$displayed_ping_plugin = false;
	if( $available_ping_plugins )
	{
		foreach( $available_ping_plugins as $loop_Plugin )
		{
			if( empty($loop_Plugin->code) )
			{ // Ping plugin needs a code
				continue;
			}
			$displayed_ping_plugin = true;

			$checked = in_array( $loop_Plugin->code, $ping_plugins );
			$Form->checkbox_input( 'blog_ping_plugins[]', $checked, /* TRANS: verb */ T_('Ping ').$loop_Plugin->ping_service_name, array('value'=>$loop_Plugin->code, 'note'=>$loop_Plugin->ping_service_note) );

			while( ($key = array_search($loop_Plugin->code, $ping_plugins)) !== false )
			{
				unset($ping_plugins[$key]);
			}
		}
	}
	if( ! $displayed_ping_plugin )
	{
		echo '<p>'.T_('There are no ping plugins activated.').'</p>';
	}

	// Provide previous ping services as hidden fields, in case the plugin is temporarily disabled:
	foreach( $ping_plugins as $ping_plugin_code )
	{
		$Form->hidden( 'blog_ping_plugins[]', $ping_plugin_code );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Meta data') );
	$Form->text( 'blog_description', $edited_Blog->get( 'description' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );
	$Form->text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );
	$Form->textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 5, T_('Notes'), T_('Additional info. Appears in the backoffice.'), 50, 'large' );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Static file generation'), array( 'class'=>'fieldset clear' ) );
	$Form->text( 'blog_staticfilename', $edited_Blog->get( 'staticfilename' ), 30, T_('Static filename'), T_('This is the .html file that will be created when you generate a static version of the blog homepage.') );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.15  2006/12/16 01:30:47  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 */
?>