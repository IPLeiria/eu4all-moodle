<?php

/**
 * List of all adaptable in course
 *
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'adaptable', 'view all', "index.php?id=$course->id", '');

$strresource     = get_string('modulename', 'adaptable');
$strresources    = get_string('modulenameplural', 'adaptable');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name', 'adaptable');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/adaptable/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strresources);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strresources);
echo $OUTPUT->header();

if (!$adaptables = get_all_instances_in_course('adaptable', $course)) {
    notice(get_string('thereareno', 'moodle', $strresources), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($adaptables as $adaptable) {
    $cm = $modinfo->cms[$adaptable->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($adaptable->section !== $currentsection) {
            if ($adaptable->section) {
                $printsection = get_section_name($course, $sections[$adaptable->section]);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $adaptable->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($adaptable->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // each resource file has an icon in 2.0
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon).'" class="activityicon" alt="" /> ';
    }

    $class = $adaptable->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($adaptable->name)."</a>",
        format_module_intro('resource', $adaptable, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
