<?php
/**
 * Database upgrade instructions for the 'local_eu4all' component
 *
 * @package    	EU4ALL
 * @subpackage 	UM, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_eu4all_upgrade($oldversion){
	global $CFG, $DB;

    $dbman = $DB->get_manager();

    if($oldversion<2010080400){
    	upgrade_plugin_savepoint(true, 2010080400, 'local', 'eu4all');
    }
    
    return true;
}