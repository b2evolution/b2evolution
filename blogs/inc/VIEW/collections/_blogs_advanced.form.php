<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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


$Form = & new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'advanced' );
$Form->hidden( 'blog',$edited_Blog->ID );


$Form->begin_fieldset( T_('Static file generation'), array( 'class'=>'fieldset clear' ) );
$Form->text( 'blog_staticfilename', $edited_Blog->get( 'staticfilename' ), 30, T_('Static filename'), T_('This is the .html file that will be created when you generate a static version of the blog homepage.') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Media library') );
global $basepath, $media_subdir;
$Form->radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
									array(
										array( 'none',
														T_('None') ),
										array( 'default',
														T_('Default'),
														sprintf( T_('subdirectory &quot;%s&quot; (URL blog name) of %s'), $edited_Blog->urlname, $basepath.$media_subdir ) ),
										array( 'subdir',
														T_('Subdirectory of media folder').':',
														'',
														' <span class="nobr"><code>'.$basepath.$media_subdir.'</code><input type="text" name="blog_media_subdir" size="20" maxlength="255" value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
										array( 'custom',
														T_('Custom location').':',
														'',
														'<fieldset>'
															.'<div class="label">'.T_('directory').':</div><div class="input"><input type="text" name="blog_media_fullpath" size="50" maxlength="255" value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
															.'<div class="label">'.T_('URL').':</div><div class="input"><input type="text" name="blog_media_url" size="50" maxlength="255" value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
									), T_('Media dir location'), true
								);
$Form->end_fieldset();

$Form->begin_fieldset( T_('After each new post...') );
$Form->checkbox( 'blog_pingb2evonet', $edited_Blog->get( 'pingb2evonet' ), T_('Ping b2evolution.net'), T_('to get listed on the "recently updated" list on b2evolution.net').' [<a href="http://b2evolution.net/about/terms.html">'.T_('Terms of service').'</a>]' );
$Form->checkbox( 'blog_pingtechnorati', $edited_Blog->get( 'pingtechnorati' ), T_('Ping technorati.com'), T_('to give notice of new post.') );
$Form->checkbox( 'blog_pingweblogs', $edited_Blog->get( 'pingweblogs' ), T_('Ping weblogs.com'), T_('to give notice of new post.') );
$Form->checkbox( 'blog_pingblodotgs', $edited_Blog->get( 'pingblodotgs' ), T_('Ping blo.gs'), T_('to give notice of new post.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Advanced options') );
$Form->radio( 'blog_allowcomments', $edited_Blog->get( 'allowcomments' ),
					array(  array( 'always', T_('Always on all posts'), T_('Always allow comments on every posts') ),
					array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly') ),
					array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog') ),
				), T_('Allow comments'), true );

$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );
$Form->checkbox( 'blog_allowpingbacks', $edited_Blog->get( 'allowpingbacks' ), T_('Allow pingbacks'), T_("Allow other bloggers to send pingbacks to this blog, letting you know when they refer to it. This will also let you send pingbacks to other blogs.") );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
?>