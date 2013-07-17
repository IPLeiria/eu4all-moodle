<?php
/**
 * Database installation for the 'local_eu4all' component
 *
 * @package    	EU4ALL
 * @subpackage 	UM, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/user/profile/definelib.php');
require_once($CFG->dirroot.'/local/eu4all/lib/umlib.php');

/**
 * Set the user profile extra fields for the EU4ALL plugin
 * 
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @return boolean true on success
 */
function xmldb_local_eu4all_install() {
    global $CFG, $DB;
    
    return true;
}