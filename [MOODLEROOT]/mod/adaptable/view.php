<?php


/**
 * Resource module version information
 *
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/adaptable/locallib.php');

$id  = optional_param('id', 0, PARAM_INT); // Course Module ID

$cm = get_coursemodule_from_id('adaptable', $id);

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$PAGE->set_url('/mod/adaptable/view.php', array('id' => $cm->instance));

error_log($course->id .'adaptable'. ' view' . ' view.php?id='.$cm->id . ' '. $cm->instance. ' '. $cm->id);

//get resourceId from CP 
$resourceId = adaptable_eu4all_get_adapted_resourceId($cm->instance);

//course_module 
$cm1 = $DB->get_record('course_modules', array('id'=>$resourceId), '*', MUST_EXIST);

$module = $DB->get_record('modules', array('id'=>$cm1->module), '*', MUST_EXIST);


$url = new moodle_url('/mod/'.$module->name.'/view.php/', array('id'=>$resourceId));

global $_SESSION;
$_SESSION['adaptable']=1;

redirect($url);


