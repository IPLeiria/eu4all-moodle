<?php 
/**
 * Event handlers registration for the 'mod_adaptable' component
 *
 * @package    	EU4ALL
 * @subpackage 	CP, MR, mod_adaptable
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array (
	'mod_deleted' => array (
		'handlerfile'      => '/mod/adaptable/lib.php',
		'handlerfunction'  => 'adaptable_mod_deleted_handler',
		'schedule'         => 'instant'
	),
);