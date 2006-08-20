<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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


echo '<h2>'.T_('Banned domains blacklist').'</h2>';

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
$keywords = $Request->param( 'keywords', 'string', '', true );

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
	global $Request;

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
		 * @var the antispam sources
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
 * Revision 1.3  2006/08/20 20:12:33  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.2  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.1  2006/06/25 17:42:47  fplanque
 * better use of Results class (mainly for filtering)
 *
 */
?>