<?php
 
class block_vpl_aria_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
 
	$mform->addElement('text', 'config_gregex', 'Regex on grade');
	$mform->setDefault('config_gregex', '');
        $mform->addElement('text', 'config_cregex', 'Regex on comments');
        $mform->setDefault('config_cregex', '');
        $mform->addElement('text', 'config_eregex', 'Regex on execution');
        $mform->setDefault('config_eregex', '');
        $mform->setType('config_gregex', PARAM_RAW);        
        $mform->setType('config_cregex', PARAM_RAW);
        $mform->setType('config_eregex', PARAM_RAW);
 
    }
}
