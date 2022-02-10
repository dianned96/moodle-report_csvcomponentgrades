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

/**
 * Spreadsheet export report for assignments marked with advanced grading methods
 *
 * @package    report_csvcomponentgrades
 * @copyright  2021 Dianne Dhanassar <dianne.dhanassar@my.uwi.edu>
 * @copyright  based on work by 2014 Paul Nicholls and 2018 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
define("TITLESROW", 4);
define("HEADINGSROW", 5);

require_once($CFG->dirroot.'/lib/csvlib.class.php'); 
require_once($CFG->dirroot.'/report/csvcomponentgrades/locallib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');


/**
 * Get all students given the course/module details
 * @param \context_module $modcontext
 * @param \stdClass $cm
 * @return array
 */
function report_csvcomponentgrades_get_students($modcontext, $cm) :array {
    global $DB;
    $assign = new assign($modcontext, $cm, $cm->course);
    $result = $DB->get_records_sql('SELECT stu.id AS userid, stu.idnumber AS idnumber,
        stu.firstname, stu.lastname, stu.username AS student
        FROM {user} stu
        JOIN {user_enrolments} ue ON ue.userid = stu.id
        JOIN {enrol} enr ON ue.enrolid = enr.id
       WHERE enr.courseid = ?
    ORDER BY lastname ASC, firstname ASC, userid ASC', [$cm->course]);
    if ($assign->is_blind_marking()) {
        foreach ($result as &$r) {
            $r->firstname = '';
            $r->lastname = '';
            $r->student = get_string('participant', 'assign') .
             ' ' . \assign::get_uniqueid_for_user_static($cm->instance, $r->userid);
        }
    }
    return $result;
}
/**
 * Add header text to report, name of course etc
 * TODO: Why is there method and methodname?
 *
 * @param csv_export_writer $csvfile
 * @param string $coursename
 * @param string $modname
 * @param string $method
 * @param string $methodname
 * @param boolean $showgroups - groups being shown.
 */

function report_csvcomponentgrades_add_header(csv_export_writer $csvfile, 
    $coursename, $modname, $method, $methodname, $showgroups = false) {
    //Declare a header and input the coursename and the module name
    $header = array(
        $coursename,
        $modname
    );
    //Switch case scenario for the methodname
    switch($method) {
        case 'rubric':
              $methodname = get_string('rubric', 'gradingform_rubric').' '.$methodname;
              break;
        case 'markingguide':
              $methodname = get_string('guide', 'gradingform_guide').' '.$methodname;
              break; 
    }
    //Add methodname to the existing array $header 
    $header[] = $methodname;
    //Add header to the csv file
    $csvfile->add_data($header);
}

/**
 * Add header text to the columns - criterion.
 * Rubrics - Score, Definition, Feedback.
 * Marking Guide - Score, Feedback.
 * 
 * @param csv_export_writer $csvfile
 * @param array $data       All the data from the sql database in an array
 * @param string $method    To add the headers respective to the method type
 * @param string $first     Returns the value of the first array element.
 * @return void
 */

function report_csvcomponentgrades_col_header(csv_export_writer $csvfile, $data, $first, $method){
    //Declare an array $colm_header and input headers:- First Name, Last Name, Username and Student ID
    $colm_header = array(
        'First Name',
        'Last name',
        'Username',
        'Student ID'
    );
    
    //Passing the array $data through the variable $line
    //You access an array like this, instead of accessing the 
    //elemnents directly from the array 
    foreach($data as $line){
       if ($line->userid !== $first->userid) {
        break;
        }
        //Output Score/Add score to the array $colm_header
        array_push($colm_header, "Score");
        if ($method == 'rubric') { //Only rubrics have a "definition".
        //Output definition/ Add defn to the array $colm_header
            array_push($colm_header, "Definition");
        }
        //Output feedback/ Add feedaback to the array $colm_header
        array_push($colm_header, "Feedback");
    }
    $csvfile->add_data($colm_header);
}


/**
 * Get data for each student
 *
 * @param array $students   All the students data
 * @param array $data       array of objects
 * @return array
 */
function report_csvcomponentgrades_process_data(array $students, array $data) {
    foreach ($students as $student) {
        $student->data = array();
        foreach ($data as $key => $line) {
            if ($line->userid == $student->userid) {
                $student->data[$key] = $line;
                unset($data[$key]);
            }
        }
    }
    return $students;
}

/**
 * Get the student's information and student's grading data
 *
 * @param csv_export_writer $csvfile
 * @param array $students
 * @param string $method
 * @param array $groups - user group information (optional).
 * @return void
 */
function report_csvcomponentgrades_add_data(csv_export_writer $csvfile, array $students, $method, $groups = null) {
    foreach ($students as $student) {
        //create array named row - add all datafields to row, one at a time. 
        $row = array(
            $student->firstname, 
            $student->lastname, 
            $student->student
        );
        if (get_config('report_csvcomponentgrades', 'showstudentid')){
            $row[] = $student->idnumber;
        }

        foreach ($student->data as $line) {
            if (is_numeric($line->score)) {
                $row[] = $line->score;
            } else {
                /* if BTEC 0=N and 1=Y */
                $row[] = $line->score;
            }
            if ($method == 'rubric') {
                // Only rubrics have a "definition".
                $row[] = $line->definition; 
            }
            $row[] = $line->remark;
        }
        $csvfile->add_data($row); //Add the row to the file within the loop.
    }
}

/**
 * Get object with list of groups each user is in.
 *
 * @param int $courseid
 */
function report_csvcomponentgrades_get_user_groups($courseid) {
    global $DB;

    $sql = "SELECT g.id, g.name, gm.userid
              FROM {groups} g
              JOIN {groups_members} gm ON gm.groupid = g.id
             WHERE g.courseid = ?
          ORDER BY gm.userid";

    $rs = $DB->get_recordset_sql($sql, [$courseid]);
    foreach ($rs as $row) {
        if (!isset($groupsbyuser[$row->userid])) {
            $groupsbyuser[$row->userid] = [];
        }
        $groupsbyuser[$row->userid][] = $row->name;
    }
    $rs->close();
    return $groupsbyuser;

}
