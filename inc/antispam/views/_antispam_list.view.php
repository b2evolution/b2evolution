<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( get_param('display_mode') == 'js' )
{	// This is an Ajax response
	echo '<h2 class="page-title">'.T_('Antispam blacklist').'</h2>';
}

echo '<p class="well">'.T_('User generated content containing keywords from the Antispam Blacklist will be rejected.');
if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{
	global $antispamsrv_tos_url;
	echo '<br />'.sprintf( T_('You can share your keywords with and retrieve keywords from the Central Antispam Blacklist service <a %s>Terms of service</a>'), 'href="'.$antispamsrv_tos_url.'"' );
}
echo '</p>';

// ADD KEYWORD FORM:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) // TODO: check for 'add' here once it's mature.
{ // add keyword or domain
	global $keyword;

	$Form = new Form( NULL, 'antispam_add', 'post', 'compact' );
	$Form->begin_form( 'fform', T_('Add a banned keyword').get_manual_link( 'antispam-tab' ) );
		$Form->add_crumb('antispam');
		$Form->hidden_ctrl();
		$Form->hidden( 'action', 'ban' );
		$Form->text( 'keyword', $keyword, 50, T_('Keyword/phrase to ban'), '', 80 ); // TODO: add note
		/*
		 * TODO: explicitly add a domain?
		 * $add_Form->text( 'domain', $domain, 30, T_('Add a banned domain'), 'note..', 80 ); // TODO: add note
		 */
	$Form->end_form( array( array( 'submit', 'submit', T_('Check & ban...'), 'SaveButton' ) ) );
}


/*
 * Query antispam blacklist:
 */
$keywords = param( 'keywords', 'string', '', true );
$source = param( 'source', 'string', '', true );

$SQL = new SQL();

$SQL->SELECT( 'askw_ID, askw_string, askw_source' );
$SQL->FROM( 'T_antispam__keyword' );

if( ! empty( $keywords ) )
{	// Filter by keywords:
	$SQL->add_search_field( 'askw_string' );
	$SQL->WHERE_kw_search( $keywords, 'AND' );
}

if( ! empty( $source ) )
{	// Filter by source:
	$SQL->WHERE_and( 'askw_source = '.$DB->quote( $source ) );
}

// Create result set:
$Results = new Results( $SQL->get(), 'antispam_' );

$Results->title = T_('Banned keywords blacklist');

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{	// Allow to request keywords from Central Antispam if current user has a permission:
	global $admin_url;
	$Results->global_icon( T_('Request update from Central Antispam Blacklist'), '', $admin_url.'?ctrl=antispam&amp;action=poll&amp;'.url_crumb( 'antispam' ), T_('Request update from Central Antispam Blacklist'), 0, 0, array( 'class' => 'action_icon btn-primary' ) );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_antispam( & $Form )
{
	$Form->text( 'keywords', get_param('keywords'), 20, T_('Keywords'), T_('Separate with space'), 50 );

	$Form->select_input_array( 'source', get_param( 'source' ), array(
			''         => T_('All'),
			'local'    => T_('Local'),
			'reported' => T_('Reported'),
			'central'  => T_('Central'),
		), T_('Source') );
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
						'order' => 'askw_string',
						'td' => '%htmlspecialchars(#askw_string#)%',
					);

// Set columns:
function antispam_source2( & $row )
{
	static $askw_sources = NULL;

	if( $askw_sources === NULL )
	{
		/**
		 * the antispam sources
		 * @var array
		 * @static
		 */
		$askw_sources = array (
			'local' => T_('Local'),
			'reported' => T_('Reported'),
			'central' => T_('Central'),
		);
	}

	return $askw_sources[$row->askw_source];
}
$Results->cols[] = array(
						'th' => T_('Source'),
						'th_class' => 'shrinkwrap',
						'order' => 'askw_source',
						'td' => '%antispam_source2({row})%',
					);

// Check if we need to display more:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // User can edit, spamlist: add controls to output columns:

	// Add a column for actions:
	function antispam_actions( & $row )
	{
		global $admin_url;

		$output = '';

		if( $row->askw_source == 'local' )
		{
			$output .= '<a href="'.regenerate_url( 'action,keyword', 'action=report&amp;keyword='
				.rawurlencode( $row->askw_string ) ).'&amp;'.url_crumb( 'antispam' ).'" title="'.
				T_('Report abuse to centralized ban blacklist!').'" class="btn btn-warning btn-xs">'.
				T_('Report').'</a> ';
		}

		$output .= '<a href="'.regenerate_url( 'action,keyword', 'action=ban&amp;keyword='
			.rawurlencode( $row->askw_string ) ).'&amp;'.url_crumb( 'antispam' ).'" title="'.
			T_('Check hit-logs and comments for this keyword!').'" class="btn btn-default btn-xs">'.
			T_('Re-check').'</a>';

		$output .= ' <a href="'.$admin_url.'?ctrl=antispam&amp;action=remove&amp;hit_ID='.$row->askw_ID.'&amp;'.url_crumb( 'antispam' )
			.'" title="'.T_('Allow keyword back (Remove it from the blacklist)').'" class="btn btn-danger btn-xs">'.
			T_('Remove from blacklist').'</a>';

		return $output;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td' => '%antispam_actions({row})%',
							'td_class' => 'nowrap',
						);
}

// Display results:
$Results->display();

?>