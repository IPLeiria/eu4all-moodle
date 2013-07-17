<?php
/**
 * User model library for the EU4ALL
 *
 * @package    	EU4ALL
 * @subpackage 	UM, local_eu4all
 * @version	2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 	Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die(''); // silence is golden

// Load the common lib
require_once($CFG->dirroot.'/local/eu4all/lib.php');

define('UM_XSD', $CFG->dirroot.'/local/eu4all/lib/xsd/imslip_isoafa_integr.xsd');

// Event handlers
/**
 * Handles the user creation on the central repository
 * 
 * @param object $user with the created user
 * @return boolean with the result of the operation
 */
function eu4all_um_usercreated_handler($user){
	EU4ALL_UserModel::sendRegistrationEvent($user->username);
	EU4ALL_UserModel::setPersonalUserData($user->username);
	
	return true;
}

/**
 * Handles the user update on the central repository
 * 
 * @param object $user with the updated user
 * @return boolean with the result of the operation
 */
function eu4all_um_userupdated_handler($user){
	EU4ALL_UserModel::setPersonalUserData($user->username);
	
	return true;
}

/**
 * Handles the user elimination on the central repository
 * 
 * @param object $user with the deleted user
 * @return boolean with the result of the operation
 */
function eu4all_um_userdeleted_handler($user){
	EU4ALL_UserModel::deleteUserModel($user->username);
	
	return true;
}

/**
 * Handles the user fields syncronization with the central repository
 * 
 * @param object $user with the authenticated user
 * @return boolean with the result of the operation
 */
function eu4all_um_userauthenticated_handler($user){
	if(!($personalData = EU4ALL_UserModel::getPersonalUserData($user->username))){
		// Register the user account (if it doesn't exists)
		EU4ALL_UserModel::sendRegistrationEvent($user->username);
	}
	return true;
}

/**
 * EU4ALL User Model client to communicate with the central repository
 *
 * @package    	EU4ALL
 * @subpackage 	UM, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class EU4ALL_UserModel{
	private static $soapClient = NULL;
	
	/**
	 * We will be using a unique instance so this constructor will be private
	 * @param $wsdl with the service URL to use on the SoapClient link
	 * @uses global $CFG for the proxy settings
	 */
	private function __construct($wsdl=NULL){
		global $CFG;
		try{
			if(is_null($wsdl)):
				if(!($wsdl = get_config(EU4ALL_PLUGINNAME, 'eu4all_um_wsdl_url'))):
					throw new Exception(get_string('webServiceLinkFailed', EU4ALL_PLUGINNAME));
				endif;
			endif;
			
			// Enable trace
			$options = array('trace' => true);
			
			// If we have proxy settings, use them to connect the client
			if(!empty($CFG->proxyhost)){
				$options['proxy_host']=$CFG->proxyhost;
			}
			if(!empty($CFG->proxyport)){
				$options['proxy_port']=$CFG->proxyport;
			}
			if(!empty($CFG->proxyuser)){
				$options['proxy_login']=$CFG->proxyuser;
			}
			if(!empty($CFG->proxypassword)){
				$options['proxy_password']=$CFG->proxypassword;
			}
			
			// Define the client
			if(!@file_get_contents($wsdl)) {
		    	throw new SoapFault('-1', '');
		    }
			self::$soapClient = new SoapClient($wsdl, $options);
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM] ".get_string('webServiceLinkFailed', EU4ALL_PLUGINNAME));
		}
	}
	
	/**
	 * Get an user object based on the conditions with extra fields from the profile
	 * @param $conditions array with the conditions to select an user
	 */
	private static function getUserWithProfile($conditions=array()){
		global $DB;
		if(!empty($conditions) && $user = $DB->get_record('user', $conditions, '*', IGNORE_MULTIPLE)){
			//profile_load_data($user);
			return $user;
		}
		return false;
	}
	
	/**
	 * Get an user object based on the conditions with extra fields from the profile
	 * @param $conditions array with the conditions to select an user
	 */
	private static function log($message=NULL, $forceOutput=false){
		global $CFG;
		if(!is_null($message) && ($forceOutput || !empty($CFG->debug))){
			error_log($message);
		}
	}
	
	/**
	 * Extract the user profile from XML
	 * 
	 * @param String $xmlString
	 * @return object with the user properties
	 */
	public static function extractUserProfileFromXml($xmlString, $prefix='pnp'){
		$eu4allProfileData = new stdClass();
		if(!empty($xmlString)){
			if($xml = simplexml_load_string($xmlString, 'SimpleXMLElement', 0, 'http://www.imsglobal.org/xsd/imslip_v1p0')):
				$xml->registerXPathNamespace($prefix,'http://www.imsglobal.org/xsd/imslip_v1p0');
				
				// first name
				if($node = $xml->xpath("//$prefix:identification/$prefix:name/$prefix:partname/$prefix:typename[$prefix:tyvalue='First']/../$prefix:text")):
					$eu4allProfileData->firstName = "{$node[0]}";
				endif;
				
				// last name
				if($node = $xml->xpath("//$prefix:identification/$prefix:name/$prefix:partname/$prefix:typename[$prefix:tyvalue='Last']/../$prefix:text")):
					$eu4allProfileData->lastName = "{$node[0]}";
				endif;
				
				// email
				if($node = $xml->xpath("//$prefix:identification/$prefix:contactinfo/$prefix:email")):
					$eu4allProfileData->email = "{$node[0]}";
				endif;
				
				// birth date
				if($node = $xml->xpath("//$prefix:identification/$prefix:demographics/$prefix:date/$prefix:typename[$prefix:tyvalue='Birth']/../$prefix:datetime")):
					$eu4allProfileData->birthDate = "{$node[0]}";
				endif;
				
				// birth place
				if($node = $xml->xpath("//$prefix:identification/$prefix:demographics/$prefix:placeofbirth")):
					$eu4allProfileData->birthPlace = "{$node[0]}";
				endif;
				
				// gender
				if($node = $xml->xpath("//$prefix:identification/$prefix:demographics/$prefix:gender")):
					if(isset($node[0]->attributes()->gender)):
						$eu4allProfileData->gender = "{$node[0]->attributes()->gender}";
					endif;
				endif;
			endif;
		}
		
		return $eu4allProfileData;
	}
	
	/**
	 * Extract the user accessibility preferences from XML
	 * 
	 * @param String $xmlString
	 * @param string $prefix with the xml prefix to use
	 * @return object with the user properties
	 */
	public static function extractUserPreferencesFromXml($xmlString, $prefix='pnp'){
		$eu4allProfileData = new stdClass();
		if(!empty($xmlString)){
			if($xml = simplexml_load_string($xmlString, 'SimpleXMLElement', 0, 'isoafa_pnp')):
				$xml->registerXPathNamespace($prefix,'isoafa_pnp');
				
				$contentTypes = array('text', 'audio', 'image', 'video', 'multimedia');
				foreach($contentTypes as $contentType){
					if($nodes = $xml->xpath("//$prefix:AccessForAllUser/$prefix:context/$prefix:content[@eu4all_original_content_type='$contentType']/*")):
						$eu4allProfileData->{$contentType."Alternative"} = new stdClass();
						$eu4allProfileData->{$contentType."Enhancement"} = array();
						foreach($nodes as $node){
							switch($node->getName()){
								case 'alternative':
									$alternative = new stdClass();
									if($value = $node->attributes()->usage){
										$alternative->usage = "$value";
									}
									if($value = $node->attributes()->adaptation_type){
										$alternative->adaptationType = "$value";
									}
									if($value = $node->attributes()->original_access_mode){
										$alternative->originalAccessMode = "$value";
									}
									if($value = $node->attributes()->representation_form){
										$alternative->representationForm = "$value";
									}else{
										$alternative->representationForm = "void";
									}
									
									$eu4allProfileData->{$contentType."Alternative"} = $alternative;
									break;
									
								case 'enhancement';
									$enhancements = new stdClass();
									if($value = $node->attributes()->usage){
										$enhancements->usage = "$value";
									}
									if($value = $node->attributes()->adaptation_type){
										$enhancements->adaptationType = "$value";
									}
									if($value = $node->attributes()->original_access_mode){
										$enhancements->originalAccessMode = "$value";
									}
									
									$eu4allProfileData->{$contentType."Enhancement"}[] = $enhancements;
								
									break;
							}
						}
					endif;
				}
			endif;
		}
		
		return $eu4allProfileData;
	}
	
	/**
	 * Given the profile data, structure the user accessibility preferences on a XML format suitable for update the user preferences within central repository
	 * 
	 * @param object $eu4allProfileData with the eu4all profile data
	 * @param string $prefix with the xml prefix to use
	 * @return String with the XML data, or boolean false on error
	 */
	public static function formatUserPreferencesAsXml($eu4allProfileData=null, $prefix='pnp'){
		if(!empty($eu4allProfileData)){
			try{
				$dom = new DOMDocument("1.0");
				$context = $dom->createElementNS('isoafa_pnp', "$prefix:context");
				$context->setAttribute('identifier','eu4all_context');
				$dom->appendChild($context);
				
				$display = $dom->createElement("$prefix:display");
				$context->appendChild($display);
				
				$contentTypes = array('text', 'audio', 'image', 'video', 'multimedia');
				
				foreach($contentTypes as $contentType){
					$content = $dom->createElement("$prefix:content");
					$content->setAttribute('eu4all_original_content_type', $contentType);
					if(isset($eu4allProfileData->{$contentType."Alternative"})){
						$alternative = $dom->createElement("$prefix:alternative");
						$alternative->setAttribute('usage', $eu4allProfileData->{$contentType."Alternative"}->usage);
						$alternative->setAttribute('adaptation_type', $eu4allProfileData->{$contentType."Alternative"}->adaptationType);
						$alternative->setAttribute('original_access_mode', $eu4allProfileData->{$contentType."Alternative"}->originalAccessMode);
						if($eu4allProfileData->{$contentType."Alternative"}->representationForm!='void'){
							$alternative->setAttribute('representation_form', $eu4allProfileData->{$contentType."Alternative"}->representationForm);
						}
						$content->appendChild($alternative);
					}
					if(isset($eu4allProfileData->{$contentType."Enhancement"})){
						foreach($eu4allProfileData->{$contentType."Enhancement"} as $enhancementData){
							$enhancement = $dom->createElement("$prefix:enhancement");
							$enhancement->setAttribute('usage', $enhancementData->usage);
							$enhancement->setAttribute('adaptation_type', $enhancementData->adaptationType);
							$enhancement->setAttribute('original_access_mode', $enhancementData->originalAccessMode);
							$content->appendChild($enhancement);
						}
					}
					$context->appendChild($content);
				}
				
				return $dom->saveXML($context);
			}catch (Exception $ex){
				self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('formatUserPreferencesAsXmlFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
			}
		}
		return false;
	}
	
	/**
	 * Given the user id, format the user accessibility preferences on a XML format suitable for update the user preferences within central repository
	 * 
	 * @param String $userId with the user id
	 * @param object $eu4allProfileData with the eu4all profile data
	 * @return String with the XML data, or a empty string on error
	 */
	public static function getMoodleUserProfileInImsLipFormat($userId, $eu4allProfileData=null, $managerReference=EU4ALL_MANAGER_REFERENCE){
		// Checks for an user id
		if(empty($userId)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for a valid model
		if(empty($eu4allProfileData)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidModel', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		try{
			$user = self::getUserWithProfile(array('username'=>$userId));
			if($user):
				$dom = new DOMDocument("1.0");
				
				$learnerinformation = $dom->createElementNS('http://www.imsglobal.org/xsd/imslip_v1p0', 'learnerinformation');
				$dom->appendChild($learnerinformation);
				
				// content type
				$contentype = $dom->createElement('contentype');
				$learnerinformation->appendChild($contentype);
				
				$referential = $dom->createElement('referential');
				$contentype->appendChild($referential);
				
				$sourcedid = $dom->createElement('sourcedid');
				$referential->appendChild($sourcedid);
				
				$source = $dom->createElement('source', $managerReference);
				$sourcedid->appendChild($source);
				
				$id = $dom->createElement('id', $user->email);
				$sourcedid->appendChild($id);
				
				// identification
				$identification = $dom->createElement('identification');
				$learnerinformation->appendChild($identification);
				
				$contentype = $dom->createElement('contentype');
				$identification->appendChild($contentype);
				
				$referential = $dom->createElement('referential');
				$contentype->appendChild($referential);
				
				$indexid = $dom->createElement('indexid', 'eu4all_identification');
				$referential->appendChild($indexid);
				
				// name
				$name = $dom->createElement('name');
				$identification->appendChild($name);
				
				$typename = $dom->createElement('typename');
				$name->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Preferred");
				$typename->appendChild($tyvalue);
				
				// first name
				$partname = $dom->createElement('partname');
				$name->appendChild($partname);
				
				$typename = $dom->createElement('typename');
				$partname->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "First");
				$typename->appendChild($tyvalue);
				
				$text = $dom->createElement('text', $user->firstname);
				$partname->appendChild($text);
				
				// last name
				$partname = $dom->createElement('partname');
				$name->appendChild($partname);
				
				$typename = $dom->createElement('typename');
				$partname->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Last");
				$typename->appendChild($tyvalue);
				
				$text = $dom->createElement('text', $user->lastname);
				$partname->appendChild($text);
				
				// contact info
				$contactinfo = $dom->createElement('contactinfo');
				$identification->appendChild($contactinfo);
				
				$typename = $dom->createElement('typename');
				$contactinfo->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Work");
				$typename->appendChild($tyvalue);
				
				$contentype = $dom->createElement('contentype');
				$contactinfo->appendChild($contentype);
				
				$referential = $dom->createElement('referential');
				$contentype->appendChild($referential);
				
				$indexid = $dom->createElement('indexid', 'eu4all_contact');
				$referential->appendChild($indexid);
				
				// email
				$email = $dom->createElement('email', $user->email);
				$contactinfo->appendChild($email);
				
				// address
				$address = $dom->createElement('address');
				$identification->appendChild($address);
				
				$typename = $dom->createElement('typename');
				$address->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Work");
				$typename->appendChild($tyvalue);
				
				$contentype = $dom->createElement('contentype');
				$address->appendChild($contentype);
				
				$referential = $dom->createElement('referential');
				$contentype->appendChild($referential);
				
				$indexid = $dom->createElement('indexid', 'eu4all_address');
				$referential->appendChild($indexid);
				
				// city
				$city = $dom->createElement('city', $user->city);
				$address->appendChild($city);
				
				// demographics
				$demographics = $dom->createElement('demographics');
				$identification->appendChild($demographics);
				
				$typename = $dom->createElement('typename');
				$demographics->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Adult");
				$typename->appendChild($tyvalue);
				
				$contentype = $dom->createElement('contentype');
				$demographics->appendChild($contentype);
				
				$referential = $dom->createElement('referential');
				$contentype->appendChild($referential);
				
				$indexid = $dom->createElement('indexid', 'eu4all_demographics');
				$referential->appendChild($indexid);
				
				// gender
				$gender = $dom->createElement('gender');
				$gender->setAttribute('gender', $eu4allProfileData->gender);
				$demographics->appendChild($gender);
				
				// date (of birth)
				$date = $dom->createElement('date');
				$demographics->appendChild($date);
				
				$typename = $dom->createElement('typename');
				$date->appendChild($typename);
				
				$tysource = $dom->createElement('tysource');
				$tysource->setAttribute('sourcetype', 'imsdefault');
				$typename->appendChild($tysource);
				
				$tyvalue = $dom->createElement('tyvalue', "Birth");
				$typename->appendChild($tyvalue);
				
				// datetime
				$datetime = $dom->createElement('datetime', $eu4allProfileData->birthDate);
				$date->appendChild($datetime);
				
				// placeofbirth
				$placeofbirth = $dom->createElement('placeofbirth', "{$eu4allProfileData->birthPlace}");
				$placeofbirth->setAttribute('xml:lang', $user->lang);
				$demographics->appendChild($placeofbirth);
				
				// accessibility
				$accessibility = $dom->createElement('accessibility');
				$learnerinformation->appendChild($accessibility);
				
				$pnp_AccessForAllUser = $dom->createElementNS('isoafa_pnp', 'pnp:AccessForAllUser');
				$accessibility->appendChild($pnp_AccessForAllUser);
				
				// Create a accessibility context
				$contextDocument = new DOMDocument;
				$contextDocument->preserveWhiteSpace = true;
				$contextDocument->loadXML(self::formatUserPreferencesAsXml($eu4allProfileData));
				$context = $dom->importNode($contextDocument->documentElement, true);
				$pnp_AccessForAllUser->appendChild($context);
				
				if(is_readable(UM_XSD)):
					libxml_use_internal_errors(true);
					$xml_data = $dom->saveXML();
					$dom_tmp = new DOMDocument("1.0");
					$dom_tmp->loadXML($xml_data);
					
					if($dom_tmp->schemaValidate(UM_XSD)) :
						return $xml_data;
					else:
						$errors = libxml_get_errors();
						$error_msg = '';
					    foreach ($errors as $error) {
					    switch ($error->level) {
						        case LIBXML_ERR_WARNING:
						            $error_msg .= get_string('warningOnLine', EU4ALL_PLUGINNAME, $error);
						            break;
						        case LIBXML_ERR_ERROR:
						            $error_msg .= get_string('errorOnLine', EU4ALL_PLUGINNAME, $error);
						            break;
						        case LIBXML_ERR_FATAL:
						            $error_msg .= get_string('fatalErrorOnLine', EU4ALL_PLUGINNAME, $error);
						            break;
						    }
					        $error_msg .= ' '.trim($error->message).'. ';
					    }
					    
					    libxml_clear_errors();
						throw new Exception(get_string('invalidUserModelXml', EU4ALL_PLUGINNAME, $error_msg));
					endif;
				endif;
				
				return $dom->saveXML();
			endif;
		}catch (Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getMoodleUserProfileInImsLipFormatFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return '';
	}
	
	/**
	 * @return {@link EU4ALL_UserModel} with the instance to use
	 */
	public static function getSoapClientInstance($url=NULL){
		if(is_null(self::$soapClient) || !is_null($url)):
			new self($url);
		endif;
		return self::$soapClient;
	}

	/**
	 * Sets the user model profile (XML) for a given user id, based on the moodle data
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $managerReference LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function setUserModel($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for an user id
		if(empty($userId)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for a valid model
		$model = self::getMoodleUserProfileInImsLipFormat($userId, $managerReference);
		if(empty($model)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidModel', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId,
				'model'=>$model
			);
			$result = $client->setUserModel($params);
			
			if(isset($result->response_code) && $result->response_code == 0):
				return true;
			else:
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			endif;
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('setUserModelFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('setUserModelFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}

	/**
	 * Gets the user model profile (XML) for a given user id
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $managerReference LMS user collection identification
	 * @return String with the model string, false on error
	 */
	public static function getUserModel($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->getUserModel($params);
			
			if(!empty($result->model)){
				return $result->model;
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getUserModelFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getUserModelFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Update the user model profile (XML) for a given user id
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param object $eu4allProfileData with the eu4all profile data
	 * @param String $managerReference LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function updateUserModel($userId, $eu4allProfileData=null, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a valid model
		$model = self::getMoodleUserProfileInImsLipFormat($userId, $eu4allProfileData, $managerReference);
		if(empty($model)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidModel', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId,
				'model'=>$model
			);
			$result = $client->updateUserModel($params);
			
			if(isset($result->response_code) && $result->response_code == 0){
				return true;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('updateUserModelFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('updateUserModelFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Deletes the user model profile (XML) for a given user id
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $managerReference LMS user collection identification
	 * @return String with the model string, false on error
	 */
	public static function deleteUserModel($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->deleteUserModel($params);
			
			if(isset($result->response_code) && $result->response_code == 0){
				return true;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('deleteUserModel', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('deleteUserModel', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Registers an user within the EU4ALL central repository
	 * 
	 * @param String $userId with the user name (moodle username)
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function sendRegistrationEvent($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		$user = self::getUserWithProfile(array('username'=>$userId));
		if($user){
			try{
				$params = array(
					'manager_reference'=>$managerReference,
					'manager_userid'=>$user->username,
					'email'=>$user->email,
					'first_name'=>$user->firstname,
					'last_name'=>$user->lastname
				);
				$result = $client->sendRegistrationEvent($params);
				
				if(isset($result->response_code) && $result->response_code == 0){
					return true;
				}else{
					self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
				}
			}catch(SoapFault $fault){
				$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
				self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('registerUserFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
			}catch(Exception $ex){
				self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('registerUserFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
			}
		}else{
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToGetTheUserData', EU4ALL_PLUGINNAME));
		}
		
		return false;
	}
	
	/**
	 * Get the profile information for the selected user
	 * 
	 * @param String $userId with the user name (moodle username)
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function getPersonalUserData($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
				
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->getPersonalUserData($params);
			if(!empty($result->personal_data)){
				return $result->personal_data;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unrecognizablePersonalDataResult', EU4ALL_PLUGINNAME));
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getPersonalUserDataFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('setPersonalUserDataFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Updates the basic profile information for the selected user
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function setPersonalUserData($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
				
		$user = self::getUserWithProfile(array('username'=>$userId));
		if($user){
			$birthDate = date("Y-m-d", time());
			$birthPlace = '';
			$gender = 'NA';
			
			if($xmlString = self::getUserModel($user->username, $managerReference)):
				$userData = self::extractUserProfileFromXml($xmlString);
				if(isset($userData->birthDate)): $birthDate = $userData->birthDate; endif;
				if(isset($userData->birthPlace)): $birthPlace = $userData->birthPlace; endif;
				if(isset($userData->gender)): $gender = $userData->gender; endif;
			endif;
			
			try{
				$params = array(
					'manager_reference'=>$managerReference,
					'manager_userid'=>$user->username,
					'email'=>$user->email,
					'first_name'=>$user->firstname,
					'last_name'=>$user->lastname,
					'place_of_residence'=>$user->city,
					'place_of_birth'=>$birthPlace,
					'date_of_birth'=>$birthDate,
					'gender'=>$gender
					
				);
				$result = $client->setPersonalUserData($params);
				
				if(isset($result->response_code) && $result->response_code == 0){
					return true;
				}else{
					self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
				}
			}catch(SoapFault $fault){
				$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
				self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('setPersonalUserDataFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
			}catch(Exception $ex){
				self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('setPersonalUserDataFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
			}
		}else{
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToGetTheUserData', EU4ALL_PLUGINNAME));
		}
		
		return false;
	}
	
	/**
	 * Returns the attribute(s) specified for the selected profile
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $path Xpath route to attribute(s)
	 * 			valid examples:
	 * 				-> /learnerinformation
	 * 				-> /learnerinformation/qcl[contentype/referential/indexid='qcl_01']
	 * 				-> /learnerinformation/accessibility/pnp:AccessForAllUser
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function getUserModelAttribute($userId, $path='/learnerinformation/accessibility/pnp:AccessForAllUser', $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($path)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidPath', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'path'=>$path,
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->getUserModelAttribute($params);
			
			if(isset($result->attribute)){
				return $result->attribute;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME));
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getUserModelAttributeFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getUserModelAttributeFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Update the user profile with the atributes retrived from the repository
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 * @uses $USER
	 */
	public static function getUserModelAttributes($userId, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$userProfileData = new stdClass();
		
		// load user profile data
		if($xmlString = self::getUserModel($userId, $managerReference)):
			$userData = self::extractUserProfileFromXml($xmlString);
			
			foreach($userData as $key=>$value){
				$userProfileData->$key = $value;
			}
		endif;
		
		// load user preferences data
		if($xmlString = self::getUserModelAttribute($userId, '/learnerinformation/accessibility/pnp:AccessForAllUser', $managerReference)):
			$userPreferences = self::extractUserPreferencesFromXml($xmlString);
			
			foreach($userPreferences as $key=>$value){
				$userProfileData->$key = $value;
			}
		endif;
		
		return $userProfileData;
	}
	
	/**
	 * Sets the accessibility profile information for the specified user
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $node with the user preferences in a XML format
	 * @param String $path with the Xpath route to insert the user preferences
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function setUserModelAttribute($userId, $node, $path='/learnerinformation/accessibility/pnp:AccessForAllUser', $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'path'=>$path,
				'node'=>$node,
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->setUserModelAttribute($params);
			
			if(isset($result->response_code) && $result->response_code >= 1){
				return true;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('setUserModelAttributeFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('setUserModelAttributeFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		return false;
	}
	
	/**
	 * Sets the accessibility profile information for the specified user, using the user profile data from moodle
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param object $eu4allProfileData with the eu4all profile data
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 * @uses EU4ALL_UserModel::setUserModelAttribute
	 */
	public static function setUserModelAttributes($userId, $eu4allProfileData=null, $managerReference=EU4ALL_MANAGER_REFERENCE){
		return self::setUserModelAttribute($userId, self::formatUserPreferencesAsXml($eu4allProfileData),$managerReference);
	}
	
	/**
	 * Updates the accessibility profile information for the specified user
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $node with the user preferences in a XML format
	 * @param String $path with the Xpath route to insert the user preferences
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 * @uses EU4ALL_UserModel::setUserModelAttribute
	 */
	public static function updateUserModelAttribute($userId, $node, $path='/learnerinformation/accessibility/pnp:AccessForAllUser', $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'path'=>$path,
				'node'=>$node,
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->updateUserModelAttribute($params);
			
			if(isset($result->response_code) && $result->response_code >= 1){
				return true;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('updateUserModelAttribute', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('updateUserModelAttributeFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		return false;
	}
	
	/**
	 * Updates the accessibility profile information for the specified user, using the user profile data from moodle
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param object $eu4allProfileData with the eu4all profile data
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 * @uses EU4ALL_UserModel::updateUserModelAttribute
	 */
	public static function updateUserModelAttributes($userId, $eu4allProfileData=null, $managerReference=EU4ALL_MANAGER_REFERENCE){
		return self::updateUserModelAttribute($userId, self::formatUserPreferencesAsXml($eu4allProfileData), '/learnerinformation/accessibility/pnp:AccessForAllUser',$managerReference);
	}
	
	/**
	 * Sets the accessibility profile information for the specified user
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $property_type Type of property (allowed values are 'interest', 'goal', 'competency' or 'preference')
	 * @param String $property_id Unique ID of the property to be created/updated
	 * @param String $property_value current value of the property
	 * @param String $property_description Description of the property (optional)
	 * @param String $managerReference with LMS user collection identification
	 * @return boolean true on success, false otherwise
	 */
	public static function setUserProperty($userId, $property_type, $property_id, $property_value='', $property_description='', $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a valid property type
		if(!in_array($property_type, array('interest','goal','competency','preference'))){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserPropertyType', EU4ALL_PLUGINNAME, $property_type));
			return false;
		}
		
		// Checks for a valid property id
		if(empty($property_id)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidPropertyId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'property_type'=>$property_type,
				'property_id'=>$property_id,
				'property_value'=>$property_value,
				'property_description'=>$property_description,
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->setUserProperty($params);
			
			if(isset($result->response_code) && $result->response_code == 0){
				return true;
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->response_code})");
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('setUserPropertyFailed', EU4ALL_PLUGINNAME, $property_id)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('setUserPropertyFailed', EU4ALL_PLUGINNAME, $property_id)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Sets the accessibility profile information for the specified user
	 * 
	 * @param String $userId LMS user id (moodle username)
	 * @param String $property_type Type of property (allowed values are 'interest', 'goal', 'competency' or 'preference')
	 * @param String $property_id Unique ID of the property to be created/updated
	 * @param String $managerReference with LMS user collection identification
	 * @return String with the property value
	 */
	public static function getUserProperty($userId, $property_type, $property_id, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for an user id
		if(empty($userId)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		}
		
		// Checks for a valid property type
		if(!in_array($property_type, array('interest','goal','competency','preference'))){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserPropertyType', EU4ALL_PLUGINNAME, $property_type));
			return false;
		}
		
		// Checks for a valid property id
		if(empty($property_id)){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidPropertyId', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array(
				'property_type'=>$property_type,
				'property_id'=>$property_id,
				'manager_reference'=>$managerReference,
				'manager_userid'=>$userId
			);
			$result = $client->getUserProperty($params);
			
			if(isset($result->property_value)){
				return "{$result->property_value}";
			}else{
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME));
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getUserPropertyFailed', EU4ALL_PLUGINNAME, $property_id)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getUserPropertyFailed', EU4ALL_PLUGINNAME, $property_id)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Compares two users' common parameters to estimate their similarity. Returns an error if any of the profiles does not exist. The parameters compared are interests, competencies, goals and preferences. The system returns a memory structure where the information is expressed (for each parameter class) as follows
	 * @param String $userId1 with the user id from the first user
	 * @param String $userId2 with the user id from the second user
	 * @param String $managerReference with LMS user collection identification
	 * @return object with the following format:
	  		 [goal_similarity] => stdClass Object (
				 [common_elements_found] => 0
				 [similarity_percentage] => 0
			 )
			
			 [interest_similarity] => stdClass Object (
				 [common_elements_found] => 0
				 [similarity_percentage] => 0
			 )
			
			 [competency_similarity] => stdClass Object (
				 [common_elements_found] => 0
				 [similarity_percentage] => 0
			 )
			
			 [preference_similarity] => stdClass Object (
				 [common_elements_found] => 1
				 [similarity_percentage] => 100
		 	 )
	 */
	public static function getUsersSimilarity($userId1, $userId2, $managerReference=EU4ALL_MANAGER_REFERENCE){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for an user id
		if(empty($userId1)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for an user id
		if(empty($userId2)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidUserId', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		// Checks for a manager reference
		if(empty($managerReference) || $managerReference=="EU4ALL_MANAGER_REFERENCE"):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('invalidManagerReference', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		try{
			$params = array(
				'manager_reference'=>$managerReference,
				'first_manager_userid'=>$userId1,
				'second_manager_userid'=>$userId2
			);
			$result = $client->getUsersSimilarity($params);
			
			if(isset($result->similarity)):
				return $result->similarity;
			else:
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME));
			endif;
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getUsersSimilarityFailed', EU4ALL_PLUGINNAME, $property_id)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getUsersSimilarityFailed', EU4ALL_PLUGINNAME, $property_id)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Get the version from the EU4ALL repository
	 * 
	 * @param string $url with the wsdl url to get the version from
	 * @return string the the version or boolean false
	 */
	public static function getVersion($url=NULL){
		$client = self::getSoapClientInstance($url);
		
		// Checks for a valid client (kind of)
		if(is_null($client)):
			self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		try{
			$params = array(
				'any'=>''
			);
			$result = $client->getVersion($params);
			
			if(isset($result->version)):
				return $result->version;
			else:
				self::log("[".EU4ALL." - UM ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME));
			endif;
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$fault->getLine()."] ".get_string('getVersionFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - UM ".__FUNCTION__."@".$ex->getLine()."] ".get_string('getVersionFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
}
