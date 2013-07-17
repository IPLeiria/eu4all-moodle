<?php
/**
 * Metadata repository library for the EU4ALL
 *
 * @package    	EU4ALL
 * @subpackage 	MR, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die(''); // silence is golden

// Load the common lib
require_once($CFG->dirroot.'/local/eu4all/lib.php');

if(!defined('EU4ALL_MR_USERNAME')):
	$username = get_config(EU4ALL_PLUGINNAME, 'eu4all_mr_username');
	define('EU4ALL_MR_USERNAME', (($username)?$username:'admin'));
endif;
if(!defined('EU4ALL_MR_PASSWORD')):
	$password = get_config(EU4ALL_PLUGINNAME, 'eu4all_mr_password');
	define('EU4ALL_MR_PASSWORD', (($password)?$password:'admin'));
endif;

/**
 * Reindex a relation array based on the resource id
 * @param array $relations
 */
function eu4all_mr_adaptable_reindex_relations(&$relations){
	$tmp = $relations;
	$relations = array();
	foreach ($tmp as $relation):
		if(isset($relation->resource_id)):
			$relations[$relation->resource_id] = $relation;
		endif;
	endforeach;
}

// Event handlers
/**
 * Handles the adaptable module creation on the central repository
 * 
 * @param object $eventdata with the module data
 * @return boolean with the result of the operation
 */
function eu4all_mr_adaptable_created_handler($eventdata){
	
	eu4all_mr_adaptable_updated_handler($eventdata);
	
	return true;
}

/**
 * Handles the adaptable module updating on the central repository
 * 
 * @param object $eventdata with the module data
 * @return boolean with the result of the operation
 */
function eu4all_mr_adaptable_updated_handler($eventdata){
	date_default_timezone_set('UTC');
	if(isset($eventdata->previousAdaptableRelations)):
		eu4all_mr_adaptable_reindex_relations($eventdata->previousAdaptableRelations);
	endif;
	// we will update the default resource
	if(isset($eventdata->defaultResource)):
		// we don't want to delete the resource, so removing from the $eventdata->previousAdaptableRelations list
		if(isset($eventdata->defaultResource->resource_id) && isset($eventdata->previousAdaptableRelations) && isset($eventdata->previousAdaptableRelations[$eventdata->defaultResource->resource_id])):
			unset($eventdata->previousAdaptableRelations[$eventdata->defaultResource->resource_id]);
		endif;
		
		// define the defaults values
		$eventdata->defaultResource->eu4all_learning_object_id = new stdClass();
		$eventdata->defaultResource->eu4all_learning_object_id->internalID = $eventdata->defaultResource->resource_id;
		$eventdata->defaultResource->eu4all_learning_object_id->owner = EU4ALL_MANAGER_REFERENCE;
		$eventdata->defaultResource->eu4all_learning_object_id->repository = new moodle_url('/mod/resource/view.php', array('id'=>$eventdata->defaultResource->resource_id));
		$eventdata->defaultResource->eu4all_media_object_id = new stdClass();
		$eventdata->defaultResource->eu4all_media_object_id->internalID = $eventdata->defaultResource->resource_id;
		$eventdata->defaultResource->eu4all_media_object_id->owner = EU4ALL_MANAGER_REFERENCE;
		$eventdata->defaultResource->eu4all_media_object_id->repository = new moodle_url('/mod/resource/view.php', array('id'=>$eventdata->defaultResource->resource_id));
		$eventdata->defaultResource->eu4all_MR_internal_id = $eventdata->defaultResource->resource_id;
		$eventdata->defaultResource->creation_date = date("Y-m-d");
		$eventdata->defaultResource->modification_date = date("Y-m-d");
		$eventdata->defaultResource->last = date("Y-m-d");
		$eventdata->defaultResource->version = 1;
		$eventdata->defaultResource->user_agent = "moodle";
		$eventdata->defaultResource->source = "moodle";
		
		if(EU4ALL_MetadataRepository::updateMD($eventdata->defaultResource)){
			// error_log("default resource {$eventdata->defaultResource->resource_id} updated");
		}else{
			// error_log("default resource {$eventdata->defaultResource->resource_id} not updated");
		}
	endif;
	
	// alternatives
	if(isset($eventdata->alternativeResources)):
		foreach($eventdata->alternativeResources as $alternative):
			// we don't want to delete the resource, so removing from the $eventdata->previousAdaptableRelations list
			if(isset($eventdata->previousAdaptableRelations) && isset($eventdata->previousAdaptableRelations[$alternative->resource_id])):
				unset($eventdata->previousAdaptableRelations[$alternative->resource_id]);
			endif;
			
			// define the defaults values
			$alternative->eu4all_learning_object_id = new stdClass();
			$alternative->eu4all_learning_object_id->internalID = $alternative->resource_id;
			$alternative->eu4all_learning_object_id->owner = EU4ALL_MANAGER_REFERENCE;
			$alternative->eu4all_learning_object_id->repository = new moodle_url('/mod/resource/view.php', array('id'=>$alternative->resource_id));
			$alternative->eu4all_media_object_id = new stdClass();
			$alternative->eu4all_media_object_id->internalID = $alternative->resource_id;
			$alternative->eu4all_media_object_id->owner = EU4ALL_MANAGER_REFERENCE;
			$alternative->eu4all_media_object_id->repository = new moodle_url('/mod/resource/view.php', array('id'=>$alternative->resource_id));
			$alternative->eu4all_MR_internal_id = $alternative->resource_id;
			$alternative->creation_date = date("Y-m-d");
			$alternative->modification_date = date("Y-m-d");
			$alternative->last = date("Y-m-d");
			$alternative->version = 1;
			$alternative->user_agent = "moodle";
			$alternative->source = "moodle";
			
			if(EU4ALL_MetadataRepository::updateMD($alternative)){
				// error_log("alternative resource {$alternative->resource_id} updated");
			}else{
				// error_log("alternative resource {$alternative->resource_id} not updated");
			}
		endforeach;
	endif;
	
	// delete the removed resources
	eu4all_mr_adaptable_deleted_handler($eventdata);
	
	return true;
}

/**
 * Handles the adaptable module deletion on the central repository
 * 
 * @param object $eventdata with the module data
 * @return boolean with the result of the operation
 */
function eu4all_mr_adaptable_deleted_handler($eventdata){
	if(isset($eventdata->previousAdaptableRelations)):
		foreach($eventdata->previousAdaptableRelations as $alternative):
			if(EU4ALL_MetadataRepository::deleteMD($alternative->resource_id)){
				// error_log("resource {$alternative->resource_id} deleted");
			}else{
				// error_log("resource {$alternative->resource_id} not deleted");
			}
		endforeach;
	endif;
	
	return true;
}

/**
 * EU4ALL Metada repository client to communicate with the central repository
 *
 * @package    	EU4ALL
 * @subpackage 	MR, local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class EU4ALL_MetadataRepository{
	const ModuleName='adaptable';
	private static $soapClient = NULL;
	
	/**
	 * We will be using a unique instance so this constructor will be private
	 * @param $wsdl with the service URL to use on the SoapClient linkupdating
	 * @uses global $CFG for the proxy settings
	 */
	private function __construct($wsdl=NULL){
		global $CFG;
		try{
			if(is_null($wsdl)):
				if(!($wsdl = get_config(EU4ALL_PLUGINNAME, 'eu4all_mr_wsdl_url'))):
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
	private static function log($message=NULL, $forceOutput=false){
		global $CFG;
		if(!is_null($message) && ($forceOutput || !empty($CFG->debug))){
			error_log($message);
		}
	}
	
	/**
	 * Create a XML document based on the resource data
	 * 
	 * @param stdClass $resource with the resource data
	 * @return String with the XML data
	 */
	private static function _extractXmlFromResource($resource){
		$p1Prefix = 'p1';
		$p2Prefix = 'p2';
		$p3Prefix = 'p3';
				
		$dom = new DOMDocument("1.0");
		$mr = $dom->createElementNS('http://eu4all.eu/MR', "$p1Prefix:MR");
		$mr->setAttribute("xmlns:$p2Prefix", 'http://ltsc.ieee.org/xsd/LOM');
		$mr->setAttribute("xmlns:$p3Prefix", 'http://eu4all.atosorigin.es/eu4all/MRSchema');
		$mr->setAttribute('xmlns:schemaLocation', 'http://eu4all.eu/MR file:///var/www/eu4all/eu4all/WEB-INF/classes/xsd/MR.xsd');
		$dom->appendChild($mr);
		
		//DRD part
		$DRDPart = $dom->createElement("$p1Prefix:DRDPart");
		$mr->appendChild($DRDPart);
		
		// original mode
		if(isset($resource->original_mode)):
			$element = $dom->createElement('access_mode_statement');
			$element->setAttribute('original_mode', $resource->original_mode);
			$DRDPart->appendChild($element);
		endif;
		
		if(isset($resource->type)){
			if(isset($resource->type) && $resource->type != 'default'):
				// adaptation of
				$element = $dom->createElement('is_adaptation');
				$element->setAttribute('extent', 'EU4ALL_M');
				if(isset($resource->adaptation_of)):
					$element->setAttribute('is_adaptation_of', EU4ALL_PREFIX.$resource->adaptation_of);
				endif;
				$DRDPart->appendChild($element);
				
				// adaptation
				$element = $dom->createElement('adaptation');
				if(isset($resource->adaptation_type)):
					$element->setAttribute('adaptation_type', $resource->adaptation_type);
				endif;
				if(isset($resource->original_access_mode)):
					$element->setAttribute('original_access_mode', $resource->original_access_mode);
				endif;
				if(isset($resource->representation_form) && strtolower($resource->representation_form)!='void'):
					$element->setAttribute('representation_form', $resource->representation_form);
				endif;
				$DRDPart->appendChild($element);
				
				
			endif;
		}
		
		// media object / resource id
		$element = $dom->createElement('eu4all_media_object_id');
		if(isset($resource->resource_id)):
			$element->setAttribute('eu4all_identifier', EU4ALL_PREFIX.$resource->resource_id);
		endif;
		$DRDPart->appendChild($element);
		
		// default resource
		if(isset($resource->type) && $resource->type == 'default'):
			if(isset($resource->original_content_type)):
				$element = $dom->createElement('eu4all_original_content_type', $resource->original_content_type);
				$DRDPart->appendChild($element);
			endif;
		endif;
				
		// MR part
		$MRPart = $dom->createElement("$p1Prefix:MRPart");
		$mr->appendChild($MRPart);
		
		// learning object identification
		if(isset($resource->eu4all_learning_object_id)):
			$element = $dom->createElement("$p3Prefix:eu4all_learning_object_id");
			$MRPart->appendChild($element);
			
			if(isset($resource->eu4all_learning_object_id->internalID)):
				$element->setAttribute('internalID', EU4ALL_PREFIX.$resource->eu4all_learning_object_id->internalID);
			endif;
			if(isset($resource->eu4all_learning_object_id->owner)):
				$element->setAttribute('owner', $resource->eu4all_learning_object_id->owner);
			endif;
			if(isset($resource->eu4all_learning_object_id->repository)):
				$element->setAttribute('repository', $resource->eu4all_learning_object_id->repository);
			endif;
		endif;
		
		// media object identification
		if(isset($resource->eu4all_media_object_id)):
			$element = $dom->createElement("$p3Prefix:eu4all_media_object_id");
			$MRPart->appendChild($element);
			
			if(isset($resource->eu4all_media_object_id->internalID)):
				$element->setAttribute('internalID', EU4ALL_PREFIX.$resource->eu4all_media_object_id->internalID);
			endif;
			if(isset($resource->eu4all_media_object_id->owner)):
				$element->setAttribute('owner', $resource->eu4all_media_object_id->owner);
			endif;
			if(isset($resource->eu4all_media_object_id->repository)):
				$element->setAttribute('repository', $resource->eu4all_media_object_id->repository);
			endif;
		endif;
		
		// MR internal identification
		if(isset($resource->eu4all_MR_internal_id)):
			$element = $dom->createElement("$p3Prefix:eu4all_MR_internal_id", EU4ALL_PREFIX.$resource->eu4all_MR_internal_id);
			$MRPart->appendChild($element);
		endif;
		
		// Creation date
		if(isset($resource->creation_date)):
			$element = $dom->createElement("$p3Prefix:creation_date", $resource->creation_date);
			$MRPart->appendChild($element);
		endif;
		
		// Modification date
		if(isset($resource->modification_date)):
			$element = $dom->createElement("$p3Prefix:modification_date", $resource->modification_date);
			$MRPart->appendChild($element);
		endif;
		
		// Last date
		if(isset($resource->last)):
			$element = $dom->createElement("$p3Prefix:last", $resource->last);
			$MRPart->appendChild($element);
		endif;
		
		// Version
		if(isset($resource->version)):
			$element = $dom->createElement("$p3Prefix:version", $resource->version);
			$MRPart->appendChild($element);
		endif;
		
		// User agent
		if(isset($resource->user_agent)):
			$element = $dom->createElement("$p3Prefix:user_agent", $resource->user_agent);
			$MRPart->appendChild($element);
		endif;
		
		// Source
		if(isset($resource->source)):
			$element = $dom->createElement("$p3Prefix:source", $resource->source);
			$MRPart->appendChild($element);
		endif;
		
		return $dom->saveXML($mr);
	}
	
	/**
	 * Given the XML try to extract the resource data
	 * 
	 * @param String $xmlString with the XML data to parse
	 * @return stdClass instance with the resource data
	 */
	private static function _extractResourceFromXml($xmlString){
		$resource = new stdClass();
				
		if(!empty($xmlString) && $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', 0, 'http://eu4all.eu/MR')){
			$p1Prefix = 'p1';
			$p2Prefix = 'p2';
			$p3Prefix = 'p3';
			
			$xml->registerXPathNamespace($p1Prefix,'http://eu4all.eu/MR');
			$xml->registerXPathNamespace($p2Prefix,'http://ltsc.ieee.org/xsd/LOM');
			$xml->registerXPathNamespace($p3Prefix,'http://eu4all.atosorigin.es/eu4all/MRSchema');
			
			$resource->type = 'default';
			
			// original mode
			if($node = $xml->xpath("//$p1Prefix:DRDPart/access_mode_statement")):
				if(isset($node[0]->attributes()->original_mode)):
					$resource->original_mode = "{$node[0]->attributes()->original_mode}";
				endif;
			endif;
			
			// resource id
			if($node = $xml->xpath("//$p1Prefix:DRDPart/eu4all_media_object_id")):
				if(isset($node[0]->attributes()->eu4all_identifier)):
					$resource->resource_id = str_ireplace(EU4ALL_PREFIX, '' , "{$node[0]->attributes()->eu4all_identifier}");
				endif;
			endif;
			
			// original content type (default only)
			if($node = $xml->xpath("//$p1Prefix:DRDPart/eu4all_original_content_type")):
				$resource->original_content_type = "{$node[0]}";
			endif;
			
			// set the type to alternative if it is an adaptation (alternative only)
			if($node = $xml->xpath("//$p1Prefix:DRDPart/is_adaptation")):
				if(isset($node[0]->attributes()->is_adaptation_of)):
					$resource->type = 'alternative';
					
					if(isset($node[0]->attributes()->is_adaptation_of)):
						$resource->adaptation_of = str_ireplace(EU4ALL_PREFIX, '' , "{$node[0]->attributes()->is_adaptation_of}");
					endif;
				endif;
			endif;
			
			// adaptation_type, original access mode and representation_form (alternative only)
			if($node = $xml->xpath("//$p1Prefix:DRDPart/adaptation")):
				if(isset($node[0]->attributes()->adaptation_type)):
					$resource->adaptation_type = "{$node[0]->attributes()->adaptation_type}";
				endif;
				
				if(isset($node[0]->attributes()->original_access_mode)):
					$resource->original_access_mode = "{$node[0]->attributes()->original_access_mode}";
				endif;
				
				if(isset($node[0]->attributes()->representation_form)):
					$resource->representation_form = "{$node[0]->attributes()->representation_form}";
				else:
					$resource->representation_form = 'void';
				endif;
			endif;
			
			// learning object identification
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:eu4all_learning_object_id")):
				$resource->eu4all_learning_object_id = new stdClass();
				
				if(isset($node[0]->attributes()->internalID)):
					$resource->eu4all_learning_object_id->internalID = str_ireplace(EU4ALL_PREFIX, '' , "{$node[0]->attributes()->internalID}");
				endif;
				
				if(isset($node[0]->attributes()->owner)):
					$resource->eu4all_learning_object_id->owner = "{$node[0]->attributes()->owner}";
				endif;
				
				if(isset($node[0]->attributes()->repository)):
					$resource->eu4all_learning_object_id->repository = "{$node[0]->attributes()->repository}";
				endif;
			endif;
			
			// media object identification
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:eu4all_media_object_id")):
				$resource->eu4all_media_object_id = new stdClass();
				
				if(isset($node[0]->attributes()->internalID)):
					$resource->eu4all_media_object_id->internalID = str_ireplace(EU4ALL_PREFIX, '' , "{$node[0]->attributes()->internalID}");
				endif;
				
				if(isset($node[0]->attributes()->owner)):
					$resource->eu4all_media_object_id->owner = "{$node[0]->attributes()->owner}";
				endif;
				
				if(isset($node[0]->attributes()->repository)):
					$resource->eu4all_media_object_id->repository = "{$node[0]->attributes()->repository}";
				endif;
			endif;
			
			// MR internal identification
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:eu4all_MR_internal_id")):
				$resource->eu4all_MR_internal_id = str_ireplace(EU4ALL_PREFIX, '' , "{$node[0]}");
			endif;
			
			// Creation date
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:creation_date")):
				$resource->creation_date = "{$node[0]}";
			endif;
			
			// Modification date
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:modification_date")):
				$resource->modification_date = "{$node[0]}";
			endif;
			
			// Last date
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:last")):
				$resource->last = "{$node[0]}";
			endif;
			
			// Version
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:version")):
				$resource->version = "{$node[0]}";
			endif;
			
			// User agent
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:user_agent")):
				$resource->user_agent = "{$node[0]}";
			endif;
			
			// Source
			if($node = $xml->xpath("//$p1Prefix:MRPart/$p3Prefix:source")):
				$resource->source = "{$node[0]}";
			endif;
		}
		return $resource;
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
	 * Get the service information from EU4ALL repository
	 * 
	 * @param string $url with the wsdl url to get the version from
	 * @return string the the version or boolean false
	 */
	public static function serviceInformation($url=NULL){
		$client = self::getSoapClientInstance($url);
		
		// Checks for a valid client (kind of)
		if(is_null($client)):
			self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		endif;
		
		try{
			$params = array();
			$result = $client->serviceInformation($params);
			
			if(isset($result->return)):
				return $result->return;
			else:
				self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME));
			endif;
		}catch(SoapFault $fault){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$fault->getLine()."] ".get_string('serviceInformationFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$ex->getLine()."] ".get_string('serviceInformationFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Get the metadata for the resource identifier
	 * 
	 * @param String $resourceIdentifier with the resource identifier
	 * @return stdClass instance with a resource, boolean false on error
	 */
	public static function retrieveMD($resourceIdentifier=false){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = array();
			if(!empty($resourceIdentifier)){
				$resource = new stdClass();
				$resource->resource_id = $resourceIdentifier;
				$params['retrieveMD'] = new SoapVar(array('retrieveMD'=>new SoapVar(self::_extractXmlFromResource($resource), XSD_ANYXML)), SOAP_ENC_OBJECT);
			}
			
			$result = $client->retrieveMD($params);
			
			if(isset($result->operationType) && $result->operationType == 'OK' && !empty($result->message)){
				return self::_extractResourceFromXml($result->message);
			}else{
				self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->message})");
			}
		}catch(SoapFault $fault){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$fault->getLine()."] ".get_string('retrieveMDFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()})");
		}catch(Exception $ex){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$ex->getLine()."] ".get_string('retrieveMDFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Insert the metadata for the specified resource
	 * 
	 * @param stdClass $resource with the resource to insert
	 * @return boolean true on success, false otherwise
	 */
	public static function insertMD($resource=NULL){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = new stdClass();
			$params->rdfInstance = new stdClass();
			$params->rdfInstance->rdfInstance = new SoapVar(self::_extractXmlFromResource($resource), XSD_ANYXML);
			$params->username = EU4ALL_MR_USERNAME;
			$params->password = EU4ALL_MR_PASSWORD;
			$params->overwrite = true;
			
			$result = $client->insertMD($params);
			
			if(isset($result->operationType) && $result->operationType == 'OK'){
				return true;
			}else{
				self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->message})");
			}
		}catch(SoapFault $fault){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$fault->getLine()."] ".get_string('insertMDFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()})");
			
			error_log(print_r($client->__getLastRequest(),true));
		}catch(Exception $ex){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$ex->getLine()."] ".get_string('insertMDFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Update the metadata for the specified resource
	 * 
	 * @param stdClass $resource with the resource to update
	 * @return boolean true on success, false otherwise
	 */
	public static function updateMD($resource=NULL){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = new stdClass();
			$params->rdfInstance = new stdClass();
			$params->rdfInstance->rdfInstance = new SoapVar(self::_extractXmlFromResource($resource), XSD_ANYXML);
			$params->username = EU4ALL_MR_USERNAME;
			$params->password = EU4ALL_MR_PASSWORD;
			
			$result = $client->updateMD($params);
			
			if(isset($result->operationType) && $result->operationType == 'OK'){
				return true;
			}else{
				echo("<pre>".print_r($result,true)."</pre>");

				self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->message})");
			}
		}catch(SoapFault $fault){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$fault->getLine()."] ".get_string('updateMDFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()})");
			
			error_log(print_r($client->__getLastRequest(),true));
		}catch(Exception $ex){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$ex->getLine()."] ".get_string('updateMDFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		
		return false;
	}
	
	/**
	 * Deletes the metadata for the resource identifier
	 * 
	 * @param String $resourceIdentifier with the resource identifier
	 * @return boolean true on success, false otherwise
	 */
	public static function deleteMD($resourceIdentifier=false){
		$client = self::getSoapClientInstance();
		
		// Checks for a valid client (kind of)
		if(is_null($client)){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unableToCreateSoapClientInstance', EU4ALL_PLUGINNAME));
			return false;
		}
		
		try{
			$params = new stdClass();
			if(!empty($resourceIdentifier)){
				$resource = new stdClass();
				$resource->resource_id = $resourceIdentifier;
				$params->rdfInstance = new stdClass();
				$params->rdfInstance->rdfInstance = new SoapVar(self::_extractXmlFromResource($resource), XSD_ANYXML);
			}
			$params->username = EU4ALL_MR_USERNAME;
			$params->password = EU4ALL_MR_PASSWORD;
			
			$result = $client->deleteMD($params);
			
			if(isset($result->operationType) && $result->operationType == 'OK' && !empty($result->message)){
				return true;
			}else{
				self::log("[".EU4ALL." - MR ".__FUNCTION__."] ".get_string('unknownResponseCode', EU4ALL_PLUGINNAME)." ({$result->message})");
			}
		}catch(SoapFault $fault){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$fault->getLine()."] ".get_string('deleteMDFailed', EU4ALL_PLUGINNAME)." ({$fault->getMessage()})");
			error_log(print_r($client->__getLastRequest(),true));
		}catch(Exception $ex){
			self::log("[".EU4ALL." - MR ".__FUNCTION__."@".$ex->getLine()."] ".get_string('deleteMDFailed', EU4ALL_PLUGINNAME)." (".$ex->getMessage().")");
		}
		return false;
	}
}


