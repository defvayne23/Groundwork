<?php
### CORE ######################################
$aConfig['env'] = 'Development'; // Production, Development
$aConfig['base'] = ''; // Base URL. If not set, will try to generate automatically. (http://example.com/)
$aConfig['logThreshold'] = 4; // 0 = Off, 1 = Error, 2 = Notice, 3 = Debug, 4 = Info, 5 = All
###############################################

### OPTIONS ###################################
$aConfig['options']['timezone'] = 'America/Chicago';
$aConfig['options']['formatDate'] = 'y-m-d';
$aConfig['options']['formatTime'] = 'h:i a';
###############################################

### DATABASE ##################################
$aConfig['database']['connect'] = false;
$aConfig['database']['host'] = '';
$aConfig['database']['username'] = '';
$aConfig['database']['password'] = '';
$aConfig['database']['database'] = '';
$aConfig['database']['fetch'] = 'assoc'; // object, assoc, ordered
###############################################

### AUTOLOAD ##################################
$aConfig['autoLoad']['model'] = array(
	// array('model_name'[, 'set_name'])
	// 'model_name'
);
$aConfig['autoLoad']['helper'] = array(
	// 'helper_name'
);
###############################################