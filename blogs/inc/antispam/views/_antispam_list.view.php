<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


echo '<a href="?ctrl=set_antispam" style="float: right;"><strong>'.T_('Antispam settings').' &raquo;</strong></a>';
echo '<h2>'.T_('Antispam blacklist').'</h2>';

// ADD KEYWORD FORM:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) // TODO: check for 'add' here once it's mature.
{ // add keyword or domain
	global $keyword;

	$Form = & new Form( NULL, 'antispam_add', 'post', 'compact' );
	$Form->begin_form( 'fform', T_('Add a banned keyword') );
		$Form->hidden_ctrl();
		$Form->hidden( 'action', 'ban' );
		$Form->text( 'keyword', $keyword, 50, T_('Keyword/phrase to ban'), '', 80 ); // TODO: add note
		/*
		 * TODO: explicitly add a domain?
		 * $add_Form->text( 'domain', $domain, 30, T_('Add a banned domain'), 'note..', 80 ); // TODO: add note
		 */
	$Form->end_form( array( array( 'submit', 'submit', T_('Check & ban...'), 'SaveButton' ) ) );
}


echo '<p class="center">'.T_('Any URL containing one of the following keywords will be banned from posts, comments and logs.');
if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{
	echo '<br />'.T_( 'If a keyword restricts legitimate domains, click on the green tick to stop banning with this keyword.');
}
echo '</p>';


if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // User can edit:
	?>
	<p class="center">
		[<a href="?ctrl=antispam&amp;action=poll"><?php echo T_('Request abuse update from centralized blacklist!') ?></a>]
		[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
	</p>
	<?php
}


/*
 * Query antispam blacklist:
 */
$keywords = param( 'keywords', 'string', '', true );

$where_clause = '';

if( !empty( $keywords ) )
{
	$kw_array = split( ' ', $keywords );
	foreach( $kw_array as $kw )
	{
		$where_clause .= 'aspm_string LIKE "%'.$DB->escape($kw).'%" AND ';
	}
}

$sql = 'SELECT aspm_ID, aspm_string, aspm_source
					FROM T_antispam
				 WHERE '.$where_clause.' 1';

// Create result set:
$Results = & new Results( $sql, 'antispam_' );

$Results->title = T_('Banned keywords blacklist');


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_antispam( & $Form )
{
	$Form->text( 'keywords', get_param('keywords'), 20, T_('Keywords'), T_('Separate with space'), 50 );
}
$Results->filter_area = array(
	'callback' => 'filter_antispam',
	'url_ignore' => 'results_antispam_page,keywords',
	'presets' => array(
		'all' => array( T_('All keywords'), '?ctrl=antispam' ),
		)
	);



/*
 * Column definitions:
 */
$Results->cols[] = array(
						'th' => T_('Keyword'),
						'order' => 'aspm_string',
						'td' => '%htmlspecialchars(#aspm_string#)%',
					);

// Set columns:
function antispam_source2( & $row )
{
	static $aspm_sources = NULL;

	if( $aspm_sources === NULL )
	{
		/**
		 * the antispam sources
		 * @var array
		 * @static
		 */
		$aspm_sources = array (
			'local' => T_('Local'),
			'reported' => T_('Reported'),
			'central' => T_('Central'),
		);
	}

	return $aspm_sources[$row->aspm_source];
}
$Results->cols[] = array(
						'th' => T_('Source'),
						'order' => 'aspm_source',
						'td' => '%antispam_source2({row})%',
					);

// Check if we need to display more:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // User can edit, spamlist: add controls to output columns:
	// Add CHECK to 1st column:
	$Results->cols[0]['td'] = action_icon( TS_('Allow keyword back (Remove it from the blacklist)'), 'allowback',
															 '?ctrl=antispam&amp;action=remove&amp;hit_ID=$aspm_ID$' )
															 .$Results->cols[0]['td'];

	// Add a column for actions:
	function antispam_actions( & $row )
	{
		$output = '';

		if( $row->aspm_source == 'local' )
		{
			$output .= '[<a href="'.regenerate_url( 'action,keyword', 'action=report&amp;keyword='
									.rawurlencode( $row->aspm_string )).'" title="'.
									T_('Report abuse to centralized ban blacklist!').'">'.
									T_('Report').'</a>]';
		}

		return $output.'[<a href="'.regenerate_url( 'action,keyword', 'action=ban&amp;keyword='
									.rawurlencode( $row->aspm_string )).'" title="'.
									T_('Check hit-logs and comments for this keyword!').'">'.
									T_('Re-check').'</a>]';
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td' => '%antispam_actions({row})%',
						);
}

// Display results:
$Results->display();


/*
 * $Log$
 * Revision 1.3  2008/01/21 09:35:25  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/11/22 14:16:43  fplanque
 * antispam / banning cleanup
 *
 * Revision 1.1  2007/09/04 14:56:19  fplanque
 * antispam cleanup
 *
 * Revision 1.1  2007/06/25 10:59:22  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.6  2006/12/07 23:13:12  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 */
?>