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
 * Exports a .csv file of the csvcomponent grades in a rubric-graded assignment.
 *
 * @package    report_csvcomponentgrades
 * @copyright  2021 Dianne Dhanassar <dianne.dhanassar@my.uwi.edu>
 * @copyright  based on work by 2014 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//Declare and initialise required libraries
require('../../config.php');
require_once($CFG->dirroot.'/lib/csvlib.class.php'); 
require_once($CFG->dirroot.'/report/csvcomponentgrades/locallib.php');

$id          = required_param('id', PARAM_INT); //Course ID.
$modid       = required_param('modid', PARAM_INT); //CM ID.

$params['id'] = $id;
$params['modid'] = $id;

//setting the URL of the page in index.php
$PAGE->set_url('/report/csvcomponentgrades/index.php', $params);

//Populate the string 'course' from the database. 
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);

$modinfo = get_fast_modinfo($course->id);
$cm = $modinfo->get_cm($modid);

$modcontext = context_module::instance($cm->id);
require_capability('mod/assign:grade', $modcontext);

//Group mode for the course is not empty and the show groups configuration is checked
$showgroups = !empty($course->groupmode) && get_config('report_csvcomponentgrades', 'showgroups');

// Trigger event for logging.
$event = \report_csvcomponentgrades\event\report_viewed::create(array(
    'context' => $modcontext,
    'other' => array(
        'gradingmethod' => 'rubric'
    )
));
$event->add_record_snapshot('course_modules', $cm); // Add cached data that will be most probably used in event observers.This is used to improve performance, but it is required for data that was just deleted.
$event->trigger(); 

$filename = $course->shortname . ' - ' . $cm->name . '.csv';

//Populate $data with the student and course information from the sql database
$data = $DB->get_records_sql("SELECT    grf.id AS grfid, crs.shortname AS course, asg.name AS assignment, gd.name AS rubric,
                                        grc.description, grl.definition, grl.score, grf.remark, grf.criterionid,
                                        rubm.username AS grader, stu.id AS userid, stu.idnumber AS idnumber, stu.firstname,
                                        stu.lastname, stu.username AS student, gin.timemodified AS modified
                                FROM {course} crs
                                JOIN {course_modules} cm ON crs.id = cm.course
                                JOIN {assign} asg ON asg.id = cm.instance
                                JOIN {context} c ON cm.id = c.instanceid
                                JOIN {grading_areas}  ga ON c.id=ga.contextid
                                JOIN {grading_definitions} gd ON ga.id = gd.areaid
                                JOIN {gradingform_rubric_criteria} grc ON (grc.definitionid = gd.id)
                                JOIN {gradingform_rubric_levels} grl ON (grl.criterionid = grc.id)
                                JOIN {grading_instances} gin ON gin.definitionid = gd.id
                                JOIN {assign_grades} ag ON ag.id = gin.itemid
                                JOIN {user} stu ON stu.id = ag.userid
                                JOIN {user} rubm ON rubm.id = gin.raterid
                                JOIN {gradingform_rubric_fillings} grf ON (grf.instanceid = gin.id)
                                 AND (grf.criterionid = grc.id) AND (grf.levelid = grl.id)
                               WHERE cm.id = ? AND gin.status = 1
                            ORDER BY lastname ASC, firstname ASC, userid ASC, grc.sortorder ASC,
                                grc.description ASC", array($cm->id));

//Call the get_students function from locallib file.
$students = report_csvcomponentgrades_get_students($modcontext, $cm);

//reset rewinds array's internal pointer to the first element and returns the value of the first array element.
$first = reset($data);
if ($first === false) {
    $url = $CFG->wwwroot.'/mod/assign/view.php?id='.$cm->id;
    $message = get_string('nogradesenteredrubric', 'report_csvcomponentgrades');
    redirect($url, $message, 5);
    exit;
}

/**
* @param string $dataname    The name of the module.
* @param string $extenstion  File extension for the file. 
*/ 

//Create a CSV file and set the filename
$csvfile = new csv_export_writer("-");
$csvfile->set_filename($filename, $extension = '.csv');

//If the file is not successfully created, output an error message
if ($csvfile === false) {
    die('Error opening the file ' . $filename);
}

//Call the add_header function from the locallib file to add headers to the CSV file 
$header = report_csvcomponentgrades_add_header($csvfile, $course->fullname, $cm->name, 'rubric', $first->rubric, $showgroups);

//Call the col_header function from the locallib file to add column headers
$colm_header = report_csvcomponentgrades_col_header($csvfile, $data, $first, 'rubric');

//Call the process_data function from the locallib file 
$students = report_csvcomponentgrades_process_data($students, $data);

$groups = array();
if ($showgroups) {
    $groups = report_csvcomponentgrades_get_user_groups($course->id);
}
//call the add_data function from the locallib file to add the grading data to the csv file
$row = report_csvcomponentgrades_add_data($csvfile, $students, 'rubric', $groups);

//Close the file 
$csvfile->download_file();
exit;
