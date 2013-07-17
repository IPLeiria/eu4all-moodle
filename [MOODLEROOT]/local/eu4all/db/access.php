<?php
$capabilities = array(
	'local/eu4all:configureplugin' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'archetypes' => array(
			'guest' => CAP_PROHIBIT,
			'user' => CAP_PROHIBIT,
			'manager' => CAP_PROHIBIT
		)
	),
	'local/eu4all:umviewownprofile' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_MODULE,
		'archetypes' => array(
			'guest' => CAP_PROHIBIT,
			'user' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		)
	),
	'local/eu4all:umeditownprofile' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'archetypes' => array(
			'guest' => CAP_PROHIBIT,
			'user' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		)
	),
);