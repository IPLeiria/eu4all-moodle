<?php 
/**
 * Event handlers registration for the 'local_eu4all' component
 *
 * @package    	EU4ALL
 * @subpackage 	UM, MR, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array (
	'user_created' => array (
		'handlerfile'      => '/local/eu4all/lib/umlib.php',
		'handlerfunction'  => 'eu4all_um_usercreated_handler',
		'schedule'         => 'instant'
	),
	'user_updated' => array (
		'handlerfile'      => '/local/eu4all/lib/umlib.php',
		'handlerfunction'  => 'eu4all_um_userupdated_handler',
		'schedule'         => 'instant'
	),
	'user_deleted' => array (
		'handlerfile'      => '/local/eu4all/lib/umlib.php',
		'handlerfunction'  => 'eu4all_um_userdeleted_handler',
		'schedule'         => 'instant'
	),
	'user_authenticated' => array (
		'handlerfile'      => '/local/eu4all/lib/umlib.php',
		'handlerfunction'  => 'eu4all_um_userauthenticated_handler',
		'schedule'         => 'instant'
	),
	'adaptable_created' => array (
		'handlerfile'      => '/local/eu4all/lib/mrlib.php',
		'handlerfunction'  => 'eu4all_mr_adaptable_created_handler',
		'schedule'         => 'instant'
	),
	'adaptable_updated' => array (
		'handlerfile'      => '/local/eu4all/lib/mrlib.php',
		'handlerfunction'  => 'eu4all_mr_adaptable_updated_handler',
		'schedule'         => 'instant'
	),
	'adaptable_deleted' => array (
		'handlerfile'      => '/local/eu4all/lib/mrlib.php',
		'handlerfunction'  => 'eu4all_mr_adaptable_deleted_handler',
		'schedule'         => 'instant'
	),
);