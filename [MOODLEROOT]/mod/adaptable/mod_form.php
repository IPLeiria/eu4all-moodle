<?php


/**
 * Adaptable configuration form
 *
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/adaptable/locallib.php');

class mod_adaptable_mod_form extends moodleform_mod {
	private $externalData=NULL;
	
	public function __construct(&$data, $section, $cm, $course){
		global $DB;
		
		$this->externalData = &$data;
		self::getData($data->instance, $this->externalData);
		
		parent::__construct($data, $section, $cm, $course);
	}
	/**
	 * Using the SimpleXMLElement, apply a XPath string to return a valid array of options
	 * @param SimpleXMLElement $xml
	 * @param string $xpath
	 * @param string $baseLocaleString
	 * @return array <string, string>
	 */
	private static function _getDataFromXml(SimpleXMLElement $xml, $xpath='', $baseLocaleString=''){
		$result = array();
		
		if($xml && method_exists($xml, 'xpath') && !empty($xpath) && !empty($baseLocaleString)){
			if($nodes = $xml->xpath($xpath)){
				foreach($nodes as $node){
					foreach($node->attributes() as $name=>$value){
						if(strtolower($name)=='value'){
							$result["$value"] = get_string($baseLocaleString."$value", 'adaptable');
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * Get the first key of an array
	 * 
	 * @param array $array
	 * @return mixed with the key or boolean false on error
	 */
	private static function _getFirstArrayKey($array){
		$keys = array_keys($array);
		return (isset($keys[0])?$keys[0]:false);
	}
	
	/**
	 * Form definition
	 */
    function definition() {
        global $CFG, $DB;
        
        $mform =& $this->_form;
        
    	if(!$postData = &data_submitted()){
        	$postData = $this->externalData;
        }
        
        $config = get_config('adaptable');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
		$this->add_intro_editor(isset($config->requiremodintro)?$config->requiremodintro:false, get_string('intro', 'adaptable'));
		
        //-------------------------------------------------------
        
        $mform->addElement('header', 'defaultresourceheader', get_string('defaultresourceheader', 'adaptable'));
       	
        $resources = adaptable_get_all_course_resources($this->current->course, !empty($postData->instance)?$postData->instance:true);
        $resourcesUsed = $resources;
        
    	// load the content types vocabulary from the xsd
        $xml = simpleXML_load_file($CFG->dirroot.'/local/eu4all/lib/xsd/eu4all-isoafa-vocab.xsd'); 
		if($xml!==false){
			$adaptationTypes = self::_getDataFromXml($xml, '//xsd:simpleType[@name="Adaptation_Type_Vocabulary"]/xsd:restriction/xsd:enumeration', 'adaptationType');
			$accessModes = self::_getDataFromXml($xml, '//xsd:simpleType[@name="Access_Mode_Vocabulary"]/xsd:restriction/xsd:enumeration', 'accessMode');
			$representationForms = array_merge(array('void'=>get_string("representationFormVoid", 'adaptable')), self::_getDataFromXml($xml, '//xsd:simpleType[@name="Representation_Form_Vocabulary"]/xsd:restriction/xsd:enumeration', 'representationForm'));
			
			$selectsData = array('adaptationTypes'=>$adaptationTypes,'accessModes'=>$accessModes,'representationForms'=>$representationForms);
		}else{
			$selectsData=null;
		}
		
        // default resource
        if(count($resources)>0){
	        $mform->addElement('select', 'defaultResourceId', get_string('defaultResource', 'adaptable'), $resources);
	        $mform->addHelpButton('defaultResourceId', 'defaultResource', 'adaptable');
	        $mform->addRule('defaultResourceId', null, 'required', null);
	    	if(isset($postData->defaultResourceId)){
	        	// restore previous state
	        	if(isset($_POST["defaultResourceId"])){
	        		$_POST["defaultResourceId"] = $postData->defaultResourceId;
	        	}
	        	$mform->setDefault("defaultResourceId", $postData->defaultResourceId);
	        	// remove the used item from the array
	        	unset($resourcesUsed[$postData->defaultResourceId]);
	        }else{
	        	$key = self::_getFirstArrayKey($resourcesUsed);
	        	$mform->setDefault("defaultResourceId", $key);
	        	// remove the used item from the array
	        	unset($resourcesUsed[$key]);
	        }
        
	        // original mode
	        $mform->addElement('select', 'defaultResourceOriginalMode', get_string('defaultResourceOriginalMode', 'adaptable'), $selectsData['accessModes']);
	        $mform->addHelpButton('defaultResourceOriginalMode', 'defaultResourceOriginalMode', 'adaptable');
	        $mform->addRule('defaultResourceOriginalMode', null, 'required', null);
	    	if(isset($postData->defaultResourceOriginalMode)){
	        	// restore previous state
	        	if(isset($_POST["defaultResourceOriginalMode"])){
	        		$_POST["defaultResourceOriginalMode"] = $postData->defaultResourceOriginalMode;
	        	}
	        	$mform->setDefault("defaultResourceOriginalMode", $postData->defaultResourceOriginalMode);
	        }
	        
	        // original content type
	        $mform->addElement('text', 'defaultResourceOriginalContentType', get_string('defaultResourceOriginalContentType', 'adaptable'));
	        $mform->addHelpButton('defaultResourceOriginalContentType', 'defaultResourceOriginalContentType', 'adaptable');
	        $mform->setType('defaultResourceOriginalContentType', PARAM_TAGLIST);
	        $mform->addRule('defaultResourceOriginalContentType', null, 'required', null);
	        $mform->setDefault('defaultResourceOriginalContentType', (isset($postData->defaultResourceOriginalContentType)?$postData->defaultResourceOriginalContentType:''));
	        
	        // alternatives
	    	$a=1;
			if(!empty($postData) && !empty($selectsData)){
		        foreach($postData as $key=>$value){
		        	if(preg_match("/resourceAlternative\d+$/", $key) && !isset($postData->{$key."Remove"})){
		        		$mform->addElement('hidden', "resourceAlternative{$a}");
				        $mform->setType("resourceAlternative{$a}", PARAM_INT);
				        $mform->setDefault("resourceAlternative{$a}", $a);
				        
				        $mform->addElement('header', "resourceAlternative{$a}Header", get_string('resourceAlternative', 'adaptable', $a));
				        
		        		// Alternative resource
				        $mform->addElement('select', "resourceAlternative{$a}Resource", get_string('resourceAlternativeResource', 'adaptable'), $resources);
				        $mform->addHelpButton("resourceAlternative{$a}Resource", 'resourceAlternativeResource', 'adaptable');
	        			$mform->addRule("resourceAlternative{$a}Resource", null, 'required', null);
				        if(isset($postData->{$key."Resource"})){
				        	// restore previous state
				        	if(isset($_POST["resourceAlternative{$a}Resource"])){
				        		$_POST["resourceAlternative{$a}Resource"] = $postData->{$key."Resource"};
				        	}
				        	$mform->setDefault("resourceAlternative{$a}Resource", $postData->{$key."Resource"});
				        	
				        	// remove the used item from the array
				        	unset($resourcesUsed[$postData->{$key."Resource"}]);
				        }else{
				        	$key = self::_getFirstArrayKey($resourcesUsed);
				        	$mform->setDefault("resourceAlternative{$a}Resource", $key);
				        	// remove the used item from the array
				        	unset($resourcesUsed[$key]);
				        }
				        
				        // Original mode
				        $mform->addElement('select', "resourceAlternative{$a}OriginalMode", get_string('resourceAlternativeOriginalMode', 'adaptable'), $selectsData['accessModes']);
				        $mform->addHelpButton("resourceAlternative{$a}OriginalMode", 'resourceAlternativeOriginalMode', 'adaptable');
	        			$mform->addRule("resourceAlternative{$a}OriginalMode", null, 'required', null);
				        if(isset($postData->{$key."OriginalMode"})){
				        	// restore previous state
				        	if(isset($_POST["resourceAlternative{$a}OriginalMode"])){
				        		$_POST["resourceAlternative{$a}OriginalMode"] = $postData->{$key."OriginalMode"};
				        	}
				        	$mform->setDefault("resourceAlternative{$a}OriginalMode", $postData->{$key."OriginalMode"});
				        }
				        
				        // Adaptation type
				        $mform->addElement('select', "resourceAlternative{$a}AdaptationType", get_string('resourceAlternativeAdaptationType', 'adaptable'), $selectsData['adaptationTypes']);
				        $mform->addHelpButton("resourceAlternative{$a}AdaptationType", 'resourceAlternativeAdaptationType', 'adaptable');
	        			$mform->addRule("resourceAlternative{$a}AdaptationType", null, 'required', null);
				        if(isset($postData->{$key."AdaptationType"})){
				        	// restore previous state
				        	if(isset($_POST["resourceAlternative{$a}AdaptationType"])){
				        		$_POST["resourceAlternative{$a}AdaptationType"] = $postData->{$key."AdaptationType"};
				        	}
				        	$mform->setDefault("resourceAlternative{$a}AdaptationType", $postData->{$key."AdaptationType"});
				        }
				        
				        // Representation form
				        $mform->addElement('select', "resourceAlternative{$a}RepresentationForm", get_string('resourceAlternativeRepresentationForm', 'adaptable'), $selectsData['representationForms']);
				        $mform->addHelpButton("resourceAlternative{$a}RepresentationForm", 'resourceAlternativeRepresentationForm', 'adaptable');
	        			$mform->addRule("resourceAlternative{$a}RepresentationForm", null, 'required', null);
				        if(isset($postData->{$key."RepresentationForm"})){
				        	// restore previous state
				        	if(isset($_POST["resourceAlternative{$a}RepresentationForm"])){
				        		$_POST["resourceAlternative{$a}RepresentationForm"] = $postData->{$key."RepresentationForm"};
				        	}
				        	$mform->setDefault("resourceAlternative{$a}RepresentationForm", $postData->{$key."RepresentationForm"});
				        }
				        
				        // remove block
				        $mform->addElement('submit', "resourceAlternative{$a}Remove", get_string("resourceAlternativeRemove", 'adaptable', $a));
				        
				        $a++;
		        		
		        	}
		        }
		        
		        // add a new block
		        if(isset($postData->{"resourceAlternativeAdd"}) && count($resourcesUsed)>0){
		        	$mform->addElement('hidden', "resourceAlternative{$a}");
			        $mform->setType("resourceAlternative{$a}", PARAM_INT);
			        $mform->setDefault("resourceAlternative{$a}", $a);
			        $mform->addElement('header', "resourceAlternative{$a}Header", get_string('resourceAlternative', 'adaptable', $a));
			        
			        // alternative resource
			        $mform->addElement('select', "resourceAlternative{$a}Resource", get_string('resourceAlternativeResource', 'adaptable'), $resources);
			        $mform->addHelpButton("resourceAlternative{$a}Resource", 'resourceAlternativeResource', 'adaptable');
			        $mform->addRule("resourceAlternative{$a}Resource", null, 'required', null);
			        $mform->setType("resourceAlternative{$a}Resource", PARAM_INT);
		        	$key = self::_getFirstArrayKey($resourcesUsed);
	        		$_POST["resourceAlternative{$a}Resource"] = $key;
		        	$mform->setDefault("resourceAlternative{$a}Resource", $key);
		        	// remove the used item from the array
		        	unset($resourcesUsed[$key]);
			        
			        // Original mode
			        $mform->addElement('select', "resourceAlternative{$a}OriginalMode", get_string('resourceAlternativeOriginalMode', 'adaptable'), $selectsData['accessModes']);
			        $mform->addHelpButton("resourceAlternative{$a}OriginalMode", 'resourceAlternativeOriginalMode', 'adaptable');
	        		$mform->addRule("resourceAlternative{$a}OriginalMode", null, 'required', null);
	        		$key = self::_getFirstArrayKey($selectsData['accessModes']);
	        		$_POST["resourceAlternative{$a}OriginalMode"] = $key;
		        	$mform->setDefault("resourceAlternative{$a}OriginalMode", $key);
			        
			        // Adaptation type
			        $mform->addElement('select', "resourceAlternative{$a}AdaptationType", get_string('resourceAlternativeAdaptationType', 'adaptable'), $selectsData['adaptationTypes']);
			        $mform->addHelpButton("resourceAlternative{$a}AdaptationType", 'resourceAlternativeAdaptationType', 'adaptable');
	        		$mform->addRule("resourceAlternative{$a}AdaptationType", null, 'required', null);
	        		$key = self::_getFirstArrayKey($selectsData['adaptationTypes']);
	        		$_POST["resourceAlternative{$a}AdaptationType"] = $key;
		        	$mform->setDefault("resourceAlternative{$a}AdaptationType", $key);
			        
			        // Representation form
			        $mform->addElement('select', "resourceAlternative{$a}RepresentationForm", get_string('resourceAlternativeRepresentationForm', 'adaptable'), $selectsData['representationForms']);
			        $mform->addHelpButton("resourceAlternative{$a}RepresentationForm", 'resourceAlternativeRepresentationForm', 'adaptable');
	        		$mform->addRule("resourceAlternative{$a}RepresentationForm", null, 'required', null);
	        		$key = self::_getFirstArrayKey($selectsData['representationForms']);
	        		$_POST["resourceAlternative{$a}RepresentationForm"] = $key;
		        	$mform->setDefault("resourceAlternative{$a}RepresentationForm", $key);
			        
			        // remove block
			        $mform->addElement('submit', "resourceAlternative{$a}Remove", get_string("resourceAlternativeRemove", 'adaptable', $a));
				    
			        $a++;
		        }
			}
	     	
	        // new alternative
	        if(count($resourcesUsed)>0){
		        $mform->addElement('header', 'addResourceAlternativeHeader', get_string('addResourceAlternativeHeader', 'adaptable'));
		        $mform->addElement('submit', "resourceAlternativeAdd", get_string("resourceAlternativeAdd", 'adaptable'));
	        }
	        
        }else{
        	$mform->addElement('static', 'staticDefaultResourceId', get_string('defaultResource', 'adaptable'), '<strong>'.get_string('err_atLeastAnAssociatedResourceMustExist', 'adaptable').'</strong>');
        }
        
        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     * Data preprocessing method
     * @param $default_values
     */
    function data_preprocessing(&$default_values) {
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $default_values['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
        parent::data_preprocessing($default_values);   
    }
    
	/**
     * Load the adaptable_relations data for the instance id
     *
     * @param int with the id of the module
     */
    public static function getData($id, &$defaultValues=NULL) {
    	global $DB;
    	if (!is_object($defaultValues)) {
    		$defaultValues = new stdClass();
    		$defaultValues->instance = $id;
    	}
        if($adaptableRelations = $DB->get_records('adaptable_relations', array('adaptable_id'=>$id))){
	        $a=1;
	        foreach($adaptableRelations as $adaptableRelation){
	        	if($adaptableRelation->type=='default'){
	        		$defaultValues->defaultResourceId = $adaptableRelation->resource_id;
	        		$defaultValues->defaultResourceOriginalMode = $adaptableRelation->original_mode;
	        		$defaultValues->defaultResourceOriginalContentType = $adaptableRelation->original_content_type;
	        	}else{
	        		$defaultValues->{"resourceAlternative{$a}"} = $a;
	        		$defaultValues->{"resourceAlternative{$a}Resource"} = $adaptableRelation->resource_id;
	        		$defaultValues->{"resourceAlternative{$a}OriginalMode"} = $adaptableRelation->original_mode;
	        		$defaultValues->{"resourceAlternative{$a}AdaptationType"} = $adaptableRelation->adaptation_type;
	        		$defaultValues->{"resourceAlternative{$a}RepresentationForm"} = $adaptableRelation->representation_form;
	        		$a++;
	        	}
	        }
        }
        return $defaultValues;
    }
    
    /**
     * Get the form data and return null if the submit button was just a refresh (like the one's to add or remove an form element
     */
	function get_data() {
		if ($this->is_submitted() and $this->is_validated() and $postData = &data_submitted()) {
			foreach($postData as $key=>$value){
	        	if(preg_match("/resourceAlternative(\d+)Remove$/", $key) or isset($postData->{'resourceAlternativeAdd'})){
	        		return NULL;
	        	}
	        }
		}
		
        return parent::get_data();
    }
    
	/**
     * Load the adaptable_relations data
     *
     * @param mixed $default_values object or array of default values
     */
    function set_data($default_values) {
    	global $DB;
    	
        if (is_object($default_values)){
        	$this->externalData = self::getData($default_values->instance, $default_values);
        }
        
        parent::set_data($default_values);
    }
    
    /***
     * Validate the submited form data
     */
	function validation($data, $files) {
		$errors = parent::validation($data, $files);
		
		if(!isset($data['defaultResourceId'])){
			$errors['staticDefaultResourceId'] = get_string('err_youCannotAddThisResource', 'adaptable');
		}else{
			// check for duplicates
			$usedResources = array($data['defaultResourceId']=>'defaultResourceId');
			foreach($data as $key=>$value){
	        	if(preg_match("/resourceAlternative(\d+)$/", $key, $matches) && !isset($data[$key."Remove"])){
	        		if(isset($usedResources[$data["{$key}Resource"]])){
	        			if($usedResources[$data["{$key}Resource"]]=='defaultResourceId'){
	        				$errors["{$key}Resource"] = get_string('err_usedResourceAsDefaultResource', 'adaptable');
	        			}else{
	        				$errors["{$key}Resource"] = get_string('err_usedResourceInAnotherAlternativeResource', 'adaptable', $data[$usedResources[$data["{$key}Resource"]]]);
	        			}
	        			continue;
	        		}
	        		$usedResources[$data["{$key}Resource"]] = $key;
	        	}
			}
		}
		
		return $errors;
	}
}
