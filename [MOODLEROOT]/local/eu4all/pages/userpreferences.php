<?php
/**
 * User preferences view page for the EU4ALL user model component
 *
 * @package    	UM, EU4ALL
 * @subpackage 	local_eu4all
 * @version		2010.1
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/local/eu4all/lib/umlib.php');
require_once('./forms/um_user_preferences_edit_form.php');

define('EU4ALLMODULENAME', 'local_eu4all');

$url = new moodle_url('/local/eu4all/pages/userpreferences.php');
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
    //view own message profile
    require_capability('local/eu4all:umviewownprofile', $systemcontext);
} else {
    // teachers, parents, etc.
    require_capability('local/eu4all:umviewownprofile', $personalcontext);
    // no view of guest user account
    if (isguestuser($user->id)) {
        print_error('guestCannotViewPreferencesError', EU4ALLMODULENAME);
    }
    // no editing of admins by non admins!
    if (is_siteadmin($user) and !is_siteadmin($USER)) {
        print_error('useradmineditadmin');
    }
    //$PAGE->navigation->extend_for_user($user);
}

//header("content-type: text/xml;");
//die(EU4ALL_UserModel::getUserModel($USER->username));

// output layout
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('viewAccessibilityPreferences', EU4ALLMODULENAME));
$PAGE->set_title(get_string('viewAccessibilityPreferences', EU4ALLMODULENAME));
echo($OUTPUT->header());

$userProfile = EU4ALL_UserModel::getUserModelAttributes($USER->username);

if(!empty($userProfile)):
	?>
	<div class="userprofile" style="border:1px solid #DDDDDD; padding:10px; background-color: #FFFFFF;">
		<table class="list" summary="<?php print_string('viewAccessibilityPreferences', EU4ALLMODULENAME); ?>">
			<?php if(isset($userProfile->firstName)): ?>
				<tr>
					<td class="label c0"><?php print_string('firstName', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php echo($userProfile->firstName); ?></td>
				</tr>
			<?php endif; ?>
			<?php if(isset($userProfile->lastName)): ?>
				<tr>
					<td class="label c0"><?php print_string('lastName', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php echo($userProfile->lastName); ?></td>
				</tr>
			<?php endif; ?>
			<?php if(isset($userProfile->email)): ?>
				<tr>
					<td class="label c0"><?php print_string('email', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php echo($userProfile->email); ?></td>
				</tr>
			<?php endif; ?>
			<?php if(isset($userProfile->gender)): ?>
				<tr>
					<td class="label c0"><?php print_string('gender', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php print_string("gender{$userProfile->gender}", EU4ALLMODULENAME); ?></td>
				</tr>
			<?php endif; ?>
			<?php if(isset($userProfile->birthDate)): ?>
				<tr>
					<td class="label c0"><?php print_string('birthDate', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php echo(date("Y-m-d", um_user_preferences_edit_form::parseDateFromIso8601($userProfile->birthDate))); ?></td>
				</tr>
			<?php endif; ?>
			<?php if(isset($userProfile->birthPlace)): ?>
				<tr>
					<td class="label c0"><?php print_string('birthPlace', EU4ALLMODULENAME); ?>:</td>
					<td class="info c1"><?php echo($userProfile->birthPlace); ?></td>
				</tr>
			<?php endif; ?>
			<?php 
				$contentTypes = array('text', 'audio', 'image', 'video', 'multimedia');
				foreach($contentTypes as $contentType){
					if(isset($userProfile->{$contentType."Alternative"})):
						if(!empty($userProfile->{$contentType."Alternative"})):
							$alternative = $userProfile->{$contentType."Alternative"}; 
							?>
								<tr>
									<td class="label c0" style="vertical-align: top;"><?php print_string("{$contentType}Alternative", EU4ALLMODULENAME); ?>:</td>
									<td class="info c1">
										<table class="list" summary="<?php print_string("{$contentType}Alternative", EU4ALLMODULENAME); ?>">
											<?php if(isset($alternative->usage)): ?>
												<tr>
													<td class="label c0"><?php print_string("{$contentType}AlternativeUsage", EU4ALLMODULENAME); ?>:</td>
													<td class="info c1"><?php print_string("usage{$alternative->usage}", EU4ALLMODULENAME); ?></td>
												</tr>
											<?php endif; ?>
											<?php if(isset($alternative->adaptationType)): ?>
												<tr>
													<td class="label c0"><?php print_string("{$contentType}AlternativeAdaptationType", EU4ALLMODULENAME); ?>:</td>
													<td class="info c1"><?php print_string("adaptationType{$alternative->adaptationType}", EU4ALLMODULENAME); ?></td>
												</tr>
											<?php endif; ?>
											<?php if(isset($alternative->originalAccessMode)): ?>
												<tr>
													<td class="label c0"><?php print_string("{$contentType}AlternativeOriginalAccessMode", EU4ALLMODULENAME); ?>:</td>
													<td class="info c1"><?php print_string("accessMode{$alternative->originalAccessMode}", EU4ALLMODULENAME); ?></td>
												</tr>
											<?php endif; ?>
											<?php if(isset($alternative->representationForm) && $alternative->representationForm!='void'): ?>
												<tr>
													<td class="label c0"><?php print_string("{$contentType}AlternativeRepresentationForm", EU4ALLMODULENAME); ?>:</td>
													<td class="info c1"><?php print_string("representationForm{$alternative->representationForm}", EU4ALLMODULENAME); ?></td>
												</tr>
											<?php endif; ?>
										</table>
								</tr>
							<?php 
						endif; 
					endif; 
					if(isset($userProfile->{$contentType."Enhancement"})):
						if(!empty($userProfile->{$contentType."Enhancement"})):
							$enhancements = $userProfile->{$contentType."Enhancement"}; 
							foreach($enhancements as $a=>$enhancement){
								?>
									<tr>
										<td class="label c0" style="vertical-align: top;"><?php print_string("{$contentType}Enhancement", EU4ALLMODULENAME, ($a+1)); ?>:</td>
										<td class="info c1">
											<table class="list" summary="<?php print_string("{$contentType}Enhancement", EU4ALLMODULENAME, ($a+1)); ?>">
												<?php if(isset($enhancement->usage)): ?>
													<tr>
														<td class="label c0"><?php print_string("{$contentType}EnhancementUsage", EU4ALLMODULENAME); ?>:</td>
														<td class="info c1"><?php print_string("usage{$enhancement->usage}", EU4ALLMODULENAME); ?></td>
													</tr>
												<?php endif; ?>
												<?php if(isset($enhancement->adaptationType)): ?>
													<tr>
														<td class="label c0"><?php print_string("{$contentType}EnhancementAdaptationType", EU4ALLMODULENAME); ?>:</td>
														<td class="info c1"><?php print_string("adaptationType{$enhancement->adaptationType}", EU4ALLMODULENAME); ?></td>
													</tr>
												<?php endif; ?>
												<?php if(isset($enhancement->originalAccessMode)): ?>
													<tr>
														<td class="label c0"><?php print_string("{$contentType}EnhancementOriginalAccessMode", EU4ALLMODULENAME); ?>:</td>
														<td class="info c1"><?php print_string("accessMode{$enhancement->originalAccessMode}", EU4ALLMODULENAME); ?></td>
													</tr>
												<?php endif; ?>
											</table>
									</tr>
								<?php 
							}
						endif; 
					endif; 
				}
			?>
		</table>
	</div>
	<?php 
else:
	print_string('noProfileFound', EU4ALLMODULENAME);
endif;
?><a href="<?php echo(new moodle_url('/local/eu4all/pages/userpreferencesedit.php')); ?>"><?php echo(get_string('editAccessibilityPreferences', EU4ALLMODULENAME)); ?></a><?php 
echo($OUTPUT->footer());