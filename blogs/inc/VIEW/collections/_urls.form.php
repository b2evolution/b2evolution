<?php
/**
 * This file implements the UI view for the collection URL properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

// Prepare last part of blog URL preview:
switch( $edited_Blog->get( 'access_type' ) )
{
	case 'default':
		$blog_urlappend = 'index.php';
		break;

	case 'index.php':
		if( $Settings->get('links_extrapath') == 'disabled' )
		{
			$blog_urlappend = 'index.php?blog='.$edited_Blog->ID;
		}
		else
		{	// fp>> TODO: This seems odd, shouldn't it be the URL name here??
			$blog_urlappend = 'index.php/'.$edited_Blog->get( 'stub' );
		}
		break;

	case 'stub':
		$blog_urlappend = $edited_Blog->get( 'stub' );
		break;
}

?>
<script type="text/javascript">
	<!--
	var blog_baseurl = '<?php $edited_Blog->disp( 'baseurl', 'formvalue' ); ?>';
	var blog_urlappend = '<?php echo str_replace( "'", "\'", $blog_urlappend ) ?>';

	function update_urlpreview( base, append )
	{
		if( typeof base == 'string' ){ blog_baseurl = base; }
		if( typeof append == 'string' ){ blog_urlappend = append; }

		text = blog_baseurl + blog_urlappend;

		if( document.getElementById( 'urlpreview' ).hasChildNodes() )
		{
			document.getElementById( 'urlpreview' ).firstChild.data = text;
		}
		else
		{
			document.getElementById( 'urlpreview' ).appendChild( document.createTextNode( text ) );
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


global $baseurl, $maxlength_urlname_stub;

// determine siteurl type (if not set from update-action)
if( preg_match('#https?://#', $edited_Blog->get( 'siteurl' ) ) )
{ // absolute
	$blog_siteurl_type = 'absolute';
	$blog_siteurl_relative = '';
	$blog_siteurl_absolute = $edited_Blog->get( 'siteurl' );
}
else
{ // relative
	$blog_siteurl_type = 'relative';
	$blog_siteurl_relative = $edited_Blog->get( 'siteurl' );
	$blog_siteurl_absolute = 'http://';
}

$Form->begin_fieldset( T_('Blog URL') );

	$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('Blog URL name'), T_('Used to uniquely identify this blog. Appears in URLs and gets used as default for the media location (see the advanced tab).'), $maxlength_urlname_stub );

	// TODO: we should have an extra DB column that either defines type of blog_siteurl OR split blog_siteurl into blog_siteurl_abs and blog_siteurl_rel (where blog_siteurl_rel could be "blog_sitepath")
	$Form->radio( 'blog_siteurl_type', $blog_siteurl_type,
		array(
			array( 'relative',
							T_('Relative to baseurl').':',
							'',
							'<span class="nobr"><code>'.$baseurl.'</code>'
							.'<input type="text" id="blog_siteurl_relative" name="blog_siteurl_relative" size="30" maxlength="120" value="'.format_to_output( $blog_siteurl_relative, 'formvalue' ).'" onkeyup="update_urlpreview( \''.$baseurl.'\'+this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[0].checked=true; update_urlpreview( \''.$baseurl.'\'+this.value );" /></span>'
							.'<div class="notes">'.T_('With trailing slash. By default, leave this field empty. If you want to use a subfolder, you must handle it accordingly on the Webserver (e-g: create a subfolder + stub file or use mod_rewrite).').'</div>',
							'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();"'
			),
			array( 'absolute',
							T_('Absolute URL').':',
							'',
							'<input type="text" id="blog_siteurl_absolute" name="blog_siteurl_absolute" size="40" maxlength="120" value="'.format_to_output( $blog_siteurl_absolute, 'formvalue' ).'" onkeyup="update_urlpreview( this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[1].checked=true; update_urlpreview( this.value );" />'.
							'<span class="notes">'.T_('With trailing slash.').'</span>',
							'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();"'
			)
		),
		T_('Blog Folder URL'), true );

	if( $default_blog_ID = $Settings->get('default_blog_ID') )
	{
		$Debuglog->add('Default blog is set to: '.$default_blog_ID);
		$BlogCache = & get_Cache( 'BlogCache' );
		if( $default_Blog = & $BlogCache->get_by_ID($default_blog_ID, false) )
		{ // Default blog exists
			$defblog = $default_Blog->dget('shortname');
		}
	}
	$Form->radio( 'blog_access_type', $edited_Blog->get( 'access_type' ),
		array(
			array( 'default', T_('Automatic detection by index.php'),
							T_('Match absolute URL or use default blog').
								' ('.( !isset($defblog)
									?	/* TRANS: NO current default blog */ T_('No default blog is currently set')
									: /* TRANS: current default blog */ T_('Current default :').' '.$defblog ).
								')',
							'',
							'onclick="update_urlpreview( false, \'index.php\' );"'
			),
			array( 'index.php', T_('Explicit reference on index.php'),
							T_('You might want to use extra-path info with this.'),
							'',
							'onclick="update_urlpreview( false, \'index.php'.( $Settings->get('links_extrapath') != 'disabled' ? "/'+document.getElementById( 'blog_urlname' ).value" : '?blog='.$edited_Blog->ID."'" ).' )"'
			),
			array( 'stub', T_('Explicit reference to stub file (Advanced)').':',
							'',
							'<label for="blog_stub">'.T_('Stub name').':</label>'.
							'<input type="text" name="blog_stub" id="blog_stub" size="20" maxlength="'.$maxlength_urlname_stub.'" value="'.$edited_Blog->dget( 'stub', 'formvalue' ).'" onkeyup="update_urlpreview( false, this.value );" onfocus="update_urlpreview( false, this.value ); document.getElementsByName(\'blog_access_type\')[2].checked = true;" />'.
							'<div class="notes">'.T_("For this to work, you must handle it accordingly on the Webserver (e-g: create a stub file or use mod_rewrite).").'</div>',
							'onclick="document.getElementById( \'blog_stub\' ).focus();"'
			),
		), T_('Preferred access type'), true );

	$Form->info( T_('URL preview'), '<span id="urlpreview">'.$edited_Blog->dget( 'baseurl', 'entityencoded' ).$blog_urlappend.'</span>' );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Chapter URLs') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', T_('Do not use extra path info'), T_('Chapter links will look like: \'stub?cat=123\'') ),
				array( 'subchap', T_('Use sub-chapter'), T_('Chapter links will look like \'stub/subchap/\'' ) ),
				array( 'chapters', T_('Use chapter path'), T_('Chapter links will look like \'stub/chapter/subchap/\'' ) ),
			), T_('Chapter links'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post URLs') );

		// TODO

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.1  2006/09/11 19:36:58  fplanque
 * blog url ui refactoring
 *
 */
?>