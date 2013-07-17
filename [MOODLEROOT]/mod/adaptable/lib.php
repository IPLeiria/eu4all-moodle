<?php
/**
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * List of features supported in Adaptable module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function adaptable_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function adaptable_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function adaptable_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function adaptable_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function adaptable_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add the adaptable relations to the database
 * @param $data to get the data from
 * @param $eventdata to append the event data
 * @return boolean true on success, false otherwise
 */
function adaptable_add_relations_to_db($data, &$eventdata){
    global $DB;
    
	$adaptableRelationsData = new stdClass();
    $adaptableRelationsData->adaptable_id = $data->instance;
    $adaptableRelationsData->resource_id = $data->defaultResourceId;
    $adaptableRelationsData->type = 'default';
    $adaptableRelationsData->original_mode = $data->defaultResourceOriginalMode;
    $adaptableRelationsData->original_content_type = $data->defaultResourceOriginalContentType;
    if($DB->insert_record('adaptable_relations', $adaptableRelationsData)){
    	$eventdata->defaultResource = $adaptableRelationsData;
    	$eventdata->alternativeResources = array();
    	
    	$alternativeResource = array();
		foreach($data as $key=>$value){
        	if(preg_match("/resourceAlternative\d+$/", $key)){
        		$alternativeResource = new stdClass();
        		$alternativeResource->adaptable_id = $data->instance;
        		$alternativeResource->resource_id = $data->{"{$key}Resource"};
        		$alternativeResource->adaptation_of = $data->defaultResourceId;
        		$alternativeResource->type = 'alternative';
        		$alternativeResource->original_mode = $data->{"{$key}OriginalMode"};
        		$alternativeResource->adaptation_type = $data->{"{$key}AdaptationType"};
        		$alternativeResource->representation_form = $data->{"{$key}RepresentationForm"};
        		$alternativeResource->original_access_mode = $data->defaultResourceOriginalMode;
        		
        		if($DB->insert_record('adaptable_relations', $alternativeResource)){
        			$eventdata->alternativeResources[]=$alternativeResource;
        		}
        	}
		}
		return true;
    }
    return false;
}

/**
 * Add adaptable instance.
 * @param object $data
 * @param object $mform
 * @return int new resoruce instance id
 */
function adaptable_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    $cmid = $data->coursemodule;
    $data->timemodified = time();
    $displayoptions = array();
    if (isset($data->display) && $data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (isset($data->display) && in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printheading'] = (int)!empty($data->printheading);
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    if($data->id = $DB->insert_record('adaptable', $data)){
	    // we need to use context now, so we need to make sure all needed info is already in db
	    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
	    $data->instance = $data->id;
	    $eventdata = new stdClass();
    	if(adaptable_add_relations_to_db($data, $eventdata)){
	    	events_trigger('adaptable_created', $eventdata);
    		adaptable_set_mainfile($data);
    		
    		return $data->id;
	    }
    }
    
    return $data->id;
}

/**
 * Update adaptable instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function adaptable_update_instance($data, $mform) {
    global $CFG, $DB;
    
    if(isset($data->submitbutton) || isset($data->submitbutton2)){
    	
    	require_once("$CFG->libdir/resourcelib.php");
	    $data->timemodified = time();
	    $data->id           = $data->instance;
	    $data->revision++;
	
	    $displayoptions = array();
	    if (isset($data->display) && $data->display == RESOURCELIB_DISPLAY_POPUP) {
	        $displayoptions['popupwidth']  = $data->popupwidth;
	        $displayoptions['popupheight'] = $data->popupheight;
	    }
	    if (isset($data->display) && in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
	        $displayoptions['printheading'] = (int)!empty($data->printheading);
	        $displayoptions['printintro']   = (int)!empty($data->printintro);
	    }
	    $data->displayoptions = serialize($displayoptions);
	    
	    
	    $eventdata = new stdClass();
	    
	    // cache and delete the old records
	    $previousAdaptableRelations = $DB->get_records('adaptable_relations', array('adaptable_id'=>$data->instance));
	    if($DB->delete_records('adaptable_relations', array('adaptable_id'=>$data->instance))){
	    	$eventdata->previousAdaptableRelations = $previousAdaptableRelations;
	    	if($DB->update_record('adaptable', $data)){
		    	if(adaptable_add_relations_to_db($data, $eventdata)){
			    	events_trigger('adaptable_updated', $eventdata);
			    	adaptable_set_mainfile($data);
	    			
	    			return true;
			    }
	    	}
	    }
    	return false;
    }
    return true;
}

/**
 * Delete adaptable instance.
 * @param int $id
 * @return boolean true on success, false otherwise
 */
function adaptable_delete_instance($id) {
    global $DB;
    
    $eventdata = new stdClass();
    if(($eventdata->previousAdaptableRelations = $DB->get_records('adaptable_relations', array('adaptable_id'=>$id))) && $DB->delete_records('adaptable_relations', array('adaptable_id'=>$id))){
    	if($DB->delete_records('adaptable', array('id'=>$id))){
    		events_trigger('adaptable_deleted', $eventdata);
    		return true;
    	}
    }
    return false;
}

/**
 * Handles the associated resources deletion triggering the update on the central repository
 * 
 * @param object $eventdata with the module data
 * @return boolean with the result of the operation
 */
function adaptable_mod_deleted_handler($eventdata){
	global $DB;
	
	if(($previousAdaptableRelations = $DB->get_records('adaptable_relations', array('resource_id'=>$eventdata->cmid))) && $DB->delete_records('adaptable_relations', array('resource_id'=>$eventdata->cmid))){
		$eventdata = new stdClass();
		$eventdata->previousAdaptableRelations = $previousAdaptableRelations;
		events_trigger('adaptable_deleted', $eventdata);
	}
	return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $resource
 * @return object|null
 */
function adaptable_user_outline($course, $user, $mod, $resource) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'adaptable',
                                              'action'=>'view', 'info'=>$resource->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new object();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $resource
 */
function adaptable_user_complete($course, $user, $mod, $resource) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'adaptable',
                                              'action'=>'view', 'info'=>$resource->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'adaptable');
    }
}

/**
 * Returns the users with data in one adaptable
 *
 * @param int $resourceid
 * @return bool false
 */
function adaptable_get_participants($resourceid) {
    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return object info
 */
function adaptable_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$page = $DB->get_record('adaptable', array('id'=>$coursemodule->instance), 'id, name, display ,displayoptions')) {
        return NULL;
    }

    $info = new object();
    $info->name = $page->name;

    if ($page->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/adaptable/view.php?id=$coursemodule->id";
    $options = empty($page->displayoptions) ? array() : unserialize($page->displayoptions);
    $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resievents_trigger('adaptable_deleted', $eventdata);zable=yes";
    $info->extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";

    return $info;
}


/**
 * Lists all browsable file areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function adaptable_get_file_areas($course, $cm, $context) {
   $areas = array();
    $areas['content'] = get_string('content', 'adaptable');
    return $areas;
}

/**
 * File browsing support for adaptable module content area.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function adaptable_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_adaptable', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_adaptable', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/adaptable/locallib.php");
        return new adaptable_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: adaptable_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the adaptable files.
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function adaptable_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_adaptable/$filearea/0/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        $resource = $DB->get_record('adaptable', array('id'=>$cminfo->instance), 'id, legacyfiles', MUST_EXIST);
        if ($resource->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
            return false;
        }
        if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_adaptable', 'content', 0)) {
            return false;
        }
        // file migrate - update flag
        $resource->legacyfileslast = time();
        $DB->update_record('adaptable', $resource);
    }

    // should we apply filters?
    $mimetype = $file->get_mimetype();
    if ($mimetype = 'text/html' or $mimetype = 'text/plain') {
        $filter = $DB->get_field('adaptable', 'filterfiles', array('id'=>$cm->instance));
    } else {
        $filter = 0;
    }

    // finally send the file
    send_stored_file($file, 86400, $filter, $forcedownload);
}