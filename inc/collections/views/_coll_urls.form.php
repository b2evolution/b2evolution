<?php
/**
 * This file implements the UI view for the collection URL properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var Log
 */
global $Debuglog;

global $admin_url;

?>
<script type="text/javascript">
	function show_hide_chapter_prefix(ob)
	{
		var fldset = document.getElementById( 'category_prefix_container' );
		if( ob.value == 'param_num' )
		{
			fldset.style.display = 'none';
		}
		else
		{
			fldset.style.display = '';
		}
	}
</script>


<?php

global $blog, $tab;

global $preset;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );


global $baseurl, $baseprotocol, $basehost, $baseport;

// determine siteurl type (if not set from update-action)
if( preg_match('#https?://#', $edited_Blog->get( 'siteurl' ) ) )
{ // absolute
	$blog_siteurl_relative = '';
	$blog_siteurl_absolute = $edited_Blog->get( 'siteurl' );
}
else
{ // relative
	$blog_siteurl_relative = $edited_Blog->get( 'siteurl' );
	$blog_siteurl_absolute = 'http://';
}

$Form->begin_fieldset( T_('Collection base URL').get_admin_badge().get_manual_link('collection-base-url-settings') );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('Collection URL name'), T_('Used to uniquely identify this collection. Appears in URLs and gets used as default for the media location (see the advanced tab).'), 255 );

		if( $default_blog_ID = $Settings->get('default_blog_ID') )
		{
			$Debuglog->add('Default collection is set to: '.$default_blog_ID);
			$BlogCache = & get_BlogCache();
			if( $default_Blog = & $BlogCache->get_by_ID($default_blog_ID, false) )
			{ // Default blog exists
				$defblog = $default_Blog->dget('shortname');
			}
		}

		$siteurl_relative_warning = '';
		if( ! preg_match( '~(^|/|\.php.?)$~i', $blog_siteurl_relative ) )
		{
			$siteurl_relative_warning = ' <span class="note red">'.T_('WARNING: it is highly recommended that this ends in with a / or .php !').'</span>';
		}

		$siteurl_absolute_warning = '';
		if( ! preg_match( '~(^|/|\.php.?)$~i', $blog_siteurl_absolute ) )
		{
			$siteurl_absolute_warning = ' <span class="note red">'.T_('WARNING: it is highly recommended that this ends in with a / or .php !').'</span>';
		}


		$access_type_options = array(
			/* TODO: Tblue> This option only should be available if the
			 *              current blog is set as the default blog, otherwise
			 *              this setting is confusing. Another possible
			 *              solution would be to change the default blog
			 *              setting if this blog-specific setting is changed,
			 *              but then we would be have the same setting in
			 *              two places... I would be in favor of the first
			 *              solution.
			 * fp> I think it should actually change the default blog setting because
			 * people have a hard time finding the settings. I personally couldn't care
			 * less that there are 2 ways to do the same thing.
			 */
			array( 'baseurl', T_('Default collection on baseurl'),
											$baseurl.' ('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ T_('No default collection is currently set')
												: /* TRANS: current default blog */ T_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php\' );"'
			),
			array( 'default', T_('Default collection in index.php'),
											$baseurl.'index.php ('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ T_('No default collection is currently set')
												: /* TRANS: current default blog */ T_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php\' );"'
			),
			array( 'index.php', T_('Explicit param on index.php'),
										$baseurl.'index.php?blog='.$edited_Blog->ID,
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php?blog='.$edited_Blog->ID.'\' )"',
			),
			array( 'extrabase', T_('Extra path on baseurl'),
										$baseurl.'<span class="blog_url_text">'.$edited_Blog->get( 'urlname' ).'</span>/ ('.T_('Requires mod_rewrite').')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', document.getElementById( \'blog_urlname\' ).value+\'/\' )"'
			),
			array( 'extrapath', T_('Extra path on index.php'),
										$baseurl.'index.php/<span class="blog_url_text">'.$edited_Blog->get( 'urlname' ).'</span>/',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php/\'+document.getElementById( \'blog_urlname\' ).value+\'/\' )"'
			),
			array( 'relative', T_('Relative to baseurl').':',
										'',
										'<span class="nobr"><code>'.$baseurl.'</code>'
										.'<input type="text" id="blog_siteurl_relative" class="form_text_input form-control" name="blog_siteurl_relative" size="35" maxlength="120" value="'
										.format_to_output( $blog_siteurl_relative, 'formvalue' )
										.'" onkeyup="update_urlpreview( \''.$baseurl.'\', this.value );"
										onfocus="document.getElementsByName(\'blog_access_type\')[5].checked=true;
										update_urlpreview( \''.$baseurl.'\', this.value );" /></span>'.$siteurl_relative_warning,
										'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();"'
			)
		);
		if( ! is_valid_ip_format( $basehost ) )
		{// Not an IP address, we can use subdomains:
			$access_type_options[] = array( 'subdom', T_('Subdomain of basehost'),
										$baseprotocol.'://<span class="blog_url_text">'.$edited_Blog->urlname.'</span>.'.$basehost.$baseport.'/',
										'',
										'onclick="update_urlpreview( \''.$baseprotocol.'://\'+document.getElementById( \'blog_urlname\' ).value+\'.'.$basehost.$baseport.'/\' )"'
			);
		}
		else
		{ // Don't allow subdomain for IP address:
			$access_type_options[] = array( 'subdom', T_('Subdomain').':',
										sprintf( T_('(Not possible for %s)'), $basehost ),
										'',
										'disabled="disabled"'
			);
		}
		$access_type_options[] = array( 'absolute', T_('Absolute URL').':',
										'',
										'<input type="text" id="blog_siteurl_absolute" class="form_text_input form-control" name="blog_siteurl_absolute" size="50" maxlength="120" value="'
											.format_to_output( $blog_siteurl_absolute, 'formvalue' )
											.'" onkeyup="update_urlpreview( this.value );"
											onfocus="document.getElementsByName(\'blog_access_type\')[7].checked=true;
											update_urlpreview( this.value );" />'.$siteurl_absolute_warning,
										'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();"'
		);

		$Form->radio( 'blog_access_type', $edited_Blog->get( 'access_type' ), $access_type_options, T_('Collection base URL'), true );

?>
<script type="text/javascript">
// Script to update the Blog URL preview:
function update_urlpreview( baseurl, url_path )
{
	if( typeof( url_path ) != 'string' )
	{
		url_path = '';
	}
	if( ! baseurl.match( /\/[^\/]+\.[^\/]+\/$/ ) )
	{
		baseurl = baseurl.replace( /\/$/, '' ) + '/';
	}
	jQuery( '#urlpreview' ).html( baseurl + url_path );

	var basepath = baseurl.replace( /^(.+\/)([^\/]+\.[^\/]+)?$/, '$1' );
	basepath = basepath.replace( /^(https?:\/\/(.+?)(:.+?)?)\//i, '/' );

	jQuery( '#media_assets_url_type_relative' ).html( baseurl + 'media/' );
	jQuery( '#rsc_assets_url_type_relative' ).html( basepath + 'rsc/' );
	jQuery( '#skins_assets_url_type_relative' ).html( basepath + 'skins/' );
	jQuery( '#plugins_assets_url_type_relative' ).html( basepath + 'plugins/' );
}

// Update blog url name in several places on the page:
jQuery( '#blog_urlname' ).bind( 'keyup blur', function()
{
	jQuery( '.blog_url_text' ).html( jQuery( this ).val() );
	var blog_access_type_obj = jQuery( 'input[name=blog_access_type]:checked' );
	if( blog_access_type_obj.length > 0 &&
	    ( blog_access_type_obj.val() == 'extrabase' || blog_access_type_obj.val() == 'extrapath' || blog_access_type_obj.val() == 'subdom' ) )
	{
		blog_access_type_obj.click();
	}
} );

// Select 'absolute' option when cursor is focused on input element
jQuery( '[id$=_assets_absolute_url]' ).focus( function()
{
	var radio_field_name = jQuery( this ).attr( 'id' ).replace( '_absolute_url', '_url_type' );
	jQuery( '[name=' + radio_field_name + ']' ).attr( 'checked', 'checked' );
} );
</script>
<?php

	}

	// URL Preview (always displayed)
	$blogurl = $edited_Blog->gen_blogurl();
	$Form->info( T_('URL preview'), '<span id="urlpreview">'.$blogurl.'</span>' );

	$http_protocol_options = array(
		array( 'always_redirect', T_('Always redirect to URL above') ),
		array( 'allow_both', sprintf( T_('Allow both %s and %s as valid URLs'), '<code>http</code>', '<code>https</code>' ) ) );

		$Form->radio( 'blog_http_protocol', $edited_Blog->get( 'http_protocol' ), $http_protocol_options, T_('SSL'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Cookie Settings').get_admin_badge().get_manual_link( 'collection-cookie-settings' ) );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// If current user has a permission to edit collection advanced admin settings:
		$Form->switch_layout( 'none' );
		$Form->output = false;
		$cookie_domain_custom_field = $Form->text( 'cookie_domain_custom', $edited_Blog->get_setting( 'cookie_domain_custom' ), 50, '', '', 120 );
		$cookie_path_custom_field = $Form->text( 'cookie_path_custom', $edited_Blog->get_setting( 'cookie_path_custom' ), 50, '', '', 120 );
		$Form->output = true;
		$Form->switch_layout( NULL );

		$Form->radio( 'cookie_domain_type', $edited_Blog->get_setting( 'cookie_domain_type' ), array(
				array( 'auto', T_('Automatic'), $edited_Blog->get_cookie_domain( 'auto' ) ),
				array( 'custom', T_('Custom').':', '', $cookie_domain_custom_field ),
			), T_('Cookie domain'), true );

		$Form->radio( 'cookie_path_type', $edited_Blog->get_setting( 'cookie_path_type' ), array(
				array( 'auto', T_('Automatic'), $edited_Blog->get_cookie_path( 'auto' ) ),
				array( 'custom', T_('Custom').':', '', $cookie_path_custom_field ),
			), T_('Cookie path'), true );
	}
	else
	{	// Display only info about colleciton cookie domain and path if user has no permission to edit:
		$Form->info( T_('Cookie domain'), $edited_Blog->get_cookie_domain() );
		$Form->info( T_('Cookie path'), $edited_Blog->get_cookie_path() );
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Assets URLs / CDN support').get_admin_badge().get_manual_link( 'assets-url-cdn-settings' ) );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		global $rsc_url, $media_url, $skins_url, $plugins_url;

		$absolute_url_note = T_('Enter path to %s folder ending with / -- This may be located in a CDN zone');
		$assets_url_data = array();
		// media url:
		$assets_url_data['media_assets_url_type'] = array(
				'label'        => sprintf( T_('Load %s assets from'), '<code>/media/</code>' )
			);
		if( $edited_Blog->get( 'media_location' ) == 'none' )
		{ // if media location is disabled
			$assets_url_data['media_assets_url_type']['info'] = sprintf( T_('The media directory is <a %s>turned off</a> for this collection'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$edited_Blog->ID.'#media_dir_location"' );
		}
		elseif( $edited_Blog->get( 'media_location' ) == 'custom' )
		{ // if media location is customized
			$assets_url_data['media_assets_url_type']['info'] = sprintf( T_('A custom location has already been set in the <a %s>advanced properties</a>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$edited_Blog->ID.'"' );
		}
		else
		{
			$assets_url_data['media_assets_url_type'] += array(
					'url'          => $media_url,
					'absolute_url' => 'media_assets_absolute_url',
					'folder'       => '/media/',
					'local_url'    => $edited_Blog->get_local_media_url( 'relative', true )
				);
		}
		// skins url:
		$assets_url_data['skins_assets_url_type'] = array(
				'label'        => sprintf( T_('Load %s assets from'), '<code>/skins/</code>' ),
				'url'          => $skins_url,
				'absolute_url' => 'skins_assets_absolute_url',
				'folder'       => '/skins/',
				'local_url'    => $edited_Blog->get_local_skins_url( 'relative' )
			);
		// rsc url:
		$assets_url_data['rsc_assets_url_type'] = array(
				'label'        => sprintf( T_('Load generic %s assets from'), '<code>/rsc/</code>' ),
				'url'          => $rsc_url,
				'absolute_url' => 'rsc_assets_absolute_url',
				'folder'       => '/rsc/',
				'local_url'    => $edited_Blog->get_local_rsc_url( 'relative' )
			);
		// plugins url:
		$assets_url_data['plugins_assets_url_type'] = array(
				'label'        => sprintf( T_('Load %s assets from'), '<code>/plugins/</code>' ),
				'url'          => $plugins_url,
				'absolute_url' => 'plugins_assets_absolute_url',
				'folder'       => '/plugins/',
				'local_url'    => $edited_Blog->get_local_plugins_url( 'relative' )
			);

		foreach( $assets_url_data as $asset_url_type => $asset_url_data )
		{
			if( isset( $asset_url_data['info'] ) )
			{ // Display only info for this url type
				$Form->info( $asset_url_data['label'], $asset_url_data['info'] );
			}
			else
			{ // Display options full list
				$basic_asset_url_note = $asset_url_data['url'];
				if( $asset_url_type != 'media_assets_url_type' &&
				    $edited_Blog->get( 'access_type' ) == 'absolute' &&
				    $edited_Blog->get_setting( $asset_url_type ) == 'basic' )
				{
					$basic_asset_url_note .= ' <span class="red">'
							.sprintf( T_('ATTENTION: Using a different domain for your collection and your %s folder may cause problems'), '<code>'.$asset_url_data['folder'].'</code>' )
							.' ('.( $asset_url_type == 'plugins_assets_url_type' ? T_('e-g: Ajax requests') : T_('e-g: impossible to load fonts') ).')'
						.'</span>';
				}

				$relative_asset_url_note = '<span id="'.$asset_url_type.'_relative">'.$asset_url_data['local_url'].'</span>';
				if( $asset_url_type != 'skins_assets_url_type' && $asset_url_type != 'media_assets_url_type' &&
				    $edited_Blog->get_setting( 'skins_assets_url_type' ) != 'relative' &&
				    $edited_Blog->get_setting( $asset_url_type ) == 'relative' )
				{
					$relative_asset_url_note .= ' <span class="red">'
							.sprintf( T_('ATTENTION: using a relative %s folder with a non-relative %s folder will probably lead to undesired results (because of the skin\'s &lt;baseurl&gt;).'), '<code>'.$asset_url_data['folder'].'</code>', '<code>/skins/</code>' )
						.'</span>';
				}

				$Form->radio( $asset_url_type, $edited_Blog->get_setting( $asset_url_type ), array(
					array( 'relative', (
							( $asset_url_type == 'skins_assets_url_type' || $asset_url_type == 'media_assets_url_type'  ) ?
							sprintf( T_('%s folder relative to current collection (recommended setting)'), '<code>'.$asset_url_data['folder'].'</code>' ) :
							sprintf( T_('%s folder relative to %s domain (recommended setting)'), '<code>'.$asset_url_data['folder'].'</code>', '<code>/skins/</code>' )
						), $relative_asset_url_note ),
					array( 'basic', T_('URL configured in Basic Config'), $basic_asset_url_note ),
					array( 'absolute', T_('Absolute URL').':', '',
						'<input type="text" id="'.$asset_url_data['absolute_url'].'" class="form_text_input form-control" name="'.$asset_url_data['absolute_url'].'"
						size="50" maxlength="120" onfocus="document.getElementsByName(\''.$asset_url_type.'\')[2].checked=true;" value="'.$edited_Blog->get_setting( $asset_url_data['absolute_url'] ).'" />
						<span class="notes">'.sprintf( $absolute_url_note, '<code>'.$asset_url_data['folder'].'</code>' ).'</span>'
					)
				), $asset_url_data['label'], true );
			}
		}
	}
	else
	{	// Preview assets urls:
		$Form->info( sprintf( T_('Load %s assets from'), '<code>/media/</code>' ), $edited_Blog->get_local_media_url() );
		$Form->info( sprintf( T_('Load %s assets from'), '<code>/skins/</code>' ), $edited_Blog->get_local_skins_url() );
		$Form->info( sprintf( T_('Load generic %s assets from'), '<code>/rsc/</code>' ), $edited_Blog->get_local_rsc_url() );
		$Form->info( sprintf( T_('Load %s assets from'), '<code>/plugins/</code>' ), $edited_Blog->get_local_plugins_url() );
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Date archive URLs').get_manual_link('date-archive-url-settings')  );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', T_('Use param'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>m=20071231</strong>' ) ),
				array( 'extrapath', T_('Use extra-path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2007/12/31/</strong>' ) ),
			), T_('Date archive URLs'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Category URLs') . get_manual_link('category-url-settings') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', T_('Use param: cat ID'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>cat=123</strong>' ),'', 'onclick="show_hide_chapter_prefix(this);"'),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
				array( 'chapters', T_('Use extra-path: category path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
			), T_('Category URLs'), true );


		echo '<div id="category_prefix_container">';
			$Form->text_input( 'category_prefix', $edited_Blog->get_setting( 'category_prefix' ), 30, T_('Prefix'),
														T_('An optional prefix to be added to the URLs of the categories'),
														array('maxlength' => 120) );
		echo '</div>';

		if( $edited_Blog->get_setting( 'chapter_links' ) == 'param_num' )
		{ ?>
		<script type="text/javascript">
			<!--
			var fldset = document.getElementById( 'category_prefix_container' );
			fldset.style.display = 'none';
			//-->
		</script>
		<?php
		}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Tag page URLs') . get_manual_link('tag-page-url-settings'), array('id'=>'tag_links_fieldset') );

	$Form->radio( 'tag_links', $edited_Blog->get_setting('tag_links'),
		array(
			array( 'param', T_('Use param'), T_('E-g: ')
				.url_add_param( $blogurl, '<strong>tag=mytag</strong>' ) ),
			array( 'prefix-only', T_('Use extra-path').': '.'Use URL path prefix only (recommended)', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag</strong>' ) ),
			array( 'dash', T_('Use extra-path').': '.'trailing dash', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag-</strong>' ) ),
			array( 'colon', T_('Use extra-path').': '.'trailing colon', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag:</strong>' ) ),
			array( 'semicolon', T_('Use extra-path').': '.'trailing semi-colon (NOT recommended)', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag;</strong>' ) ),
		), T_('Tag page URLs'), true );


	$Form->text_input( 'tag_prefix', $edited_Blog->get_setting( 'tag_prefix' ), 30, T_('Prefix'),
		T_('An optional prefix to be added to the URLs of the tag pages'),
		array('maxlength' => 120) );

	$Form->checkbox( 'tag_rel_attrib', $edited_Blog->get_setting( 'tag_rel_attrib' ), T_('Rel attribute'),
		sprintf( T_('Add <a %s>rel="tag" attribute</a> to tag links.'), 'href="http://microformats.org/wiki/rel-tag"' ) );

$Form->end_fieldset();

// Javascript juice for the tag fields.
?>
<script type="text/javascript">
jQuery( '#tag_links_fieldset input[type=radio]' ).click( function()
{
	if( jQuery( this ).val() == 'param' )
	{ // Disable tag_prefix, if "param" is used.
		jQuery( '#tag_prefix' ).attr( 'disabled', 'disabled' );
	}
	else
	{
		jQuery( '#tag_prefix' ).removeAttr( 'disabled' );
	}

	if( jQuery( this ).val() == 'prefix-only' )
	{ // Enable tag_rel_attrib, if "prefix-only" is used.
		jQuery( '#tag_rel_attrib' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( '#tag_rel_attrib' ).attr( 'disabled', 'disabled' );
	}

	// NOTE: dh> ".closest('fieldset').andSelf()" is required for the add-field_required-class-to-fieldset-hack. Remove as appropriate.
	if( jQuery( this ).val() == 'prefix-only' )
	{
		jQuery( '#tag_prefix' ).closest( 'fieldset' ).andSelf().addClass( 'field_required' );
	}
	else
	{
		jQuery( '#tag_prefix' ).closest( 'fieldset' ).andSelf().removeClass( 'field_required' );
	}
} ).filter( ':checked' ).click();

// Set text of span.tag_links_tag_prefix according to this field, defaulting to "tag" for "prefix-only".
jQuery("#tag_prefix").keyup( function() {
	jQuery("span.tag_links_tag_prefix").each(
		function() {
			var newval = ((jQuery("#tag_prefix").val().length || jQuery(this).closest("div").find("input[type=radio]").attr("value") != "prefix-only") ? jQuery("#tag_prefix").val() : "tag");
			if( newval.length ) newval += "/";
			jQuery(this).text( newval );
		}
	) } ).keyup();
</script>


<?php
$Form->begin_fieldset( T_('Single post URLs') . get_manual_link('single-post-url-settings') );

	$Form->radio( 'single_links', $edited_Blog->get_setting('single_links'),
		array(
			  array( 'param_num', T_('Use param: post ID'), T_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>p=123&amp;more=1</strong>' ) ),
			  array( 'param_title', T_('Use param: post title'), T_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>title=post-title&amp;more=1</strong>' ) ),
				array( 'short', T_('Use extra-path: post title'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/post-title</strong>' ) ),
				array( 'y', T_('Use extra-path: year'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/post-title</strong>' ) ),
				array( 'ym', T_('Use extra-path: year & month'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/post-title</strong>' ) ),
				array( 'ymd', T_('Use extra-path: year, month & day'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/31/post-title</strong>' ) ),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/post-title</strong>' ) ),
				array( 'chapters', T_('Use extra-path: category path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/post-title</strong>' ) ),
			), T_('Single post URLs'), true );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>