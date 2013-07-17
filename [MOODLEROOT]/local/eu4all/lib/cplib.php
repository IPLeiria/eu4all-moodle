<?php

/**
 * Content personalization model library for the EU4ALL
 *
 * @package    	EU4ALL
 * @subpackage 	CP, local_eu4all
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/local/eu4all/lib.php');

// The WSDL for the CP web service
//define("EU4ALL_CP_WSDL_URL", "http://www.einclusion-projects.com:8080/EU4ALL/UNED/CP/services/personalizeResourceService?wsdl");

//define("EU4ALL_PREFIX", 'IPL_');
if(($wsdl = get_config(EU4ALL_PLUGINNAME, 'eu4all_cp_wsdl_url'))):
  define("EU4ALL_CP_WSDL_URL", $wsdl);
else:
  define("EU4ALL_CP_WSDL_URL", "http://einclusion-projects.com:8080/EU4ALL/IPL/CPv3/services/personalizeResourceService?wsdl");
endif;

/**
 * Creates a SOAP Client for the CP Web Service
 *
 * @return The SOAP client object or NULL if there was an error creating it
 */
function eu4all_create_cp_soap_client() {
	global $CFG;
	try {		
				
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
			
		$client = new SoapClient('../../local/eu4all/lib/wsdl/personalizeResourceService.wsdl', $options);
		

	} catch (Exception $ex) {
		error_log('Could not establish link with Content Personalisation web service.');
		return ;
	}

	return $client;
}


/**
 * Gets the adapted resource from the CP SOAP web service for a specific Moodle resource ID. 
 *
 * @param integer $resourceid The Moodle Resource ID of the resource for which we would like a suggestion of an alternative
 * @return integer The Moodle Resource ID of the adapted recourse or NULL if there was an error obtaining it 
 * or no alternative resource suggested
 */
function eu4all_get_adapted_resource($resourceid) {
	global $USER;
	$adaptedresourceid = $resourceid;	

	ini_set('soap.wsdl_cache_enabled', 0);
	ini_set('default_socket_timeout', 200);
			
	$client = eu4all_create_cp_soap_client();

	if($client) {
		try{
									
			$params = array();
			$params['userIdentifier'] = $USER->username;
			$params['resourceIdentifier'] = EU4ALL_PREFIX.$resourceid; // Prefix the ID
			$params['platformIdentifier'] = EU4ALL_MANAGER_REFERENCE;
						
			$result = $client->personalizeResource($params);
			
			//die("<pre>1".print_r($client,true)."</pre>");
			
			if(isset($result->resourceBase) ){
				// Display the result
				$encodedresource = $result->resourceBase->id;
				$adaptedresourceid = substr_replace($encodedresource, '', 0, strlen(EU4ALL_PREFIX));
				return $adaptedresourceid;
			}
		}catch(SoapFault $fault){
			$extra = (isset($fault->detail->ServiceFault->MessageError)?$fault->detail->ServiceFault->MessageError:'');
			error_log("[EU4ALL - CP ".__FUNCTION__."@".$fault->getLine()."] personalizeResource - SoapFault"." ({$fault->getMessage()}".(!empty($extra)?": $extra":'').")");
			//die("<pre>2".print_r($client,true)."</pre>");
		}catch(Exception $ex){
			error_log("[EU4ALL - CP ".__FUNCTION__."@".$ex->getLine()."] personalizeResource - Exception(".$ex->getMessage().")");
			//die("<pre>3".print_r($client,true)."</pre>");
		}
	}
	
	unset($client);


	return $adaptedresourceid;

}


?>