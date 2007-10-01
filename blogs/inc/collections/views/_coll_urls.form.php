<?php
/**
 * This file implements the UI view for the collection URL properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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

?>
<script type="text/javascript">
	<!--
	// Script to update the Blog URL preview:
	var blog_baseurl = '<?php echo str_replace( "'", "\'", $edited_Blog->gen_baseurl() ); ?>';

	function update_urlpreview( baseurl )
	{
		if( typeof baseurl == 'string' )
		{
			blog_baseurl = baseurl;
		}

		if( document.getElementById( 'urlpreview' ).hasChildNodes() )
		{
			document.getElementById( 'urlpreview' ).firstChild.data = blog_baseurl;
		}
		else
		{
			document.getElementById( 'urlpreview' ).appendChild( document.createTextNode( blog_baseurl ) );
		}
	}

	function show_hide_chapter_prefix(ob){
		var fldset = document.getElementById( 'category_prefix_container' );
		if( ob.value == 'param_num' )
		{
			fldset.style.display = 'none';
			var category_prefix_ob = document.getElementById( 'category_prefix' );
			category_prefix_ob.value = '';
		}
		else
		{
			fldset.style.display = '';
		}
	}
	//-->
</script>


<?php

global $blog, $tab;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );


global $baseurl, $basedomain;

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

$Form->begin_fieldset( T_('Blog URL').' ['.T_('Admin').']' );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('Blog URL name'), T_('Used to uniquely identify this blog. Appears in URLs and gets used as default for the media location (see the advanced tab).'), 255 );

		if( $default_blog_ID = $Settings->get('default_blog_ID') )
		{
			$Debuglog->add('Default blog is set to: '.$default_blog_ID);
			$BlogCache = & get_Cache( 'BlogCache' );
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


		$Form->radio( 'blog_access_type', $edited_Blog->get( 'access_type' ), array(
			array( 'default', T_('Default blog in index.php'),
											'('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ T_('No default blog is currently set')
												: /* TRANS: current default blog */ T_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'index.php\' );"'
			),
			array( 'index.php', T_('Explicit param on index.php'),
										'index.php?blog=123',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'index.php?blog='.$edited_Blog->ID.'\' )"',
			),
			array( 'extrapath', T_('Extra path on index.php'),
										'index.php/url_name',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'index.php/\'+document.getElementById( \'blog_urlname\' ).value )"'
			),
			array( 'relative', T_('Relative to baseurl').':',
										'',
										'<span class="nobr"><code>'.$baseurl.'</code>'
										.'<input type="text" id="blog_siteurl_relative" name="blog_siteurl_relative" size="35" maxlength="120" value="'
										.format_to_output( $blog_siteurl_relative, 'formvalue' )
										.'" onkeyup="update_urlpreview( \''.$baseurl.'\'+this.value );"
										onfocus="document.getElementsByName(\'blog_access_type\')[3].checked=true;
										update_urlpreview( \''.$baseurl.'\'+this.value );" /></span>'.$siteurl_relative_warning,
										'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();"'
			),
			array( 'subdom', T_('Subdomain of basedomain'),
										'http://url_name.'.$basedomain.'/',
										'',
										'onclick="update_urlpreview( \'http://\'+document.getElementById( \'blog_urlname\' ).value+\'.'.$basedomain.'/\' )"'
			),
			array( 'absolute', T_('Absolute URL').':',
										'',
										'<input type="text" id="blog_siteurl_absolute" name="blog_siteurl_absolute" size="50" maxlength="120" value="'
											.format_to_output( $blog_siteurl_absolute, 'formvalue' )
											.'" onkeyup="update_urlpreview( this.value );"
											onfocus="document.getElementsByName(\'blog_access_type\')[5].checked=true;
											update_urlpreview( this.value );" />'.$siteurl_absolute_warning,
										'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();"'
			),
		), T_('Blog base URL'), true );

	}

	// URL Preview 'always displayed)
	$blogurl = $edited_Blog->gen_blogurl();
	$Form->info( T_('URL preview'), '<span id="urlpreview">'.$blogurl.'</span>' );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Archive URLs') );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', T_('Use param'), T_('Archive links will look like ')
								.url_add_param( $blogurl, 'm=20071231' ) ),
				array( 'extrapath', T_('Use extra-path'), T_('Archive links will look like ' )
								.url_add_tail( $blogurl, '/2007/12/31/' ) ),
			), T_('Archive links'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Category URLs') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', T_('Use param: cat ID'), T_('Category links will look like ')
								.url_add_param( $blogurl, 'cat=123' ),'', 'onclick="show_hide_chapter_prefix(this);"'),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('Category links will look like ' )
								.url_add_tail( $blogurl, '/subcat/' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
				array( 'chapters', T_('Use extra-path: category path'), T_('Category links will look like ' )
								.url_add_tail( $blogurl, '/cat/subcat/' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
			), T_('Category links'), true );

$show_prefix = ($edited_Blog->get_setting('chapter_links') != 'param_num') ? true : false ;

if ($show_prefix)
{
	$style_container_prefix = '';
}
else
{
	$style_container_prefix = 'style="display:none"';
}

echo ('<div id="category_prefix_container"' . $style_container_prefix . '>');
	$Form->text_input( 'category_prefix', $edited_Blog->get_setting( 'category_prefix' ), 30, T_('Prefix'),
												T_('A optional prefix to be added to the URLs of the categories'),
												array('maxlength' => 120) );
echo('</div>');

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post URLs') );

	$Form->radio( 'single_links', $edited_Blog->get_setting('single_links'),
		array(
			  array( 'param_num', T_('Use param: post ID'), T_('Links will look like: \'stub?p=123&amp;c=1&amp;tb=1&amp;pb=1&amp;more=1\'') ),
			  array( 'param_title', T_('Use param: post title'), T_('Links will look like: \'stub?title=post-title&amp;c=1&amp;tb=1&amp;pb=1&amp;more=1\'') ),
				array( 'short', T_('Use extra-path: post title'), T_('Links will look like \'stub/post-title\'' ) ),
				array( 'y', T_('Use extra-path: year'), T_('Links will look like \'stub/2006/post-title\'' ) ),
				array( 'ym', T_('Use extra-path: year & month'), T_('Links will look like \'stub/2006/12/post-title\'' ) ),
				array( 'ymd', T_('Use extra-path: year, month & day'), T_('Links will look like \'stub/2006/12/31/post-title\'' ) ),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('Links will look like \'stub/subcat/post-title\'' ) ),
				array( 'chapters', T_('Use extra-path: category path'), T_('Links will look like \'stub/cat/subcat/post-title\'' ) ),
			), T_('Single post links'), true,
			T_('For example, single post links are used when viewing comments for a post. May be used for permalinks - see below.') );
			// fp> TODO: check where we really need to force single and where we could use any permalink

	$Form->radio( 'permalinks', $edited_Blog->get_setting('permalinks'),
		array(
			  array( 'single', T_('Link to single post') ),
			  array( 'archive', T_('Link to post in archive') ),
			  array( 'subchap', T_('Link to post in sub-category') ),
			), T_('Post permalinks'), true );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.4  2007/10/01 13:41:07  waltercruz
 * Category prefix, trying to make the code more b2evo style
 *
 * Revision 1.3  2007/09/29 01:50:50  fplanque
 * temporary rollback; waiting for new version
 *
 * Revision 1.1  2007/06/25 10:59:38  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.9  2007/05/28 15:18:30  fplanque
 * cleanup
 *
 * Revision 1.8  2007/05/28 01:35:23  fplanque
 * fixed static page generation
 *
 * Revision 1.7  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/03/25 15:07:38  fplanque
 * multiblog fixes
 *
 * Revision 1.5  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.4  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.3  2007/01/23 08:06:25  fplanque
 * Simplified!!!
 *
 * Revision 1.2  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 */
?>