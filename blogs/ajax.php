<?php

require_once dirname(__FILE__).'/conf/_config.php';
require_once $inc_path.'_main.inc.php';

global $DB;

$action = param_action();

switch( $action )
{
	// Users

	case 'get_login_list':

		$text = trim( param( 'value', 'string' ) );
		if( !empty( $text ) )
		{
			$SQL = &new SQl();
			$SQL->SELECT( 'user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_login LIKE \''.$text.'%\'' );
			$SQL->LIMIT( '10' );

			$logins = array();
			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$logins[] = $row->user_login;
			}
			echo implode( ';', $logins );
		}

		break;

	// Comments

	case 'get_comments_awaiting_moderation':

		$blog_ID = param( 'blogid', 'integer' );

		$BlogCache = & get_BlogCache();

		$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

		$CommentList = & new CommentList( $Blog, "'comment','trackback','pingback'", array( 'draft' ), '',	'',	'DESC',	'',	5 );

		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			echo '<div id="comment_'.$Comment->ID.'" class="dashboard_post dashboard_post_'.($CommentList->current_idx % 2 ? 'even' : 'odd' ).'">';

			echo '<div class="floatright"><span class="note status_'.$Comment->status.'">';
			$Comment->status();
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			echo $Comment->get_title(array('author_format'=>'<strong>%s</strong>'));
			$comment_Item = & $Comment->get_Item();
			echo ' '.T_('in response to')
					.' <a href="?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'"><strong>'.$comment_Item->dget('title').'</strong></a>';

			echo '</h3>';

			echo '<div class="notes">';
			$Comment->rating( array(
					'before'      => '',
					'after'       => ' &bull; ',
					'star_class'  => 'top',
				) );
			$Comment->date();
			if( $Comment->author_url( '', ' &bull; Url: <span class="bUrl">', '</span>' ) )
			{
				if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
				{ // There is an URL and we have permission to ban...
					// TODO: really ban the base domain! - not by keyword
					echo ' <a href="'.$dispatcher.'?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode(get_ban_domain($Comment->author_url))
						.'">'.get_icon( 'ban' ).'</a> ';
				}
			}
			$Comment->author_email( '', ' &bull; Email: <span class="bEmail">', '</span> &bull; ' );
			$Comment->author_ip( 'IP: <span class="bIP">', '</span> &bull; ' );
			$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
			echo '</div>';
		 ?>

		<div class="small">
			<?php $Comment->content() ?>
		</div>

		<div class="dashboard_action_area">
		<?php
			// Display edit button if current user has the rights:
			$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton');

			// Display publish NOW button if current user has the rights:
			$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', true, true );

			// Display deprecate button if current user has the rights:
			$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', true, true );

			// Display delete button if current user has the rights:
			$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, '&amp;', true, true );
		?>
		<div class="clear"></div>
		</div>


		<?php
			echo '</div>';
		}

		break;

	case 'get_comments_awaiting_moderation_number':

		global $DB;

		$blog_ID = param( 'blogid', 'integer' );

		$BlogCache = & get_BlogCache();

		$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

		$sql = 'SELECT COUNT(*)
					FROM T_comments
						INNER JOIN T_items__item ON comment_post_ID = post_ID ';

		$sql .= 'INNER JOIN T_postcats ON post_ID = postcat_post_ID
					INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID ';

		$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('othercats.cat_blog_ID');
		$sql .= ' AND comment_type IN (\'comment\',\'trackback\',\'pingback\') ';
		$sql .= ' AND comment_status = \'draft\'';
		$sql .= ' AND '.statuses_where_clause();

		echo $DB->get_var( $sql );

		break;

	case 'set_comment_status':

		$edited_Comment = Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$status = param( 'status', 'string' );
		$edited_Comment->set('status', $status );
		$edited_Comment->dbupdate();
		echo '1';

		break;

	case 'delete_comment':

		$edited_Comment = Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$edited_Comment->dbdelete();
		echo '1';

		break;
}


/*
 * $Log$
 * Revision 1.3  2009/11/24 22:11:54  efy-maxim
 * log
 *
 */
?>