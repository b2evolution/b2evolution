<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $Plugins, $Settings;

global $basepath, $rsc_url, $dispatcher, $admin_url;

$Form = new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'advanced' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( T_('Workflow').get_manual_link('coll-workflow-settings') );
	$Form->checkbox( 'blog_use_workflow', $edited_Blog->get_setting( 'use_workflow' ), T_('Use workflow'), T_('This will notably turn on the Tracker tab in the Posts view.') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('After each new post...').get_manual_link('after_each_new_post') );
	if( $edited_Blog->get_setting( 'allow_access' ) == 'users' )
	{
		echo '<p class="center orange">'.T_('This collection is for logged in users only.').' '.T_('It is recommended to keep pings disabled.').'</p>';
	}
	elseif( $edited_Blog->get_setting( 'allow_access' ) == 'members' )
	{
		echo '<p class="center orange">'.T_('This collection is for members only.').' '.T_('It is recommended to keep pings disabled.').'</p>';
	}
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
			$Form->checkbox_input( 'blog_ping_plugins[]', $checked, /* TRANS: %s is a ping service name */ sprintf( T_('Ping %s'), $loop_Plugin->ping_service_name ), array('value'=>$loop_Plugin->code, 'note'=>$loop_Plugin->ping_service_note) );

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


$Form->begin_fieldset( T_('External Feeds').get_manual_link('external_feeds') );

	$Form->text_input( 'atom_redirect', $edited_Blog->get_setting( 'atom_redirect' ), 50, T_('Atom Feed URL'),
	T_('Example: Your Feedburner Atom URL which should replace the original feed URL.').'<br />'
			.sprintf( T_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_atom' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

	$Form->text_input( 'rss2_redirect', $edited_Blog->get_setting( 'rss2_redirect' ), 50, T_('RSS2 Feed URL'),
	T_('Example: Your Feedburner RSS2 URL which should replace the original feed URL.').'<br />'
			.sprintf( T_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_rss2' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

$Form->end_fieldset();


if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Aggregation').' ['.T_('Admin').']'.get_manual_link('collection_aggregation_settings') );
		$Form->text( 'aggregate_coll_IDs', $edited_Blog->get_setting( 'aggregate_coll_IDs' ), 30, T_('Collections to aggregate'), T_('List collection IDs separated by \',\', \'*\' for all collections or leave empty for current collection.'), 255 );
	$Form->end_fieldset();


	$Form->begin_fieldset( T_('Caching').' ['.T_('Admin').']'.get_manual_link('collection_cache_settings'), array( 'id' => 'caching' ) );
		$ajax_enabled = $edited_Blog->get_setting( 'ajax_form_enabled' );
		$ajax_loggedin_params = array( 'note' => T_('Also use JS forms for logged in users') );
		if( !$ajax_enabled )
		{
			$ajax_loggedin_params[ 'disabled' ] = 'disabled';
		}
		$Form->checkbox_input( 'ajax_form_enabled', $ajax_enabled, T_('Enable AJAX forms'), array( 'note'=>T_('Comment and contacts forms will be fetched by javascript') ) );
		$Form->checkbox_input( 'ajax_form_loggedin_enabled', $edited_Blog->get_setting('ajax_form_loggedin_enabled'), '', $ajax_loggedin_params );
		$Form->checkbox_input( 'cache_enabled', $edited_Blog->get_setting('cache_enabled'), get_icon( 'page_cache_on' ).' '.T_('Enable page cache'), array( 'note'=>T_('Cache rendered blog pages') ) );
		$Form->checkbox_input( 'cache_enabled_widgets', $edited_Blog->get_setting('cache_enabled_widgets'), get_icon( 'block_cache_on' ).' '.T_('Enable widget/block cache'), array( 'note'=>T_('Cache rendered widgets') ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('In-skin Actions').' ['.T_('Admin').']'.get_manual_link('in_skin_action_settings') );
		if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) )
		{ // The login blog is defined in general settings
			$Form->info( T_( 'In-skin login' ), sprintf( T_('All login/registration functions are delegated to the collection: %s'), '<a href="'.$admin_url.'?ctrl=collections&tab=site_settings">'.$login_Blog->get( 'shortname' ).'</a>' ) );
		}
		else
		{ // Allow to select in-skin login for this blog
			$Form->checkbox_input( 'in_skin_login', $edited_Blog->get_setting( 'in_skin_login' ), T_( 'In-skin login' ), array( 'note' => T_( 'Use in-skin login form every time it\'s possible' ) ) );
		}
		$Form->checkbox_input( 'in_skin_editing', $edited_Blog->get_setting( 'in_skin_editing' ), T_( 'In-skin editing' ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Media directory location').' ['.T_('Admin').']'.get_manual_link('media_directory_location') );
	global $media_path;
	$Form->radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
			array(
				array( 'none', T_('None') ),
				array( 'default', T_('Default'), $media_path.'blogs/'.$edited_Blog->urlname.'/' ),
				array( 'subdir', T_('Subdirectory of media folder').':',
					'',
					' <span class="nobr"><code>'.$media_path.'</code><input
						type="text" name="blog_media_subdir" class="form_text_input form-control" size="20" maxlength="255"
						class="'.( param_has_error('blog_media_subdir') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
				array( 'custom',
					T_('Custom location').':',
					'',
					'<fieldset class="form-group">'
					.'<div class="label control-label col-lg-2">'.T_('directory').':</div><div class="input controls col-xs-8"><input
						type="text" class="form_text_input form-control" name="blog_media_fullpath" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_fullpath') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
					.'<div class="clear"></div>'
					.'<div class="label control-label col-lg-2">'.T_('URL').':</div><div class="input controls col-xs-8"><input
						type="text" class="form_text_input form-control" name="blog_media_url" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_url') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
			), T_('Media directory'), true
		);
	$Form->end_fieldset();

}

$Form->begin_fieldset( T_('Meta data').get_manual_link('blog_meta_data') );
	// TODO: move stuff to coll_settings
	$Form->text( 'blog_shortdesc', $edited_Blog->get( 'shortdesc' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );
	$Form->text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );
	$Form->text( 'blog_footer_text', $edited_Blog->get_setting( 'blog_footer_text' ), 60, T_('Blog footer'), sprintf(
		T_('Use &lt;br /&gt; to insert a line break. You might want to put your copyright or <a href="%s" target="_blank">creative commons</a> notice here.'),
		'http://creativecommons.org/license/' ), 1000, 'large' );
	$Form->textarea( 'single_item_footer_text', $edited_Blog->get_setting( 'single_item_footer_text' ), 2, T_('Single post footer'),
		T_('This will be displayed after each post in single post view.').' '.sprintf( T_('Available variables: %s.'), '<b>$perm_url$</b>, <b>$title$</b>, <b>$excerpt$</b>, <b>$author$</b>, <b>$author_login$</b>' ), 50 );
	$Form->textarea( 'xml_item_footer_text', $edited_Blog->get_setting( 'xml_item_footer_text' ), 2, T_('Post footer in RSS/Atom'),
		T_('This will be appended to each post in your RSS/Atom feeds.').' '.sprintf( T_('Available variables: %s.'), T_('same as above') ), 50 );
	$Form->textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 5, T_('Notes'),
		T_('Additional info. Appears in the backoffice.'), 50 );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Software credits').get_manual_link('software_credits') );
	$max_credits = $edited_Blog->get_setting( 'max_footer_credits' );
	$note = T_('You get the b2evolution software for <strong>free</strong>. We do appreciate you giving us credit. <strong>Thank you for your support!</strong>');
	if( $max_credits < 1 )
	{
		$note = '<img src="'.$rsc_url.'smilies/icon_sad.gif" alt="" class="bottom"> '.$note;
	}
	$Form->text( 'max_footer_credits', $max_credits, 1, T_('Max footer credits'), $note, 1 );
$Form->end_fieldset();


if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Skin and style').' ['.T_('Admin').']' );
		$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), T_('Allow customized blog CSS file'), T_('You will be able to customize the blog\'s skin stylesheet with a file named style.css in the blog\'s media file folder.') );
		$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), T_('Allow user customized CSS file for this blog'), T_('Users will be able to customize the blog and skin stylesheets with a file named style.css in their personal file folder.') );
		$Form->textarea( 'blog_head_includes', $edited_Blog->get_setting( 'head_includes' ), 5, T_('Custom meta tag/css section (before &lt;/head&gt;)'),
			T_('Add custom meta tags and/or css styles to the &lt;head&gt; section. Example use: website verification, Google+, favicon image...'), 50 );
		$Form->textarea( 'blog_footer_includes', $edited_Blog->get_setting( 'footer_includes' ), 5, T_('Custom javascript section (before &lt;/body&gt;)'),
			T_('Add custom javascript before the closing &lt;/body&gt; tag in order to avoid any issues with page loading delays for visitors with slow connection speeds.<br />Example use: tracking scripts, javascript libraries...'), 50 );
	$Form->end_fieldset();

}


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>

<script type="text/javascript">
	jQuery( '#ajax_form_enabled' ).click( function()
	{
		if( jQuery( '#ajax_form_enabled' ).attr( "checked" ) )
		{
			jQuery( '#ajax_form_loggedin_enabled' ).attr( "disabled", false );
		}
		else
		{
			jQuery( '#cache_enabled' ).attr( "checked", false );
			jQuery( '#ajax_form_loggedin_enabled' ).attr( "disabled", true );
		}
	} );
	jQuery( '#cache_enabled' ).click( function()
	{
		if( jQuery( '#cache_enabled' ).attr( "checked" ) )
		{
			jQuery( '#ajax_form_enabled' ).attr( "checked", true );
			jQuery( '#ajax_form_loggedin_enabled' ).attr( "disabled", false );
		}
	} );
	jQuery( '#advanced_perms' ).click( function()
	{
		if( ! jQuery( this ).is( ':checked' ) && jQuery( 'input[name=blog_allow_access][value=members]' ).is( ':checked' ) )
		{
			jQuery( 'input[name=blog_allow_access][value=users]' ).attr( 'checked', true );
		}
	} );
	jQuery( 'input[name=blog_allow_access][value=members]' ).click( function()
	{
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#advanced_perms' ).attr( 'checked', true );
		}
	} );
</script>