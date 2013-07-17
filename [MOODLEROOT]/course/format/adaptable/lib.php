<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains general functions for the course format Topic
 *
 * @since 2.0
 * @package moodlecore
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_adaptable_uses_sections() {
    return true;
}

/**
 * Do not show the activities in the navigation
 */
function callback_adaptable_display_content() {
    return false;
}

/**
 * Do not show the activities in the navigation
 */
function callback_adaptable_load_content(&$navigation, $course, $coursenode) {
	return false; //$navigation->load_generic_course_sections($course, $coursenode, 'adaptable');
}

/**
 * The string that is used to describe a section of ttopicshe course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_adaptable_definition() {
    return get_string('topic');
}

/**
 * The GET argument variable that is used to identify the section being
 * viewed by the user (if there is one)
 *
 * @return string
 */
function callback_adaptable_request_key() {
    return 'topic';
}

function callback_adaptable_get_section_name($course, $section) {
    // We can't add a node without any text
    if (!empty($section->name)) {
        return $section->name;
    } else if ($section->section == 0) {
        return get_string('section0name', 'format_adaptable');
    } else {
        return get_string('topic').' '.$section->section;
    }
}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
function callback_adaptable_ajax_support() {
    $ajaxsupport = new stdClass();
    /** // AJAX disabled while the bug MDL-25700 submitted by us isn't resolved
    $ajaxsupport->capable = true;
    */
    $ajaxsupport->capable = false;
    $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
    return $ajaxsupport;
}

function adaptable_format_print_section($course, $section, $mods, $modnamesused, $absolute=false, $width="100%", $hidecompletion=false) {
    global $CFG, $USER, $DB, $PAGE, $OUTPUT;

    static $initialised;

    static $groupbuttons;
    static $groupbuttonslink;
    static $isediting;
    static $ismoving;
    static $strmovehere;
    static $strmovefull;
    static $strunreadpostsone;
    static $usetracking;
    static $groupings;
    

    if (!isset($initialised)) {
        $groupbuttons     = ($course->groupmode or (!$course->groupmodeforce));
        $groupbuttonslink = (!$course->groupmodeforce);
        $isediting        = $PAGE->user_is_editing();
        $ismoving         = $isediting && ismoving($course->id);
        if ($ismoving) {
            $strmovehere  = get_string("movehere");
            $strmovefull  = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }
        include_once($CFG->dirroot.'/mod/forum/lib.php');
        if ($usetracking = forum_tp_can_track_forums()) {
            $strunreadpostsone = get_string('unreadpostsone', 'forum');
        }
        $initialised = true;
    }

    $labelformatoptions = new stdClass();
    $labelformatoptions->noclean = true;
    $labelformatoptions->overflowdiv = true;

/// Casting $course->modinfo to string prevents one notice when the field is null
    $modinfo = get_fast_modinfo($course);
    $completioninfo = new completion_info($course);

    //Accessibility: replace table with list <ul>, but don't output empty list.
    if (!empty($section->sequence)) {

        // Fix bug #5027, don't want style=\"width:$width\".
        echo "<ul class=\"section img-text\">\n";
        $sectionmods = explode(",", $section->sequence);

        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }

            $mod = $mods[$modnumber];

            if ($ismoving and $mod->id == $USER->activitycopy) {
                // do not display moving mod
                continue;
            }

            if (isset($modinfo->cms[$modnumber])) {
                // We can continue (because it will not be displayed at all)
                // if:
                // 1) The activity is not visible to users
                // and
                // 2a) The 'showavailability' option is not set (if that is set,
                //     we need to display the activity so we can show
                //     availability info)
                // or
                // 2b) The 'availableinfo' is empty, i.e. the activity was
                //     hidden in a way that leaves no info, such as using the
                //     eye icon.
                if (!$modinfo->cms[$modnumber]->uservisible &&
                    (empty($modinfo->cms[$modnumber]->showavailability) ||
                      empty($modinfo->cms[$modnumber]->availableinfo))) {
                    // visibility shortcut
                    continue;
                }
            } else {
                if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                    // module not installed
                    continue;
                }
                if (!coursemodule_visible_for_user($mod) &&
                    empty($mod->showavailability)) {
                    // full visibility check
                    continue;
                }
            }
            $isAdaptation = false;
            
            if(($mod->modname!='adaptable')){
            	$isAdaptation = adaptable_format_get_adaptable_resource_relation($mod->id);
            }
            
            if($isAdaptation && (!has_capability('format/adaptable:viewadaptables', get_context_instance(CONTEXT_MODULE, $mod->id)) && !has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)))){
            	continue;
            }

            // In some cases the activity is visible to user, but it is
            // dimmed. This is done if viewhiddenactivities is true and if:
            // 1. the activity is not visible, or
            // 2. the activity has dates set which do not include current, or
            // 3. the activity has any other conditions set (regardless of whether
            //    current user meets them)
            $canviewhidden = has_capability(
                'moodle/course:viewhiddenactivities',
                get_context_instance(CONTEXT_MODULE, $mod->id));
            $accessiblebutdim = false;
            if ($canviewhidden) {
                $accessiblebutdim = !$mod->visible;
                if (!empty($CFG->enableavailability)) {
                    $accessiblebutdim = $accessiblebutdim ||
                        $mod->availablefrom > time() ||
                        ($mod->availableuntil && $mod->availableuntil < time()) ||
                        count($mod->conditionsgrade) > 0 ||
                        count($mod->conditionscompletion) > 0;
                }
            }
            //$accessiblebutdim = $accessiblebutdim || $isAdaptation;

            $liclasses = array();
            $liclasses[] = 'activity';
            $liclasses[] = $mod->modname;
            $liclasses[] = 'modtype_'.$mod->modname;
            echo html_writer::start_tag('li', array('class'=>join(' ', $liclasses), 'id'=>'module-'.$modnumber));
            if ($ismoving) {
                echo '<a title="'.$strmovefull.'"'.
                     ' href="'.$CFG->wwwroot.'/course/mod.php?moveto='.$mod->id.'&amp;sesskey='.sesskey().'">'.
                     '<img class="movetarget" src="'.$OUTPUT->pix_url('movehere') . '" '.
                     ' alt="'.$strmovehere.'" /></a><br />
                     ';
            }

            $classes = array('mod-indent');
            if (!empty($mod->indent)) {
                $classes[] = 'mod-indent-'.$mod->indent;
                if ($mod->indent > 15) {
                    $classes[] = 'mod-indent-huge';
                }
            }
            echo html_writer::start_tag('div', array('class'=>join(' ', $classes)));

            $extra = '';
            if (!empty($modinfo->cms[$modnumber]->extra)) {
                $extra = $modinfo->cms[$modnumber]->extra;
            }

            if ($mod->modname == "label") {
                if ($accessiblebutdim || !$mod->uservisible) {
                    echo '<div class="dimmed_text"'.(($isAdaptation)?' style="color: #AAAAAA;" ':'').'><span class="accesshide"'.(($isAdaptation)?' style="color: #AAAAAA;" ':'').'>'.
                        get_string('hiddenfromstudents').'</span>';
                } else {
                    echo '<div>';
                }
                echo format_text($extra, FORMAT_HTML, $labelformatoptions);
                echo ($isAdaptation?get_string('isAdaptation', 'format_adaptable', $isAdaptation):'')."</div>";
                if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    if (!isset($groupings)) {
                        $groupings = groups_get_all_groupings($course->id);
                    }
                    echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                }

            } else { // Normal activity
                $instancename = format_string($modinfo->cms[$modnumber]->name, true,  $course->id);

                $customicon = $modinfo->cms[$modnumber]->icon;
                if (!empty($customicon)) {
                    if (substr($customicon, 0, 4) === 'mod/') {
                        list($modname, $iconname) = explode('/', substr($customicon, 4), 2);
                        $icon = $OUTPUT->pix_url($iconname, $modname);
                    } else {
                        $icon = $OUTPUT->pix_url($customicon);
                    }
                } else {
                    $icon = $OUTPUT->pix_url('icon', $mod->modname);
                }

                //Accessibility: for files get description via icon, this is very ugly hack!
                $altname = '';
                $altname = $mod->modfullname;
                if (!empty($customicon)) {
                    $archetype = plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                    if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                        $mimetype = mimeinfo_from_icon('type', $customicon);
                        $altname = get_mimetype_description($mimetype);
                    }
                }
                // Avoid unnecessary duplication.
                if (false !== stripos($instancename, $altname)) {
                    $altname = '';
                }
                // File type after name, for alphabetic lists (screen reader).
                if ($altname) {
                    $altname = get_accesshide(' '.$altname);
                }

                // We may be displaying this just in order to show information
                // about visibility, without the actual link
                if ($mod->uservisible) {
                    // Display normal module link
                    if (!$accessiblebutdim) {
                        $linkcss = (($isAdaptation)?' style="color: #AAAAAA;" ':'');
                        $accesstext  ='';
                    } else {
                        $linkcss = ' class="dimmed" '.(($isAdaptation)?' style="color: #AAAAAA;" ':'');
                        $accesstext = '<span class="accesshide">'.
                            get_string('hiddenfromstudents').': </span>';
                    }

                    echo '<a '.$linkcss.' '.$extra.
                         ' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.
                         '<img src="'.$icon.'" class="activityicon" alt="'.get_string('modulename',$mod->modname).'" /> '.
                         $accesstext.'<span class="instancename">'.$instancename.$altname.($isAdaptation?get_string('isAdaptation', 'format_adaptable', $isAdaptation):'').'</span></a>';

                    if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        if (!isset($groupings)) {
                            $groupings = groups_get_all_groupings($course->id);
                        }
                        echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                    }
                } else {
                    // Display greyed-out text of link
                    echo '<span class="dimmed_text'.(($isAdaptation)?' is_associated':'').'" '.$extra.' ><span class="accesshide">'.
                        get_string('notavailableyet','condition').': </span>'.
                        '<img src="'.$icon.'" class="activityicon" alt="'.get_string('modulename', $mod->modname).'" /> <span>'.
                        $instancename.$altname.($isAdaptation?get_string('isAdaptation', 'format_adaptable', $isAdaptation):'').'</span></span>';
                }
            }
            if ($usetracking && $mod->modname == 'forum') {
                if ($unread = forum_tp_count_forum_unread_posts($mod, $course)) {
                    echo '<span class="unread"> <a href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$mod->id.'">';
                    if ($unread == 1) {
                        echo $strunreadpostsone;
                    } else {
                        print_string('unreadpostsnumber', 'forum', $unread);
                    }
                    echo '</a></span>';
                }
            }

            if ($isediting) {
                if ($groupbuttons and plugin_supports('mod', $mod->modname, FEATURE_GROUPS, 0)) {
                    if (! $mod->groupmodelink = $groupbuttonslink) {
                        $mod->groupmode = $course->groupmode;
                    }

                } else {
                    $mod->groupmode = false;
                }
                echo '&nbsp;&nbsp;';

                echo make_editing_buttons($mod, $absolute, true, $mod->indent, $section->section);
            }

            // Completion
            $completion = $hidecompletion
                ? COMPLETION_TRACKING_NONE
                : $completioninfo->is_enabled($mod);
            if ($completion!=COMPLETION_TRACKING_NONE && isloggedin() &&
                !isguestuser() && $mod->uservisible) {
                $completiondata = $completioninfo->get_data($mod,true);
                $completionicon = '';
                if ($isediting) {
                    switch ($completion) {
                        case COMPLETION_TRACKING_MANUAL :
                            $completionicon = 'manual-enabled'; break;
                        case COMPLETION_TRACKING_AUTOMATIC :
                            $completionicon = 'auto-enabled'; break;
                        default: // wtf
                    }
                } else if ($completion==COMPLETION_TRACKING_MANUAL) {
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'manual-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'manual-y'; break;
                    }
                } else { // Automatic
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'auto-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'auto-y'; break;
                        case COMPLETION_COMPLETE_PASS:
                            $completionicon = 'auto-pass'; break;
                        case COMPLETION_COMPLETE_FAIL:
                            $completionicon = 'auto-fail'; break;
                    }
                }
                if ($completionicon) {
                    $imgsrc = $OUTPUT->pix_url('i/completion-'.$completionicon);
                    $imgalt = s(get_string('completion-alt-'.$completionicon, 'completion'));
                    if ($completion == COMPLETION_TRACKING_MANUAL && !$isediting) {
                        $imgtitle = s(get_string('completion-title-'.$completionicon, 'completion'));
                        $newstate =
                            $completiondata->completionstate==COMPLETION_COMPLETE
                            ? COMPLETION_INCOMPLETE
                            : COMPLETION_COMPLETE;
                        // In manual mode the icon is a toggle form...

                        // If this completion state is used by the
                        // conditional activities system, we need to turn
                        // off the JS.
                        if (!empty($CFG->enableavailability) &&
                            condition_info::completion_value_used_as_condition($course, $mod)) {
                            $extraclass = ' preventjs';
                        } else {
                            $extraclass = '';
                        }
                        echo "
<form class='togglecompletion$extraclass' method='post' action='togglecompletion.php'><div>
<input type='hidden' name='id' value='{$mod->id}' />
<input type='hidden' name='sesskey' value='".sesskey()."' />
<input type='hidden' name='completionstate' value='$newstate' />
<input type='image' src='$imgsrc' alt='$imgalt' title='$imgtitle' />
</div></form>";
                    } else {
                        // In auto mode, or when editing, the icon is just an image
                        echo "<span class='autocompletion'>";
                        echo "<img src='$imgsrc' alt='$imgalt' title='$imgalt' /></span>";
                    }
                }
            }

            // Show availability information (for someone who isn't allowed to
            // see the activity itself, or for staff)
            if (!$mod->uservisible) {
                echo '<div class="availabilityinfo">'.$mod->availableinfo.'</div>';
            } else if ($canviewhidden && !empty($CFG->enableavailability)) {
                $ci = new condition_info($mod);
                $fullinfo = $ci->get_full_information();
                if($fullinfo) {
                    echo '<div class="availabilityinfo">'.get_string($mod->showavailability
                        ? 'userrestriction_visible'
                        : 'userrestriction_hidden','condition',
                        $fullinfo).'</div>';
                }
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li')."\n";
        }

    } elseif ($ismoving) {
        echo "<ul class=\"section\">\n";
    }

    if ($ismoving) {
        echo '<li><a title="'.$strmovefull.'"'.
             ' href="'.$CFG->wwwroot.'/course/mod.php?movetosection='.$section->id.'&amp;sesskey='.sesskey().'">'.
             '<img class="movetarget" src="'.$OUTPUT->pix_url('movehere') . '" '.
             ' alt="'.$strmovehere.'" /></a></li>
             ';
    }
    if (!empty($section->sequence) || $ismoving) {
        echo "</ul><!--class='section'-->\n\n";
    }
}

/**
 * Verify if a resource is associated with an adaptable
 * 
 * @param integer $id
 * @return boolean true if it is, false otherwise
 */
function adaptable_format_is_adaptation($id){
	global $DB;
	
	return $DB->record_exists('adaptable_relations', array('resource_id'=>$id));
}

/**
 * Get the resource associated with an adaptable
 * 
 * @param integer $id
 * @return boolean true if it is, false otherwise
 */
function adaptable_format_get_adaptable_resource_relation($id){
	global $DB;
	
	if($record = $DB->get_record('adaptable_relations', array('resource_id'=>$id))){
		$record->adaptable_name = $DB->get_field('adaptable', 'name ', array('id'=>$record->adaptable_id));
		return $record;
	}
	
	return false;
}