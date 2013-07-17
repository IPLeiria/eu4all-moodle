<?php
/**
 * Settings definitions for the EU4ALL components configuration
 *
 * @package    	EU4ALL
 * @subpackage 	local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
	$ADMIN->add('root', new admin_category('eu4all', get_string('eu4all', 'local_eu4all')));
	$ADMIN->add('eu4all', new admin_externalpage('eu4all_config', get_string('configuration', 'local_eu4all'), new moodle_url('/local/eu4all/pages/setup.php')), 'moodle/site:config');
}