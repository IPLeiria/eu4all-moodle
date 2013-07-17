<?php

/**
 * Adaptable module post install function
 *
 * This file replaces:
 *  - STATEMENTS section in db/install.xml
 *  - lib.php/modulename_install() post installation hook
 *  - partially defaults.php
 *
 * @package   mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>, Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_adaptable_install() {
    global $DB;

    // Install logging support
    update_log_display_entry('adaptable', 'view', 'adaptable', 'name');
    update_log_display_entry('adaptable', 'view all', 'adaptable', 'name');
    update_log_display_entry('adaptable', 'update', 'adaptable', 'name');
    update_log_display_entry('adaptable', 'add', 'adaptable', 'name');

}
