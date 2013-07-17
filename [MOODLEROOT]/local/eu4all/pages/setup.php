<?php
/**
 * Common setup page for the EU4ALL components
 *
 * @package    	EU4ALL
 * @subpackage 	local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('./forms/eu4all_setup_form.php');
require_once($CFG->dirroot.'/local/eu4all/lib.php');

admin_externalpage_setup('eu4all_config');

global $USER;

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
if(!has_capability('local/eu4all:configureplugin', $systemcontext, $USER) && !is_siteadmin($USER)):
	print_error('insuficientPermissionsToConfigureTheEu4allPlugin', EU4ALLMODULENAME);
endif;

$form = new eu4all_setup_form();

// Otherwise display the settings form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('eu4allConfiguration', EU4ALL_PLUGINNAME));
if ($data = $form->get_data()):
	try{
		if(isset($data->eu4all_manager_reference)):
			set_config('eu4all_manager_reference', $data->eu4all_manager_reference, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_um_wsdl_url)):
			set_config('eu4all_um_wsdl_url', $data->eu4all_um_wsdl_url, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_cp_wsdl_url)):
			set_config('eu4all_cp_wsdl_url', $data->eu4all_cp_wsdl_url, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_mr_wsdl_url)):
			set_config('eu4all_mr_wsdl_url', $data->eu4all_mr_wsdl_url, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_mr_username)):
			set_config('eu4all_mr_username', $data->eu4all_mr_username, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_mr_password)):
			set_config('eu4all_mr_password', $data->eu4all_mr_password, EU4ALL_PLUGINNAME);
		endif;
		if(isset($data->eu4all_resource_prefix)):
			set_config('eu4all_resource_prefix', $data->eu4all_resource_prefix, EU4ALL_PLUGINNAME);
		endif;
		
	    echo($OUTPUT->notification(get_string('eu4allSettingsSaved', EU4ALL_PLUGINNAME), 'notifysuccess'));
	}catch(Exception $ex){
		echo($OUTPUT->notification(get_string('eu4allErrorSavingSettings', EU4ALL_PLUGINNAME, $ex->getMessage()), 'notifyproblem'));
	}
endif;
$form->display();

echo $OUTPUT->footer();