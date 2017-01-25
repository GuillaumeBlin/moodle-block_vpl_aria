<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;

/**
 * Block vpl_aria class definition.
 *
 * This block can be added to a vpl page to support aria for feedback
 *
 * @package    block_vpl_aria
 * @copyright  2016 Guillaume Blin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_vpl_aria extends block_base {

  function init() {
    $this->title = get_string('pluginname', 'block_vpl_aria');
  }

  function applicable_formats() {
    return array('all' => false,'mod-vpl-*' => false, 'mod-vpl-forms-edit' => true);
  }

  function instance_config_save($data, $nolongerused = false) {
    parent::instance_config_save($data);
  }


  function get_content() {
    global $CFG, $OUTPUT, $DB, $USER;
    $this->content = new stdClass();
    $this->content->items = array();
    $this->content->icons = array();
    $this->content->footer = '';
    $id=$this->page->course->id;
    if(strpos($this->page->url, 'mod/vpl/forms/edit') === false){
        $this->content->text='';	
    }else{
	$this->content->text ="<div style='width:1px; height:1px; overflow:hidden;'>ARIA UB CAPABILITIES ADDON- 
<h5>vpl grade</h5><div id='aria_grade' role='region' aria-live='polite' aria-relevant='all' aria-atomic='true' aria-busy='false'></div> <h5>vpl comments</h5><div aria-relevant='all' role='region'  id='aria_comments' aria-live='polite' aria-atomic='true' aria-busy='false'></div><h5>vpl execution</h5><div style='width:1px; height:1px; overflow:hidden;' id='aria_execution' aria-live='polite' aria-atomic='true' aria-busy='false'></div></div>";
    $this->content->text .="<script type=\"text/javascript\">";
    $this->content->text .="var target = document.getElementById('vpl_results');";
    $this->content->text .="var observer = new MutationObserver(function(mutations) {";
$this->content->text .="document.getElementById('aria_grade').textContent='';document.getElementById('aria_comments').textContent='';document.getElementById('aria_execution').textContent='';document.getElementById('aria_grade').setAttribute('aria-busy', 'true');document.getElementById('aria_comments').setAttribute('aria-busy', 'true');document.getElementById('aria_execution').setAttribute('aria-busy', 'true');";
    $this->content->text .="  console.log(document.getElementById('vpl_results').textContent);";
$this->content->text .="document.getElementById('aria_grade').textContent=document.getElementById('ui-accordion-vpl_results-header-0').textContent".$this->config->gregex.";";
$this->content->text .="document.getElementById('aria_comments').textContent=document.getElementById('ui-accordion-vpl_results-panel-1').textContent".$this->config->cregex.";";
$this->content->text .="document.getElementById('aria_execution').textContent=document.getElementById('ui-accordion-vpl_results-panel-2').textContent".$this->config->eregex.";";
$this->content->text .="document.getElementById('aria_grade').setAttribute('aria-busy', 'false');document.getElementById('aria_comments').setAttribute('aria-busy', 'false');document.getElementById('aria_execution').setAttribute('aria-busy', 'false');";
$this->content->text .="});";
    $this->content->text .="var config = { childList: true, characterData: true };";
    $this->content->text .="observer.observe(target, config);</script>";
    }
    return $this->content;
    }


    function instance_allow_multiple() {
        return true;
    }
}

