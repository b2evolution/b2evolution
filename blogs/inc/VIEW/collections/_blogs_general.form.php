<?php
/**
 * This file implements the UI view for the General blog properties.
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
		$blog_urlappend = 'index.php'.( $Settings->get('links_extrapath') ? '/'.$edited_Blog->get( 'stub' ) : '?blog='.$edited_Blog->ID );
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

global $action, $next_action, $blogtemplate, $blog, $tab;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );
if( $action == 'copy' )
{
	$Form->hidden( 'blogtemplate', $blog );
}

$Form->begin_fieldset( T_('General parameters'), array( 'class'=>'fieldset clear' ) );
	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );
	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );
	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );
$Form->end_fieldset();


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

$Form->begin_fieldset( T_('Blog URL parameters') );

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
							'onclick="update_urlpreview( false, \'index.php'.( $Settings->get('links_extrapath') ? "/'+document.getElementById( 'blog_urlname' ).value" : '?blog='.$edited_Blog->ID."'" ).' )"'
			),
			array( 'stub', T_('Explicit reference to stub file (Advanced)').':',
							'',
							'<label for="blog_stub">'.T_('Stub name').':</label>'.
							'<input type="text" name="blog_stub" id="blog_stub" size="20" maxlength="'.$maxlength_urlname_stub.'" value="'.$edited_Blog->dget( 'stub', 'formvalue' ).'" onkeyup="update_urlpreview( false, this.value );" onfocus="update_urlpreview( false, this.value ); document.getElementsByName(\'blog_access_type\')[2].checked = true;" />'.
							'<div class="notes">'.T_("For this to work, you must handle it accordingly on the Webserver (e-g: create a stub file or use mod_rewrite).").'</div>',
							'onclick="document.getElementById( \'blog_stub\' ).focus();"'
			),
		), T_('Preferred access type'), true );

	$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL blog name'), T_('Used to uniquely identify this blog. Appears in URLs when using extra-path info. Also gets used as default for the media location (see the advanced tab).'), $maxlength_urlname_stub );

	$Form->info( T_('URL preview'), '<span id="urlpreview">'.$edited_Blog->dget( 'baseurl', 'entityencoded' ).$blog_urlappend.'</span>' );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'blog_allowcomments', $edited_Blog->get( 'allowcomments' ),
						array(  array( 'always', T_('Always on all posts'), T_('Always allow comments on every posts') ),
						array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly') ),
						array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog') ),
					), T_('Allow comments'), true );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $status_options, T_('New feedback status') /* gets referred to in antispam settings form */, array(
				'value' => $edited_Blog->get_setting('new_feedback_status'),
				'note' => T_('This status will be assigned to any new comment/trackback (unless overriden by plugins).')
			) );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

?>


<script type="text/javascript">
	<!--
	document.getElementById( 'blog_name' ).focus();
	// -->
</script>