<?php
/**
 * This file implements the UI view for the widgets params form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs('plugins/_plugin.funcs.php');

/**
 * @var ComponentWidget
 */
global $edited_ComponentWidget;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New widget') : T_('Widget') );

$Form->hidden( 'action', $creating ? 'create' : 'update' );
$Form->hidden( 'wi_ID', $edited_ComponentWidget->ID );
$Form->hiddens_by_key( get_memorized( 'action' ) );

// Display properties:
$Form->begin_fieldset( T_('Properties') );
	$Form->info( T_('Widget type'), $edited_ComponentWidget->get_name() );
 	$Form->info( T_('Description'), $edited_ComponentWidget->get_desc() );
$Form->end_fieldset();


// Display (editable) parameters:
$Form->begin_fieldset( T_('Params') );

	//$params = $edited_ComponentWidget->get_params();

	// Loop through all widget params:
	foreach( $edited_ComponentWidget->get_param_definitions( $tmp_params = array('for_editing'=>true) ) as $l_name => $l_meta )
	{
		// Display field:
		autoform_display_field( $l_name, $l_meta, $Form, 'Widget', $edited_ComponentWidget );
	}

$Form->end_fieldset();


// dh> TODO: allow the widget to display information, e.g. the coll_category_list
//       widget could say which blogs it affects. (Maybe this would be useful
//       for all even, so a default info field(set)).
//       Does a callback make sense? Then we should have a action hook too, to
//       catch any params/settings maybe? Although this could be done in the
//       same hook in most cases probably. (dh)


if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


/*
 * $Log$
 * Revision 1.6  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.5  2008/01/06 17:52:50  fplanque
 * minor/doc
 *
 * Revision 1.4  2008/01/06 15:35:54  blueyed
 * doc, todo
 *
 * Revision 1.3  2007/12/22 19:53:19  yabs
 * cleanup from adding core params
 *
 * Revision 1.2  2007/12/22 16:57:01  yabs
 * adding core parameters for css id/classname and widget list title
 *
 * Revision 1.1  2007/06/25 11:01:59  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/19 20:42:53  fplanque
 * basic demo of widget params handled by autoform_*
 *
 * Revision 1.1  2007/06/19 00:03:26  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 */
?>