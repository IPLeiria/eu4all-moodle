<?php
/**
 * Capabilities for the 'format_adaptable' component
 *
 * @package    	format_adaptable
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$capabilities = array(
	'format/adaptable:viewadaptables' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_MODULE,
		'archetypes' => array(
			'frontpage' => CAP_PROHIBIT,
			'user' => CAP_PROHIBIT,
			'guest' => CAP_PROHIBIT,
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_PROHIBIT,
			'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		)
	),
);