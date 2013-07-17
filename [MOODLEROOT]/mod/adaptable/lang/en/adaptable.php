<?php

/**
 * Strings for component 'adaptable', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>, Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['clicktoopen'] = 'Click {$a} link to open adaptable.';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['configframesize'] = 'When a web page or an uploaded file is displayed within a frame, this value is the height (in pixels) of the top frame (which contains the navigation).';
$string['configrolesinparams'] = 'Enable if you want to include localized role names in list of avaible parameter variables.';
$string['configsecretphrase'] = 'This secret phrase is used to produce encrypted code value that can be sent to some servers as a parameter.  The encrypted code is produced by an md5 value of the current user IP address concatenated with your secret phrase. ie code = md5(IP.secretphrase). Please note that this is not reliable because IP address may change and is often shared by different computers.';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselectexplain'] = 'Choose display type, unfortunately not all types are suitable for all adaptable.';
$string['framesize'] = 'Frame height';
$string['chooseavariable'] = 'Choose a variable...';
$string['modulename'] = 'Adaptable';
$string['name'] = 'Adaptable';
$string['modulenameplural'] = 'Adaptables';
$string['neverseen'] = 'Never seen';
$string['optionsheader'] = 'Options';
$string['parameterinfo'] = 'parameter=variable';
$string['parametersheader'] = 'Parameters';
$string['pluginname'] = 'Adaptable';
$string['popupheight'] = 'Popup height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Popup width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printheading'] = 'Display adaptable name';
$string['printheadingexplain'] = 'Display adaptable name above content? Some display types may not display adaptable name even if enabled.';
$string['printintro'] = 'Display adaptable description';
$string['printintroexplain'] = 'Display adaptable description bellow content? Some display types may not display description even if enabled.';
$string['rolesinparams'] = 'Include role names in parameters';
$string['serverurl'] = 'Server adaptable';
$string['urladministration'] = 'adaptable administration';

$string['defaultresourceheader'] = 'Default resource';
$string['intro'] = 'Introduction';
$string['defaultResource'] = 'Resource';
$string['defaultResource_help'] = 'The resource to show by default';
$string['defaultResourceOriginalMode'] = 'Original mode';
$string['defaultResourceOriginalMode_help'] = 'The original mode of the default resource';
$string['defaultResourceOriginalContentType'] = 'Original content type';
$string['defaultResourceOriginalContentType_help'] = 'The original content type of the default resource';
$string['err_usedResourceAsDefaultResource'] = 'The same resource cannot be used as the default and as the alternative. Please, select another resource.';
$string['err_usedResourceInAnotherAlternativeResource'] = 'The same resource is being used at alternative {$a}. Please, select another resource.';
$string['err_atLeastAnAssociatedResourceMustExist'] = 'You must have at least one unassociated resource to set as the default resource.';
$string['err_youCannotAddThisResource'] = 'You cannot add this resource.';

$string['addResourceAlternativeHeader'] = 'Add resource alternative';
$string['resourceAlternativeAdd'] = 'Add a new resource alternative';
$string['resourceAlternativeRemove'] = 'Remove resource alternative {$a}';
$string['resourceAlternative'] = 'Resource alternative {$a}:';
$string['resourceAlternativeResource'] = 'Alternative resource';
$string['resourceAlternativeResource_help'] = 'The alternative resource for the default resource';
$string['resourceAlternativeOriginalMode'] = 'Original mode';
$string['resourceAlternativeOriginalMode_help'] = 'Original mode for the alternative';
$string['resourceAlternativeAdaptationType'] = 'Adaptation type';
$string['resourceAlternativeAdaptationType_help'] = 'Adaptation type for of alternative';
$string['resourceAlternativeRepresentationForm'] = 'Representation form';
$string['resourceAlternativeRepresentationForm_help'] = 'Representation form of the alternative';

// adaptation type
$string['adaptationTypeAU'] = 'audio representation (AU)';
$string['adaptationTypeVI'] = 'visual representation (VI)';
$string['adaptationTypeTE'] = 'text representation (TE)';
$string['adaptationTypeTA'] = 'tactile representation (TA)';
$string['adaptationTypeCA'] = 'caption (CA)';
$string['adaptationTypeAD'] = 'audio description (AD)';
$string['adaptationTypeBR'] = 'braille (BR)';
$string['adaptationTypeDI'] = 'digital talking book (DI)';
$string['adaptationTypeEL'] = 'electronic book (EL)';

// access mode
$string['accessModeV'] = 'visual (V)';
$string['accessModeX'] = 'textual (X)';
$string['accessModeA'] = 'auditory (A)';
$string['accessModeT'] = 'tactil (T)';
$string['accessModeO'] = 'olfactory (O)';

// representation form
$string['representationFormVoid'] = 'void';
$string['representationFormEN'] = 'enhanced (EN)';
$string['representationFormVE'] = 'verbatim (VE)';
$string['representationFormRD'] = 'reduced (RD)';
$string['representationFormRT'] = 'real-time (RT)';
$string['representationFormTR'] = 'transcript (TR)';
$string['representationFormAL'] = 'alternative text (AL)';
$string['representationFormLO'] = 'long description (LO)';
$string['representationFormSI'] = 'sign language (SI)';
$string['representationFormIM'] = 'image-based (IM)';
$string['representationFormSY'] = 'symbolic (SY)';
$string['representationFormRE'] = 'recorded (RE)';
$string['representationFormSZ'] = 'synthesized (SZ)';
$string['representationFormHA'] = 'haptic (HA)';
