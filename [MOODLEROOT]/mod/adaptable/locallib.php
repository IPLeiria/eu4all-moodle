<?php
/**
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/adaptable/lib.php");
require_once("$CFG->dirroot/local/eu4all/lib/cplib.php");
require_once("$CFG->libdir/filelib.php");

/**
 * Redirected to migrated adaptable if needed,
 * return if incorrect parameters specified
 * @param int $oldid
 * @param int $cmid
 * @return void
 */
function adaptable_set_mainfile($data) {
    global $DB;
    if(isset($data->coursemodule) && isset($data->files)){
	    $fs = get_file_storage();
	    $cmid = $data->coursemodule;
	    $draftitemid = $data->files;
	
	    $context = get_context_instance(CONTEXT_MODULE, $cmid);
	    if ($draftitemid) {
	        file_save_draft_area_files($draftitemid, $context->id, 'mod_adaptable', 'content', 0, array('subdirs'=>true));
	    }
	    $files = $fs->get_area_files($context->id, 'mod_adaptable', 'content', 0, 'sortorder', false);
	    if (count($files) == 1) {
	        // only one file attached, set it as main file automatically
	        $file = reset($files);
	        file_set_sortorder($context->id, 'mod_adaptable', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
	    }
    }
}

/**
 * File browsing support class
 */
class adaptable_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

/*
 *  Invoke CP web service
 */
function adaptable_eu4all_get_adapted_resourceId($id){
	global $DB;
	if($default = $DB->get_field('adaptable_relations', 'resource_id', array('adaptable_id'=>$id, 'type'=>'default'))){
		return eu4all_get_adapted_resource($default);
	}
		
	return -1;
}

/**
 * Get all resource of course, except adaptable
 * 
 * @param int $courseid
 * @param boolean $excludeAlreadyAssociated mixed boolean true to exclude the resources already associated with an adaptable, false to include all the resources, int to select all the free resources and the resources associated with the given integer
 * @version 1.1
 */
function adaptable_get_all_course_resources($courseid, $excludeAlreadyAssociated=false){
	global $CFG, $DB;

	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	
	$exclude = array('folder'=>'folder','adaptable'=>'adaptable');	
	// get list of all resource-like modules
	$allmodules = $DB->get_records('modules', array('visible'=>1));
	$modules = array();
	foreach ($allmodules as $key=>$module) {
		$modname = $module->name;
		$libfile = "$CFG->dirroot/mod/$modname/lib.php";
		if (!file_exists($libfile)) {
			continue;
		}
		$archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
		if ($archetype != MOD_ARCHETYPE_RESOURCE) {
			continue;
		}

		if(!array_key_exists($modname, $exclude)){
			$modules[$modname] = get_string('modulename', $modname);
		}
		//some hacky nasic logging
		add_to_log($course->id, $modname, 'view all', "index.php?id=$course->id", '');
	}


	$modinfo = get_fast_modinfo($course);
	$usesections = course_format_uses_sections($course->format);
	if ($usesections) {
		$sections = get_all_sections($course->id);
	}
	$cms = array();
	$resources = array();
	foreach ($modinfo->cms as $cm) {
		if (!$cm->uservisible) {
			continue;
		}
		if (!array_key_exists($cm->modname, $modules)) {
			continue;
		}
		// let's skip the resources associated with another adaptable
		if($excludeAlreadyAssociated!==false){
			if($relation = $DB->get_record('adaptable_relations', array('resource_id'=>$cm->id), 'adaptable_id')){
				if(is_numeric($excludeAlreadyAssociated)){
					if($relation->adaptable_id!=$excludeAlreadyAssociated){
						continue;
					}
				}else{
					continue;
				}
			}
		}
		
		$cms[$cm->id] = $cm;
		$resources[$cm->modname][] = $cm->instance;
	}

	
	// preload instances
	foreach ($resources as $modname=>$instances) {
		$resources[$modname] = $DB->get_records_list($modname, 'id', $instances, 'id', 'id,name');		
	}
	
	$results = array();
	
	foreach ($cms as $cm) {
		if (!isset($resources[$cm->modname][$cm->instance])) {
			continue;
		}
		$resource = $resources[$cm->modname][$cm->instance];
		$results[$cm->id] = $resource->name;
		
	}

	return $results;
	//die(print_r($results, true));
}