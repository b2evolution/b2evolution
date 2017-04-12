<?php
/**
 * This file implements the UI view for the widgets params form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs('plugins/_plugin.funcs.php');

/**
 * @var ComponentWidget
 */
global $edited_ComponentWidget;
global $Collection, $Blog, $admin_url, $AdminUI, $Plugins, $display_mode;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'widget_checkchanges' );

if( ! isset( $AdminUI ) || ! isset( $AdminUI->skin_name ) || $AdminUI->skin_name != 'bootstrap' )
{	// Display a link to close form (Don't display this link on bootstrap skin, because it already has an icon to close a modal window)
	$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action' ), '', 3, 2, array( 'class' => 'action_icon close_link' ) );
}

$Form->begin_form( 'fform', sprintf( $creating ?  T_('New widget "%s" in container "%s"') : T_('Edit widget "%s" in container "%s"'), $edited_ComponentWidget->get_name(), $edited_ComponentWidget->get( 'sco_name' ) )
		.' '.action_icon( T_('Open relevant page in online manual'), 'manual', $edited_ComponentWidget->get_help_url(), NULL, 5, NULL, array( 'target' => '_blank' ) ) );

// Plugin widget form event:
$Plugins->trigger_event( 'WidgetBeginSettingsForm', array(
		'Form'            => & $Form,
		'ComponentWidget' => & $edited_ComponentWidget,
	) );

	$Form->add_crumb( 'widget' );
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
	$tmp_params = array( 'for_editing' => true );
	foreach( $edited_ComponentWidget->get_param_definitions( $tmp_params ) as $l_name => $l_meta )
	{
		$l_value = NULL;
		if( $l_name == 'allow_blockcache' )
		{
			if( isset( $l_meta['disabled'] )
			    && ( $l_meta['disabled'] == 'disabled' ) )
			{ // Force checkbox "Allow caching" to unchecked when it is disallowed from widget config
				$l_value = 0;
			}

			if( ! $Blog->get_setting( 'cache_enabled_widgets' ) )
			{ // Widget/block cache is disabled by blog setting
				$l_meta['allow_blockcache']['note'] = sprintf( T_('This widget could be cached but the block cache is OFF. Click <a %s>here</a> to enable.'),
						'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID.'#fieldset_wrapper_caching"' );
				$l_meta['disabled'] = 'disabled';
			}
		}
		// Display field:
		autoform_display_field( $l_name, $l_meta, $Form, 'Widget', $edited_ComponentWidget, NULL, $l_value );
	}

$Form->end_fieldset();


// dh> TODO: allow the widget to display information, e.g. the coll_category_list
//       widget could say which blogs it affects. (Maybe this would be useful
//       for all even, so a default info field(set)).
//       Does a callback make sense? Then we should have a action hook too, to
//       catch any params/settings maybe? Although this could be done in the
//       same hook in most cases probably. (dh)

$Form->buttons( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ),
		array( 'submit', 'actionArray[update_edit]', T_('Save and continue editing...'), 'SaveButton' )
	) );

// Plugin widget form event:
$Plugins->trigger_event( 'WidgetEndSettingsForm', array(
		'Form'            => & $Form,
		'ComponentWidget' => & $edited_ComponentWidget,
	) );

$Form->end_form();

if( $display_mode == 'js' )
{	// Reset previous and Initialize new bozo validator for each new opened widget edit form in popup window,
	// because it is not applied for new created forms dynamically:
?>
<script type="text/javascript">
if( typeof( bozo ) != 'undefined' )
{
	bozo.reset_changes();
	bozo.init();
}
</script>
<?php
}
?>