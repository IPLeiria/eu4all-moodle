<?php 
/**
 * Commom library for the EU4ALL
 *
 * @package    	EU4ALL
 * @subpackage 	UM, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die(''); // silence is golden

if(!defined('EU4ALL')):
	define('EU4ALL', 'EU4ALL');
endif;

if(!defined('EU4ALL_PLUGINNAME')):
	define('EU4ALL_PLUGINNAME', 'local_eu4all');
endif;

if(!defined('EU4ALL_PREFIX')):
	$prefix = get_config(EU4ALL_PLUGINNAME, 'eu4all_resource_prefix');
	define('EU4ALL_PREFIX', (($prefix)?$prefix:'IPL_'));
endif;

if(!defined('EU4ALL_MANAGER_REFERENCE')):
	$managerReference = get_config(EU4ALL_PLUGINNAME, 'eu4all_manager_reference');
	define('EU4ALL_MANAGER_REFERENCE', (($managerReference)?$managerReference:'IPL_Moodle'));
endif;


/**
 * Adds module specific settings to the myprofile item in the navigation block
 *
 * @param navigation_node $assignmentnode The node to add module settings to
 */
function eu4all_extends_navigation(&$navigation){
	global $PAGE, $USER;
	
	$systemcontext = get_context_instance(CONTEXT_SYSTEM);
	$view = has_capability('local/eu4all:umviewownprofile', $systemcontext);
	$edit = has_capability('local/eu4all:umeditownprofile', $systemcontext);
	
	if(($view || $edit) && $node = $navigation->get('myprofile', navigation_node::TYPE_USER)){
		if($category = $node->add(get_string('pluginname', 'local_eu4all'), null, navigation_node::TYPE_CATEGORY)){
			// Force the EU4ALL category menu expansion if the user is using a screen reader
			if(!empty($USER->screenreader)){
				$category->force_open();
			}
			
			if($view){
				$category->add(get_string('viewAccessibilityPreferences', 'local_eu4all'), new moodle_url('/local/eu4all/pages/userpreferences.php', array('id'=>$USER->id)), navigation_node::TYPE_CUSTOM);
			}
			if($edit){
				$category->add(get_string('editAccessibilityPreferences', 'local_eu4all'), new moodle_url('/local/eu4all/pages/userpreferencesedit.php', array('id'=>$USER->id)), navigation_node::TYPE_SETTING);
			}
		}
		/* // Ok moodle, you've won just this once. I will try to hack the settingsnav (it has an unexpected behaviour in some moodle instances)
		if(isset($PAGE->settingsnav) && $node = $PAGE->settingsnav->get('usercurrentsettings', navigation_node::TYPE_CONTAINER)){
			$node->add(get_string('editAccessibilityPreferences', 'local_eu4all'), new moodle_url('/local/eu4all/pages/userpreferencesedit.php', array('id'=>$USER->id)), navigation_node::TYPE_SETTING);
		}
		*/
	}
}