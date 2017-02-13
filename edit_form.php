<?php

class block_vpl_aria_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        $mform->addElement('advcheckbox', 'config_divvisbile', 'Debug mode','',array(), array(0, 1));
        $mform->addElement('text', 'config_editkeysc', 'Array of keys for edit shortcut as [14,22,188]');
        $mform->setDefault('config_editkeysc', '');
        $mform->addElement('text', 'config_savekeysc', 'Array of keys for save shortcut as [14,22,188]');
        $mform->setDefault('config_savekeysc', '');
        $mform->addElement('text', 'config_runkeysc', 'Array of keys for run shortcut as [14,22,188]');
        $mform->setDefault('config_runkeysc', '');
        $mform->addElement('text', 'config_evalkeysc', 'Array of keys for evaluate shortcut as [14,22,188]');
        $mform->setDefault('config_evalkeysc', '');
        $mform->addElement('text', 'config_gregex', 'Regex on grade');
        $mform->setDefault('config_gregex', '');
        $mform->addElement('text', 'config_cregex', 'Regex on comments');
        $mform->setDefault('config_cregex', '');
        $mform->addElement('text', 'config_eregex', 'Regex on execution');
        $mform->setDefault('config_eregex', '');
        $mform->setType('config_editkeysc', PARAM_RAW);
        $mform->setType('config_savekeysc', PARAM_RAW);
        $mform->setType('config_runkeysc', PARAM_RAW);
        $mform->setType('config_evalkeysc', PARAM_RAW);
        $mform->setType('config_gregex', PARAM_RAW);
        $mform->setType('config_cregex', PARAM_RAW);
        $mform->setType('config_eregex', PARAM_RAW);

    }
}
