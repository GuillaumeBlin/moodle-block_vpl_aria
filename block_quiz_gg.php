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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/blocks/quiz_gg/statisticslib.php');
global $CFG, $DB;

/**
 * Block quiz_gg class definition.
 *
 * This block can be added to a quiz page to force group grading.
 * Quiz content handling is inspired from the excellent quiz_simulate module from 
 * The Open University.
 *
 * @package    block_quiz_gg
 * @copyright  2016 Guillaume Blin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_quiz_gg extends block_base {

  function init() {
    $this->title = get_string('pluginname', 'block_quiz_gg');
  }

  function applicable_formats() {
    return array('all' => false,'mod-quiz' => true);
  }

  function instance_config_save($data, $nolongerused = false) {
    parent::instance_config_save($data);
  }

  /** @var object quiz record for this quiz. */
  protected $quiz;

  /** @var quiz instance of quiz for this quiz. */
  protected $quizobj;


  /**
  * @var string[]
  */
  protected $subqs = null;

  /**
  * Index is slot number. Value is full question object.
  * @var object[]
  */
  protected $questions = null;


  function get_content() {
    global $CFG, $OUTPUT, $DB, $USER;
    $this->content = new stdClass();
    $this->content->items = array();
    $this->content->icons = array();
    $this->content->footer = '';
    $id=$this->page->course->id;
    $cm = get_coursemodule_from_id('quiz', $id, 0, false, MUST_EXIST); 
    $gps=groups_get_user_groups($id);
    if(count($gps[0])!=1){
		  $this->content->text ="You are not part of a unique group. Quiz grading will not be considered for this quiz.";
		  return $this->content;
    }
    $currentgroupname = groups_get_group_name($gps[0][0]);
    $this->content->text = "<strong> <i class='fa fa-exclamation-triangle' aria-hidden='true'></i> This test will be done for group ".$currentgroupname;
	  $nbFriends=count(groups_get_members($gps[0][0]));
	  if($nbFriends>1){
		  $friends=array();
		  $this->content->text .= " (i.e. with ";
		  for($i=0;$i<$nbFriends;$i++){
			  if(array_values(groups_get_members($gps[0][0]))[$i]->id!=$USER->id){
				  $this->content->text .= ucfirst(array_values(groups_get_members($gps[0][0]))[$i]->firstname)." ";
				  $this->content->text .= ucfirst(array_values(groups_get_members($gps[0][0]))[$i]->lastname).", ";	
				  array_push($friends,array_values(groups_get_members($gps[0][0]))[$i]->id);
		    }
		  }
		$this->content->text = substr($this->content->text,0,-2).")";
	}
	$this->content->text .="</strong>";
	$pos=strpos($this->page->url,"mod/quiz/review.php");
	if($pos !== false){
		$attemptid = required_param('attempt', PARAM_INT);
		$originalid = $attemptid;
		$alreadydone = $DB->get_records('block_quiz_gg', array('blockid' => $this->instance->id, 'attemptid' => $attemptid));
		if (count($alreadydone)==0){
			$attemptobj = quiz_attempt::create($attemptid);
			$qubaids = quiz_statistics_qubaids_condition($attemptobj->get_quizid(), array(),"QUIZ_ATTEMPTLAST");
			$quizid=$attemptobj->get_quizid();
			$dm = new question_engine_data_mapper();
        		$quba = question_engine::load_questions_usage_by_activity($attemptobj->get_attempt()->uniqueid);
			$quizattemptobj = quiz_attempt::create_from_usage_id($quba->get_id());
			$slots = $quba->get_slots();
			$fieldnamesforslots = array();
        		$attemptdata = array();
        		$questionnames = array();
        		$variants = array();
        		$userids = array();
            		foreach ($slots as $slot) {
                		if (!isset($questionnames[$slot])) {
                    			$questionnames[$slot] = array();
                		}
                		if (!isset($variants[$slot])) {
                    			$variants[$slot] = array();
                		}
                		if (!isset($fieldnamesforslots[$slot])) {
                    			$fieldnamesforslots[$slot] = array();
                		}
                		$question = $quba->get_question($slot);
                		$questionnames[$slot] = $question->name;
                		$variants[$slot] = $quba->get_variant($slot);
                		$steps = $quba->get_question_attempt($slot)->get_full_step_iterator();
                		foreach ($steps as $stepno => $step) {
                    			$dataforthisslotandstep = $this->get_csv_step_data($question, $step);
                    			if (!count($dataforthisslotandstep)) {
                        			continue;
                    			}
                    			if (!isset($attemptdata[$stepno])) {
                        			$attemptdata[$stepno] = array();
                    			}
                    			$attemptdata[$stepno][$slot] = $dataforthisslotandstep;
                    			$thisstepslotfieldnames = array_keys($dataforthisslotandstep);
                    			$fieldnamesforslots[$slot] = array_unique(array_merge($fieldnamesforslots[$slot], $thisstepslotfieldnames));
                		}
            		}
            		$firstslot = reset($slots);
            		// Use last step of first slot to see if this attempt was finished.
            		$finish = $quba->get_question_attempt($firstslot)->get_last_step()->get_state()->is_finished();
            		$starts = $attemptobj->get_attempt()->timestart;
            		$ends = $attemptobj->get_attempt()->timefinish;
			list($headers, $row) = $this->get_csv_file_data($fieldnamesforslots,
                                      					$USER->id,
							 		$attemptid,
                                                         		$attemptdata,
                                                         		$questionnames,
                                                         		$variants,
                                                         		$finish);
			if(count($headers)==count($row)){
                		$stepdata = array_combine($headers, $row);
                		$stepdata = $this->explode_dot_separated_keys_to_make_subindexs($stepdata);
               		}else{
				$stepdata['uid']=$USER->id;
				$stepdata['quizattempt']=$attemptid;
				$stepdata['responses']=Array();
			}
			$uid=$stepdata['uid'];
                	if(count($gps[0])==1){
                       		$currentgroupname = groups_get_group_name($gps[0][0]);
                       		$nbFriends=count(groups_get_members($gps[0][0]));
                       		if($nbFriends>1){
                               		for($i=0;$i<$nbFriends;$i++){
                                       		$stepdata['uid']=array_values(groups_get_members($gps[0][0]))[$i]->id;
                                       		if($uid!=$stepdata['uid']){
                                               		$userid = $stepdata['uid'];
                                              		$attemptid = $this->start_attempt($stepdata, $userid, $starts,$quizid);
                                              		$this->attempt_step($stepdata, $attemptid,$ends);
                                       		}
                               		}
                       		}
               		}
			$DB->insert_record('block_quiz_gg', array('blockid' => $this->instance->id, 'attemptid' => $attemptid));
			$alreadydone = $DB->get_records('block_quiz_gg', array('blockid' => $this->instance->id, 'attemptid' => $originalid));
                	if (count($alreadydone)==0){
				$DB->insert_record('block_quiz_gg', array('blockid' => $this->instance->id, 'attemptid' => $originalid));
			}
		}
	}
	return $this->content;    
    }

    /**
     * Gets the data for one step for one question.
     *
     * @param question_definition $question The question.
     * @param question_attempt_step $step   The step to get the data from.
     * @return string[] the csv data for this step.
     */
    protected function get_csv_step_data($question, $step) {
        $csvdata = array();
        $allqtdata = $question->get_student_response_values_for_simulation($step->get_qt_data());
        foreach ($allqtdata as $qtname => $qtvalue) {
            if ($qtname[0] != '_') {
                $csvdata[$qtname] = $qtvalue;
            }
        }
        $behaviourdata = $step->get_behaviour_data();
        foreach ($behaviourdata as $behaviourvarname => $behaviourvarvalue) {
            if ($behaviourvarname[0] != '_' && $behaviourvarname != 'finish') {
                $csvdata['-'.$behaviourvarname] = $behaviourvarvalue;
            }
        }
        return $csvdata;
    }

     /**
     * @param $step array of data from csv file keyed with column names.
     * @param $attemptid integer attempt id if this is not a new attempt or 0.
     * @throws moodle_exception
     */
    protected function attempt_step($step, $attemptid, $actudate=-1) {
      if($actudate==-1){$actudate=time();}
      // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attemptid);
	      if(!isset($step['responses'])){
		      $attemptobj->process_submitted_actions($actudate, false, null);
	      }else{
        	$attemptobj->process_submitted_actions($actudate, false, $step['responses']);
	      }
        // If there is no column in the csv file 'finish', then default to finish each attempt.
        // Or else only finish when the finish column is not empty.
        if (!isset($step['finished']) || !empty($step['finished'])) {
            // Finish the attempt.
            $attemptobj = quiz_attempt::create($attemptid);
            $attemptobj->process_finish($actudate, false);
        }
    }


    /**
     * Break down row of csv data into sub arrays, according to column names.
     *
     * @param array $row from csv file with field names with parts separate by '.'.
     * @return array the row with each part of the field name following a '.' being a separate sub array's index.
     */
    protected function explode_dot_separated_keys_to_make_subindexs(array $row) {
        $parts = array();
        foreach ($row as $columnkey => $value) {
            $newkeys = explode('.', trim($columnkey));
            $placetoputvalue =& $parts;
            foreach ($newkeys as $newkeydepth => $newkey) {
                if ($newkeydepth + 1 === count($newkeys)) {
                    $placetoputvalue[$newkey] = $value;
                } else {
                    // Going deeper down.
                    if (!isset($placetoputvalue[$newkey])) {
                        $placetoputvalue[$newkey] = array();
                    }
                    $placetoputvalue =& $placetoputvalue[$newkey];
                }
            }
        }
        return $parts;
    }
    
    /**
     * @param $step array of data from csv file keyed with column names.
     * @param $userid integer id of the user doing the attempt.
     * @return integer id of the attempt created.
     * @throws moodle_exception
     */
    protected function start_attempt($step, $userid, $startdate=-1,$quizid=-1) {
        if($startdate==-1){$startdate=time();}
        // Start the attempt.
        $this->quizobj = quiz::create($quizid, $userid);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $this->quizobj->get_context());
	      $quba->set_preferred_behaviour($this->quizobj->get_quiz()->preferredbehaviour);
        $prevattempts = quiz_get_user_attempts($quizid, $userid, 'all', true);
        $attemptnumber = count($prevattempts) + 1;
        $attempt = quiz_create_attempt($this->quizobj, $attemptnumber, false, $startdate, false, $userid);
        // Select variant and / or random sub question.
        if (!isset($step['variants'])) {
            $step['variants'] = array();
        }

        // Pre-load the questions so that we can find the ids of random questions.
        $this->quizobj->preload_questions();
        $this->quizobj->load_questions();

        $randqids = $this->find_randq_ids_from_step_data($step);

        quiz_start_new_attempt($this->quizobj, $quba, $attempt, $attemptnumber, $startdate, $randqids, $step['variants']);
        quiz_attempt_save_started($this->quizobj, $quba, $attempt);
        return $attempt->id;
    }

    protected function find_randq_ids_from_step_data($step) {
        if (isset($step['randqs'])) {
            $randqids = array();
            $this->get_subq_names();
            foreach ($step['randqs'] as $slotno => $randqname) {
                $subqnames = $this->get_subq_names_for_slot($slotno);
                if (!$randqid = array_search($randqname, $subqnames)) {
                    $a = new stdClass();
                    $a->name = $randqname;
                    throw new moodle_exception('noquestionwasfoundwithname', 'quiz_simulate', '', $a);
                }
                $randqids[$slotno] = $randqid;
            }
            return $randqids;

        } else {
            return array();
        }
    }

    protected function get_subq_names() {
        if ($this->subqs !== null) {
            return;
        }
        $this->subqs = array();
        $questions = $this->quizobj->get_questions();

        foreach ($questions as $q) {
            $this->questions[$q->slot] = $q;
        }
        foreach ($this->questions as $slot => $q) {
            if ($q->qtype === 'random') {
                $randqtypeobj = question_bank::get_qtype('random');
                $subqids = $randqtypeobj->get_available_questions_from_category($q->category, !empty($q->questiontext));
                $this->subqs[$slot] = array();
                foreach ($subqids as $subqid) {
                    $subq = question_finder::get_instance()->load_question_data($subqid);
                    $this->subqs[$slot][$subq->id] = $subq->name;
                }
            }
        }
    }

    /**
     * @param int $slot the slot no
     * @throws moodle_exception
     * @return string[] the rand question names indexed by id.
     */
    protected function get_subq_names_for_slot($slot) {
        $this->get_subq_names();
        if (!isset($this->subqs[$slot])) {
            $a = new stdClass();
            $a->slotno = $slot;
            throw new moodle_exception('thisisnotarandomquestion', 'quiz_simulate', '', $a);
        }
        return $this->subqs[$slot];
    }

    /**
     * @param $slot int
     * @param $qid int
     * @return null|string null if not a random question.
     */
    protected function get_subqname_for_slot_from_id($slot, $qid) {
        $this->get_subq_names();
        if ($this->questions[$slot]->qtype !== 'random') {
            return null;
        } else {
            $subqnames = $this->get_subq_names_for_slot($slot);
            return $subqnames[$qid];
        }
    }


 /**
     * @param array[] $fieldnamesforslots The field names per slot of data to download.
     * @param int   $userids            The user id for the user for each quiz attempt.
     * @param array[] $attemptdata        with step data first index is quiz attempt no and second is step no third index is
     *                                    question type data or behaviour var name.
     * @param array[] $questionnames      The question name for question indexed by slot no and then quiz attempt.
     * @param array[] $variants           The variant no for question indexed by slot no and then quiz attempt.
     * @param bool[]  $finish             Is question attempt finished - indexed by quiz attempt no.
     * @return array[] with two values array $headers for file and array $rows for file
     */
    protected function get_csv_file_data($fieldnamesforslots, $userids, $quizattempt, $stepsslotscsvdata,  $questionnames, $variants, $finish) {
        global $DB;
        $headers = array('quizattempt', 'uid');
        $rows = array();
        $subqcolumns = array();
        $variantnocolumns = array();
        foreach (array_keys($fieldnamesforslots) as $slot) {
            sort($fieldnamesforslots[$slot]);
            $subqcolumns[$slot] = false;
            $variantnocolumns[$slot] = false;
            foreach ($fieldnamesforslots[$slot] as $fieldname) {
                $headers[] = 'responses.'.$slot.'.'.$fieldname;
            }
        }
        // Any zero elements in finish array?
        $finishcolumn = false;
	
        if (count($stepsslotscsvdata) > 1) {
                // More than one step in this quiz attempt.
                $finishcolumn = true;
        }
	if ($finishcolumn) {
            $headers[] = 'finished';
        }
            $firstrow = true;
            $stepnos = array_keys($stepsslotscsvdata);
            $laststepno = array_pop($stepnos);
            foreach ($stepsslotscsvdata as $stepno => $slotscsvdata) {
                $row = array();
                $row[] = $quizattempt;
                if ($firstrow) {
                    $row[] = $userids;
                } else {
                    $row[] = '';
                }
                foreach ($fieldnamesforslots as $slot => $fieldnames) {
                    if ($subqcolumns[$slot]) {
                        if ($firstrow) {
                            $row[] = $questionnames[$slot];
                        } else {
                            $row[] = '';
                        }
                    }
                    if ($variantnocolumns[$slot]) {
                        if ($firstrow) {
                            $row[] = $variants[$slot];
                        } else {
                            $row[] = '';
                        }
                    }
                    foreach ($fieldnames as $fieldname) {
                        $value = '';
                        $stepnocountback = $stepno;
                        if ($fieldname{0} == '-') {
                            // Behaviour data.
                            if (isset($slotscsvdata[$slot][$fieldname])) {
                                $value = $slotscsvdata[$slot][$fieldname];
                            } else {
                                $value = '';
                            }
                        } else {
                            // Question type data, repeat last value if there is no value in this step.
                            while ($stepnocountback > 0) {
                                if (isset($stepsslotscsvdata[$stepnocountback][$slot][$fieldname]) &&
                        $stepsslotscsvdata[$stepnocountback][$slot][$fieldname] !== '') {
                                    $value = $stepsslotscsvdata[$stepnocountback][$slot][$fieldname];
                                    break;
                                }
                                $stepnocountback--;
                            }
                        }
                        $row[] = $value;
                    }
                }
                if ($finishcolumn) {
                    if ($stepno == $laststepno) {
                        $row[] = $finish ? '1' : '0';
                    } else {
                        $row[] = '0';
                    }
                }
                $rows = $row;
                $firstrow = false;
            }
        return array($headers, $rows);
    }

    function instance_allow_multiple() {
        return true;
    }
}

