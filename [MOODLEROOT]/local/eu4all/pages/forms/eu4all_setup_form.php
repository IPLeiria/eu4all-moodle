<?php
/**
 * Common setup form for the EU4ALL components
 *
 * @package    	EU4ALL
 * @subpackage 	local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/eu4all/lib.php');
require_once($CFG->dirroot.'/local/eu4all/lib/umlib.php');
require_once($CFG->dirroot.'/local/eu4all/lib/mrlib.php');

class eu4all_setup_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        
        $config = get_config(EU4ALL_PLUGINNAME);
        $mform->addElement('header', 'about', get_string('about', EU4ALL_PLUGINNAME));
		$mform->addElement('html', '<div>'.get_string('usingPluginVersion', EU4ALL_PLUGINNAME, $config->version).'</div>');
		
		if($version = EU4ALL_UserModel::getVersion()){
			$mform->addElement('html', '<div>'.get_string('remoteUserModelVersion', EU4ALL_PLUGINNAME, $version).'</div>');
		}
		
    	if($version = EU4ALL_MetadataRepository::serviceInformation()){
			$mform->addElement('html', '<div>'.get_string('remoteMetadataRepositoryInformation', EU4ALL_PLUGINNAME, $version).'</div>');
		}
		
        $mform->addElement('header', 'generalsettings', get_string('generalSettings', EU4ALL_PLUGINNAME));
        $mform->addElement('text', 'eu4all_manager_reference', get_string('eu4allManagerReference', EU4ALL_PLUGINNAME));
        $mform->setType('eu4all_manager_reference', PARAM_ALPHANUMEXT);
        $mform->addRule('eu4all_manager_reference', null, 'required', null);
        $mform->setDefault('eu4all_manager_reference', (isset($config->eu4all_manager_reference)?$config->eu4all_manager_reference:'IPL_Moodle'));
        
        $mform->addElement('header', 'usermodel', get_string('userModel', EU4ALL_PLUGINNAME));
        $mform->addElement('text', 'eu4all_um_wsdl_url', get_string('eu4allUmWsdlUrl', EU4ALL_PLUGINNAME), array('size'=>'250'));
        $mform->setType('eu4all_um_wsdl_url', PARAM_URL);
        $mform->addRule('eu4all_um_wsdl_url', get_string('eu4allUmWsdlUrlEmptyURL', EU4ALL_PLUGINNAME), 'required', null);
        $mform->setDefault('eu4all_um_wsdl_url', (isset($config->eu4all_um_wsdl_url)?$config->eu4all_um_wsdl_url:'http://eu4all.adenu.ia.uned.es:8000/axis2/services/UserModelService?wsdl'));
        
        
        $mform->addElement('header', 'contentpersonalization', get_string('contentPersonalization', EU4ALL_PLUGINNAME));
        $mform->addElement('text', 'eu4all_cp_wsdl_url', get_string('eu4allCpWsdlUrl', EU4ALL_PLUGINNAME), array('size'=>'250'));
        $mform->setType('eu4all_cp_wsdl_url', PARAM_URL);
        $mform->addRule('eu4all_cp_wsdl_url', get_string('eu4allCpWsdlUrlEmptyURL', EU4ALL_PLUGINNAME), 'required', null);
        $mform->setDefault('eu4all_cp_wsdl_url', (isset($config->eu4all_cp_wsdl_url)?$config->eu4all_cp_wsdl_url:'http://einclusion-projects.com:8080/EU4ALL/IPL/CPv3/services/personalizeResourceService?wsdl'));
        
        // MR
        $mform->addElement('header', 'metadatarepository', get_string('metadataRepository', EU4ALL_PLUGINNAME));
        $mform->addElement('text', 'eu4all_mr_wsdl_url', get_string('eu4allMrWsdlUrl', EU4ALL_PLUGINNAME), array('size'=>'250'));
        $mform->setType('eu4all_mr_wsdl_url', PARAM_URL);
        $mform->addRule('eu4all_mr_wsdl_url', get_string('eu4allCpWsdlUrlEmptyURL', EU4ALL_PLUGINNAME), 'required', null);
        $mform->setDefault('eu4all_mr_wsdl_url', (isset($config->eu4all_mr_wsdl_url)?$config->eu4all_mr_wsdl_url:'http://eu4all.atosorigin.es/eu4all/services/LOMRServices?wsdl'));
        
        $mform->addElement('text', 'eu4all_mr_username', get_string('eu4allMrUsername', EU4ALL_PLUGINNAME));
        $mform->setDefault('eu4all_mr_username', (isset($config->eu4all_mr_username)?$config->eu4all_mr_username:'admin'));
        
        $mform->addElement('passwordunmask', 'eu4all_mr_password', get_string('eu4allMrPassword', EU4ALL_PLUGINNAME));
        $mform->setDefault('eu4all_mr_password', (isset($config->eu4all_mr_password)?$config->eu4all_mr_password:'admin'));
        
        $mform->addElement('text', 'eu4all_resource_prefix', get_string('eu4allResourcePrefix', EU4ALL_PLUGINNAME));
        $mform->setType('eu4all_resource_prefix', PARAM_ALPHANUMEXT);
        $mform->setDefault('eu4all_resource_prefix', (isset($config->eu4all_resource_prefix)?$config->eu4all_resource_prefix:'IPL_'));
        $mform->addHelpButton("eu4all_resource_prefix", "eu4allResourcePrefix", EU4ALL_PLUGINNAME);
        
        $this->add_action_buttons(false, get_string('save', EU4ALL_PLUGINNAME));
    }
    
	function validation($data, $files) {
		global $CFG;
        $errors= array();
        
		$errors = parent::validation($data, $files);

		if(!EU4ALL_UserModel::getVersion($data['eu4all_um_wsdl_url'])){
			$errors['eu4all_um_wsdl_url'] = get_string('eu4allUmWsdlUrlInvalid', EU4ALL_PLUGINNAME);
		}
		
		if(!EU4ALL_MetadataRepository::serviceInformation($data['eu4all_mr_wsdl_url'])){
			$errors['eu4all_mr_wsdl_url'] = get_string('eu4allMrWsdlUrlInvalid', EU4ALL_PLUGINNAME);
		}
        
        return $errors;
    }
}
