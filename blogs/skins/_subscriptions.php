<?php
/**
 * This is the template that displays the user subscriptions form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=profile
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


if( ! is_logged_in() )
{ // must be logged in!
	echo '<p>', T_( 'You are not logged in.' ), '</p>';
	return;
}
// --- //
param( 'redirect_to', 'string', '');


/**
 * @var Form form to update the profile
 */
$Form = & new Form( $htsrv_url.'subs_update.php', 'SubsForm' );

$Form->begin_form( 'bComment' );
	$Form->hidden( 'checkuser_id', $current_User->ID );
	$Form->hidden( 'redirect_to', $redirect_to );

	$Form->fieldset( T_('Global settings') );

		$Form->info( T_('Login'), $current_User->get('login'), T_('ID').': '.$current_User->ID );

		$Form->text( 'newuser_email', $current_User->get( 'email' ), 40, T_('Email'), '', 100, 'bComment' );

		$Form->checkbox( 'newuser_notify', $current_User->get( 'notify' ), T_('Notifications'), T_('Check this to receive a notification whenever one of <strong>your</strong> posts receives comments, trackbacks, etc.') );

	$Form->fieldset_end();

	$Form->fieldset( T_('Blog subscriptions') );

		$sql = 'SELECT blog_ID, blog_shortname, bloguser_subs_items, bloguser_subs_comments
							FROM T_blogs LEFT JOIN T_blogusers ON ( blog_ID = bloguser_blog_ID AND bloguser_user_ID = '.$current_User->ID.' )
						 WHERE blog_in_bloglist <> 0';
		$blog_subs = $DB->get_results( $sql );

		$subs_blog_IDs = array();
		foreach( $blog_subs AS $blog_sub )
		{
			$subs_blog_IDs[] = $blog_sub->blog_ID;
			$subscriptions = array( array( 'sub_items_'.$blog_sub->blog_ID,    '1', T_('Posts'),    $blog_sub->bloguser_subs_items ),
															array( 'sub_comments_'.$blog_sub->blog_ID, '1', T_('Comments'), $blog_sub->bloguser_subs_comments ) );
			$Form->checklist( $subscriptions, 'subscriptions', format_to_output( $blog_sub->blog_shortname, 'htmlbody' ) );
		}

		$Form->hidden( 'subs_blog_IDs', implode( ',', $subs_blog_IDs ) );

	$Form->fieldset_end();

$Form->end_form( array( array( '', '', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
?>