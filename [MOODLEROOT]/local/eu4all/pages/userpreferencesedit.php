<?php
/**
 * User preferences setup page for the EU4ALL user model component
 *
 * @package    	UM, EU4ALL
 * @subpackage 	local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once('./forms/um_user_preferences_edit_form.php');
require_once($CFG->dirroot.'/local/eu4all/lib/umlib.php');

define('EU4ALLMODULENAME', 'local_eu4all');

$url = new moodle_url('/local/eu4all/pages/userpreferencesedit.php');
$returnurl = new moodle_url('/local/eu4all/pages/userpreferences.php', array('id'=>$USER->id));
// user id
$userid = optional_param('id', $USER->id, PARAM_INT);
if($userid !== $USER->id){
	$url->param('id', $userid);
}
$PAGE->set_url($url);

// authentication check
if(!isloggedin()){
	if(empty($SESSION->wantsurl)){
		$SESSION->wantsurl = $url;
	}
	redirect(get_login_url());
}

// guest account check
if(isguestuser()){
	print_error('guestCannotSetPreferencesError', EU4ALLMODULENAME);
}

// user account check
if (!$user = $DB->get_record('user', array('id' => $userid))){
	print_error('invalidUserId', EU4ALLMODULENAME);
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$personalcontext = get_context_instance(CONTEXT_USER, $user->id);

$PAGE->set_context($personalcontext);

// check access control
if ($user->id == $USER->id) {
    //editing own message profile
    require_capability('local/eu4all:umeditownprofile', $systemcontext);
} else {
    // teachers, parents, etc.
    require_capability('local/eu4all:umeditownprofile', $personalcontext);
    // no editing of guest user account
    if (isguestuser($user->id)) {
        print_error('guestCannotSetPreferencesError', EU4ALLMODULENAME);
    }
    // no editing of admins by non admins!
    if (is_siteadmin($user) and !is_siteadmin($USER)) {
        print_error('useradmineditadmin');
    }
    $PAGE->navigation->extend_for_user($user);
}

// form processing
$form = new um_user_preferences_edit_form(EU4ALL_UserModel::getUserModelAttributes($USER->username));
if ($form->is_cancelled()){
    redirect($returnurl);
} else if ($fdata = $form->get_data()) {
	if(isset($fdata->submitbutton)){
		if($profileData = um_user_preferences_edit_form::getProfileData($fdata)){
			if(!EU4ALL_UserModel::getPersonalUserData($user->username)){
				// Register the user account (if it doesn't exists)
				EU4ALL_UserModel::sendRegistrationEvent($user->username);
			}
			if(!EU4ALL_UserModel::updateUserModel($USER->username, $profileData)){
				print_error('profileDataSaveFailed', EU4ALLMODULENAME);
			}
			$PAGE->set_title(get_string('profileDataSaved', EU4ALLMODULENAME));
			redirect($returnurl, get_string('profileDataSaved', EU4ALLMODULENAME));
		}else{
			print_error('errorOnProfileDataStructure', EU4ALLMODULENAME);
		}
	}
}

// output layout
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('viewAccessibilityPreferences', EU4ALLMODULENAME));
$PAGE->set_title(get_string('viewAccessibilityPreferences', EU4ALLMODULENAME));
echo($OUTPUT->header());
$form->setFormData();
$form->display();
echo($OUTPUT->footer());
