<?php

/**
 * User model preferences form for the EU4ALL user model component
 *
 * @package    	UM, EU4ALL
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

class um_user_preferences_edit_form extends moodleform {
	private $externalData;
	
	public function __construct($externalFormData){
		if(!empty($externalFormData)){
			$this->externalData = self::getProfileFormData($externalFormData);
		}else{
			$this->externalData = new stdClass();
		}
		parent::__construct();
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
							$result["$value"] = get_string($baseLocaleString."$value", 'local_eu4all');
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * Convert a string with a ISO 8601 date format to a unix timestamp
	 * 
	 * @param string $datetime
	 * @return string with the unix timestamp
	 */
	public static function parseDateFromIso8601($datetime) {
		date_default_timezone_set('UTC');
		$matches = array();
		
		if(preg_match("/^(\d{4})\-(\d{2})\-(\d{2})(T\d{2}:\d{2}:\d{2})([+-])(\d{2}):(\d{2})$/", $datetime, $matches) === 1) {
			return mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
		}
		return time();
	}
	
	/**
	 * Adds a specific content type to the form
	 * 
	 * @param string $contentType with the content type
	 * @param moodleform $mform with the form
	 * @param stdClass $postData with the post data
	 * @param array $selectsData with the selects options
	 */
	private static function _addContentType($contentType=null, &$mform=null, &$postData=null, $selectsData=null){
		if(!empty($mform) && !empty($contentType)){
			// text content type
			$mform->addElement('html', '<h3>'.get_string("{$contentType}Settings", 'local_eu4all').'</h3>');
			if((isset($postData->{$contentType."AlternativeAdd"}) || isset($postData->{"{$contentType}Alternative"})) && !isset($postData->{"{$contentType}AlternativeRemove"})){
		        $mform->addElement('header', "{$contentType}AlternativeHeader", get_string("{$contentType}Alternative", 'local_eu4all'));
		        $mform->addElement('hidden', "{$contentType}Alternative");
		        $mform->setType("{$contentType}Alternative", PARAM_INT);
		        $mform->setDefault("{$contentType}Alternative", '1');
		        // usage
		        $mform->addElement('select', "{$contentType}AlternativeUsage", get_string("{$contentType}AlternativeUsage", 'local_eu4all'), $selectsData['usages']);
		        $mform->addHelpButton("{$contentType}AlternativeUsage", "{$contentType}AlternativeUsage", 'local_eu4all');
				if(isset($postData->{"{$contentType}AlternativeUsage"})){
		        	// restore previous state
		        	if(isset($_POST["{$contentType}AlternativeUsage"])){
		        		$_POST["{$contentType}AlternativeUsage"] = $postData->{"{$contentType}AlternativeUsage"};
		        	}
		        	$mform->setDefault("{$contentType}AlternativeUsage", $postData->{"{$contentType}AlternativeUsage"});
		        }
		        // adaptation type
		        $mform->addElement('select', "{$contentType}AlternativeAdaptationType", get_string("{$contentType}AlternativeAdaptationType", 'local_eu4all'), $selectsData['adaptationTypes']);
		        $mform->addHelpButton("{$contentType}AlternativeAdaptationType", "{$contentType}AlternativeAdaptationType", 'local_eu4all');
				if(isset($postData->{"{$contentType}AlternativeAdaptationType"})){
		        	if(isset($_POST["{$contentType}AlternativeAdaptationType"])){
		        		$_POST["{$contentType}AlternativeAdaptationType"] = $postData->{"{$contentType}AlternativeAdaptationType"};
		        	}
		        	$mform->setDefault("{$contentType}AlternativeAdaptationType", $postData->{"{$contentType}AlternativeAdaptationType"});
		        }
		        // original access mode
		        $mform->addElement('select', "{$contentType}AlternativeOriginalAccessMode", get_string("{$contentType}AlternativeOriginalAccessMode", 'local_eu4all'), $selectsData['accessModes']);
		        $mform->addHelpButton("{$contentType}AlternativeOriginalAccessMode", "{$contentType}AlternativeOriginalAccessMode", 'local_eu4all');
				if(isset($postData->{"{$contentType}AlternativeOriginalAccessMode"})){
		        	if(isset($_POST["{$contentType}AlternativeOriginalAccessMode"])){
		        		$_POST["{$contentType}AlternativeOriginalAccessMode"] = $postData->{"{$contentType}AlternativeOriginalAccessMode"};
		        	}
		        	$mform->setDefault("{$contentType}AlternativeOriginalAccessMode", $postData->{"{$contentType}AlternativeOriginalAccessMode"});
		        }
		        // representation form
		        $mform->addElement('select', "{$contentType}AlternativeRepresentationForm", get_string("{$contentType}AlternativeRepresentationForm", 'local_eu4all'), $selectsData['representationForms']);
		        $mform->addHelpButton("{$contentType}AlternativeRepresentationForm", "{$contentType}AlternativeRepresentationForm", 'local_eu4all');
				if(isset($postData->{"{$contentType}AlternativeRepresentationForm"})){
		        	if(isset($_POST["{$contentType}AlternativeRepresentationForm"])){
		        		$_POST["{$contentType}AlternativeRepresentationForm"] = $postData->{"{$contentType}AlternativeRepresentationForm"};
		        	}
		        	$mform->setDefault("{$contentType}AlternativeRepresentationForm", $postData->{"{$contentType}AlternativeRepresentationForm"});
		        }
		        
		        $mform->addElement('submit', "{$contentType}AlternativeRemove", get_string("{$contentType}AlternativeRemove", 'local_eu4all'));
			}else{
				$mform->addElement('submit', "{$contentType}AlternativeAdd", get_string("{$contentType}AlternativeAdd", 'local_eu4all'));
			}
			
	        $a=1;
			if(!empty($postData) && !empty($selectsData)){
		        foreach($postData as $key=>$value){
		        	if(preg_match("/{$contentType}Enhancement\d+$/", $key) && !isset($postData->{$key."Remove"})){
		        		$mform->addElement('hidden', "{$contentType}Enhancement{$a}");
				        $mform->setType("{$contentType}Enhancement{$a}", PARAM_INT);
				        $mform->setDefault("{$contentType}Enhancement{$a}", $a);
				        
				        $mform->addElement('header', "{$contentType}Enhancement{$a}Header", get_string($contentType.'Enhancement', 'local_eu4all', $a));
				        
				        $mform->addElement('select', "{$contentType}Enhancement{$a}Usage", get_string($contentType.'EnhancementUsage', 'local_eu4all'), $selectsData['usages']);
				        $mform->addHelpButton("{$contentType}Enhancement{$a}Usage", $contentType.'EnhancementUsage', 'local_eu4all');
				        if(isset($postData->{$key."Usage"})){
				        	// restore previous state
				        	if(isset($_POST["{$contentType}Enhancement{$a}Usage"])){
				        		$_POST["{$contentType}Enhancement{$a}Usage"] = $postData->{$key."Usage"};
				        	}
				        	$mform->setDefault("{$contentType}Enhancement{$a}Usage", $postData->{$key."Usage"});
				        }
				        
				        $mform->addElement('select', "{$contentType}Enhancement{$a}AdaptationType", get_string($contentType.'EnhancementAdaptationType', 'local_eu4all'), $selectsData['adaptationTypes']);
				        $mform->addHelpButton("{$contentType}Enhancement{$a}AdaptationType", $contentType.'EnhancementAdaptationType', 'local_eu4all');
				        if(isset($postData->{$key."AdaptationType"})){
				        	// restore previous state
				        	if(isset($_POST["{$contentType}Enhancement{$a}AdaptationType"])){
				        		$_POST["{$contentType}Enhancement{$a}AdaptationType"] = $postData->{$key."AdaptationType"};
				        	}
				        	$mform->setDefault("{$contentType}Enhancement{$a}AdaptationType", $postData->{$key."AdaptationType"});
				        }
				        $mform->addElement('select', "{$contentType}Enhancement{$a}OriginalAccessMode", get_string($contentType.'EnhancementOriginalAccessMode', 'local_eu4all'), $selectsData['accessModes']);
				        $mform->addHelpButton("{$contentType}Enhancement{$a}OriginalAccessMode", $contentType.'EnhancementOriginalAccessMode', 'local_eu4all');
				        if(isset($postData->{$key."OriginalAccessMode"})){
				        	// restore previous state
				        	if(isset($_POST["{$contentType}Enhancement{$a}OriginalAccessMode"])){
				        		$_POST["{$contentType}Enhancement{$a}OriginalAccessMode"] = $postData->{$key."OriginalAccessMode"};
				        	}
				        	$mform->setDefault("{$contentType}Enhancement{$a}OriginalAccessMode", $postData->{$key."OriginalAccessMode"});
				        }
				        $mform->addElement('submit', "{$contentType}Enhancement{$a}Remove", get_string("{$contentType}EnhancementRemove", 'local_eu4all', $a));
				        
				        $a++;
		        		
		        	}
		        }
		        if(isset($postData->{$contentType."EnhancementAdd"})){
		        	$mform->addElement('hidden', "{$contentType}Enhancement{$a}");
			        $mform->setType("{$contentType}Enhancement{$a}", PARAM_INT);
			        $mform->setDefault("{$contentType}Enhancement{$a}", $a);
			        $mform->addElement('header', "{$contentType}Enhancement{$a}Header", get_string($contentType.'Enhancement', 'local_eu4all', $a));
			        $mform->addElement('select', "{$contentType}Enhancement{$a}Usage", get_string($contentType.'EnhancementUsage', 'local_eu4all'), $selectsData['usages']);
			        $mform->addHelpButton("{$contentType}Enhancement{$a}Usage", $contentType.'EnhancementUsage', 'local_eu4all');
			        $mform->addElement('select', "{$contentType}Enhancement{$a}AdaptationType", get_string($contentType.'EnhancementAdaptationType', 'local_eu4all'), $selectsData['adaptationTypes']);
			        $mform->addHelpButton("{$contentType}Enhancement{$a}AdaptationType", $contentType.'EnhancementAdaptationType', 'local_eu4all');
			        $mform->addElement('select', "{$contentType}Enhancement{$a}OriginalAccessMode", get_string($contentType.'EnhancementOriginalAccessMode', 'local_eu4all'), $selectsData['accessModes']);
			        $mform->addHelpButton("{$contentType}Enhancement{$a}OriginalAccessMode", $contentType.'EnhancementOriginalAccessMode', 'local_eu4all');
			        
			        $mform->addElement('submit', "{$contentType}Enhancement{$a}Remove", get_string("{$contentType}EnhancementRemove", 'local_eu4all', $a));
				    
			        $a++;
		        }
			}
		}
		$mform->addElement('submit', "{$contentType}EnhancementAdd", get_string("{$contentType}EnhancementAdd", 'local_eu4all'));
        $mform->closeHeaderBefore("{$contentType}EnhancementAdd");
	}
	
	/**
	 * Returns the enhancements of specific content type
	 * 
	 * @param stdClass $fdata
	 * @param string $contentType
	 * @return stdClass with the enhancements (if they exists)
	 */
	private static function getContentTypeProfileData($fdata=null, $contentType=null){
		if(!empty($fdata) && !empty($contentType)){
			$results = array();
			foreach($fdata as $key=>$value){
	        	if(preg_match("/{$contentType}Enhancement\d+$/", $key)){
	        		$enhancement = new stdClass();
	        		$enhancement->usage = $fdata->{$key."Usage"};
	        		$enhancement->adaptationType = $fdata->{$key."AdaptationType"};
	        		$enhancement->originalAccessMode = $fdata->{$key."OriginalAccessMode"};
	        		
	        		$results[]=$enhancement;
	        	}
			}
			return $results;
		}
		return false;
	}
	
	/**
	 * Format the submitted data on a object
	 * 
	 * @param stdClass $fdata with the post data 
	 * @return stdClass with the profile data
	 */
	public static function getProfileData($fdata=null){
		if(!empty($fdata)){
			$profileInfo = new stdClass();
			$profileInfo->gender = $fdata->gender;
			$profileInfo->birthDate = date("c", $fdata->birthDate);
			$profileInfo->birthPlace = $fdata->birthPlace;
			
			$contentTypes = array('text', 'audio', 'image', 'video', 'multimedia');
			
			foreach($contentTypes as $contentType){
				if(isset($fdata->{$contentType."Alternative"})){
					$profileInfo->{$contentType."Alternative"} = new stdClass();
					$profileInfo->{$contentType."Alternative"}->usage = $fdata->{$contentType."AlternativeUsage"};
					$profileInfo->{$contentType."Alternative"}->adaptationType = $fdata->{$contentType."AlternativeAdaptationType"};
					$profileInfo->{$contentType."Alternative"}->originalAccessMode = $fdata->{$contentType."AlternativeOriginalAccessMode"};
					$profileInfo->{$contentType."Alternative"}->representationForm = $fdata->{$contentType."AlternativeRepresentationForm"};
				}
				
				$profileInfo->{$contentType."Enhancement"} = self::getContentTypeProfileData($fdata, $contentType);
			}
			return $profileInfo;
		}
		return false;
	}
	
	/**
	 * Creates a structure to store the data to set the form values
	 * @param object $profileData with the retrieved data
	 */
	public static function getProfileFormData($profileData=null){
		if(!empty($profileData)){
			$fdata = new stdClass();
			
			if(isset($profileData->gender)){ $fdata->gender = $profileData->gender; }
			if(isset($profileData->birthPlace)){ $fdata->birthPlace = $profileData->birthPlace; }
			if(isset($profileData->birthDate)){
				$fdata->birthDate = self::parseDateFromIso8601($profileData->birthDate);
			}
			
			$contentTypes = array('text', 'audio', 'image', 'video', 'multimedia');
			
			foreach($contentTypes as $contentType){
				if(isset($profileData->{$contentType."Alternative"})){
					$fdata->{$contentType."Alternative"} = '1'; 
					if(isset($profileData->{$contentType."Alternative"}->usage)){ 
						$fdata->{$contentType."AlternativeUsage"} = $profileData->{$contentType."Alternative"}->usage; 
					}
					if(isset($profileData->{$contentType."Alternative"}->adaptationType)){ 
						$fdata->{$contentType."AlternativeAdaptationType"} = $profileData->{$contentType."Alternative"}->adaptationType; 
					}
					if(isset($profileData->{$contentType."Alternative"}->originalAccessMode)){ 
						$fdata->{$contentType."AlternativeOriginalAccessMode"} = $profileData->{$contentType."Alternative"}->originalAccessMode; 
					}
					if(isset($profileData->{$contentType."Alternative"}->representationForm)){ 
						$fdata->{$contentType."AlternativeRepresentationForm"} = $profileData->{$contentType."Alternative"}->representationForm; 
					}
				}
				
				if(isset($profileData->{$contentType."Enhancement"})){
					$enhancements = $profileData->{$contentType."Enhancement"};
					$a=1;
					foreach($enhancements as $enhancement){
						$fdata->{"{$contentType}Enhancement{$a}"} = $a;
						if(isset($enhancement->usage)){ $fdata->{"{$contentType}Enhancement{$a}Usage"} = $enhancement->usage; }
						if(isset($enhancement->adaptationType)){ $fdata->{"{$contentType}Enhancement{$a}AdaptationType"} = $enhancement->adaptationType; }
						if(isset($enhancement->originalAccessMode)){ $fdata->{"{$contentType}Enhancement{$a}OriginalAccessMode"} = $enhancement->originalAccessMode; }
						
						$a++;
					}
				}
			}
			
			return $fdata;
		}
		return false;
	}
	
	public function setFormData(){
		$this->set_data($this->externalData);
	}
	
    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        if(!(($postdata = &data_submitted()) && confirm_sesskey())){
        	$postdata = $this->externalData;
        }
        
        // default element values
        $usages = array();
        $adaptationTypes = array();
        $accessModes = array();
        $representationForms = array();
        
    	// load the profile vocabulary from the xsd
        $xml = simpleXML_load_file($CFG->dirroot.'/local/eu4all/lib/xsd/imslip_isoafa_integr.xsd'); 
		if($xml!==false){
			$genders = self::_getDataFromXml($xml, '//xsd:element[@name="gender"]/xsd:complexType/xsd:attribute[@name="gender"]/xsd:simpleType/xsd:restriction/xsd:enumeration', 'gender');
		}
        
        // load the content types vocabulary from the xsd
        $xml = simpleXML_load_file($CFG->dirroot.'/local/eu4all/lib/xsd/eu4all-isoafa-vocab.xsd'); 
		if($xml!==false){
			$usages = self::_getDataFromXml($xml, '//xsd:simpleType[@name="Usage_Vocabulary"]/xsd:restriction/xsd:enumeration', 'usage');
			$adaptationTypes = self::_getDataFromXml($xml, '//xsd:simpleType[@name="Adaptation_Type_Vocabulary"]/xsd:restriction/xsd:enumeration', 'adaptationType');
			$accessModes = self::_getDataFromXml($xml, '//xsd:simpleType[@name="Access_Mode_Vocabulary"]/xsd:restriction/xsd:enumeration', 'accessMode');
			$representationForms = array_merge(array('void'=>get_string("representationFormVoid", 'local_eu4all')), self::_getDataFromXml($xml, '//xsd:simpleType[@name="Representation_Form_Vocabulary"]/xsd:restriction/xsd:enumeration', 'representationForm'));
			
			$selectsData = array('usages'=>$usages,'adaptationTypes'=>$adaptationTypes,'accessModes'=>$accessModes,'representationForms'=>$representationForms);
		}else{
			$selectsData=null;
		}
		
		// add the personal settings fields
		$mform->addElement('header', 'personalSettingsHeader', get_string('personalSettings', 'local_eu4all'));
        $mform->addElement('select', 'gender', get_string('gender', 'local_eu4all'), $genders);
        $mform->setDefault('gender', (isset($postdata->gender)?$postdata->gender:'NA'));
        $mform->addElement('date_selector', 'birthDate', get_string('birthDate', 'local_eu4all'));
        if(isset($postdata->birthDate)){
        	$mform->setDefault('birthDate', $postdata->birthDate);
        }
        $mform->addElement('text', 'birthPlace', get_string('birthPlace', 'local_eu4all'));
        $mform->setDefault('birthPlace', (isset($postdata->birthPlace)?$postdata->birthPlace:''));
        // nasty, I know :~(
        $mform->addElement('static', 'break','');
        $mform->closeHeaderBefore("break");
			        
		// add the content types
        self::_addContentType('text', $mform, $postdata, $selectsData);
        self::_addContentType('audio', $mform, $postdata, $selectsData);
        self::_addContentType('image', $mform, $postdata, $selectsData);
        self::_addContentType('video', $mform, $postdata, $selectsData);
        self::_addContentType('multimedia', $mform, $postdata, $selectsData);
        
        $this->add_action_buttons();
    }
}
