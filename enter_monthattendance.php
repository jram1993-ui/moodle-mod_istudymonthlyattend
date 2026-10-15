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
 * Prints a particular instance of istudymonthlyattend
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require(__DIR__.'/../../config.php');
 require_once(__DIR__.'/lib.php');
 require_once(__DIR__.'/enter_monthattendance_form.php');

global $DB, $USER;

/**
 * Course module id.
 */
$id = optional_param('id', 0, PARAM_INT);

/**
 * Activity instance id.
 */
$i = optional_param('i', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('istudymonthlyattend', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('istudymonthlyattend', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('istudymonthlyattend', array('id' => $i), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('istudymonthlyattend', $moduleinstance->id, $course->id, false, MUST_EXIST);
}
$istudymonthlyattendid = $cm->instance;

require_login($course, true, $cm);

$modulecontext = \context_module::instance($cm->id);

$PAGE->set_url('/mod/istudymonthlyattend/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Set up the global variables for the form.
$monthchoice = $USER->monthchoice;
$mycourseid = $USER->mycourse;
$schoolterm = $USER->schoolterm;
$myteacher = $USER->advisorid;
$enrollment_status = $USER->enrollment_status;
$include_spring_courses = $USER->include_spring_courses;

// Output starts here.
echo $OUTPUT->header();

// Display form.
$formparams = array('course' => $mycourseid, 'monthchoice' => $monthchoice, 'schoolterm' => $schoolterm, 'myteacher' => $myteacher,
	'enrollment_status' => $enrollment_status, 'include_spring_courses' => $include_spring_courses);
$mform = new mod_istudymonthlyattend_enter_monthattendance_form(null, $formparams);
$mform->set_data($istudymonthlyattendid);
if ($mform->is_cancelled()) {
	// Cancelled forms redirect to the course main page.
	$url = new moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url);
} else if ($data = $mform->get_data()) {
	// Get the form data
	$data = $mform->get_data();

	// Get the course data.
	$submission = new stdClass();
	$submission->course = $data->courseid;
	$submission->timemodified = time();
	$submission->teacher_id = $data->teacher_id;
	$submission->month = $data->monthchoice;
	$submission->resources =mod_istudymonthlyattend\utils::remove_extra_symbols($data->resources);
	$student_count = $data->count;
	$copy_to_week1 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->copy_to_week1);
	$copy_to_week2 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->copy_to_week2);
	$copy_to_week3 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->copy_to_week3);
	$copy_to_week4 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->copy_to_week4);

	$this_term = $data->schoolterm;
	$this_month = $data->monthchoice;
	$this_enrollment = $data->enrollment_status;

	if ($student_count <> 0) {
		// Special scenerio - month 5 carries over into the spring semester - only save entries with text.
		// If $this_enrollment = 0, the students are currently inactive - only save entries with text.
		if ((($this_month == 5) AND ($this_term == 2)) OR ($this_enrollment == 0)) {
			for ($count = 1; $count <= $student_count; $count++) {
				// Get the attendance data.
				$submission->student_id = $data->{'studentid'.$count};
				$submission->timecreated = time();
				$submission->creditsweek1 = $data->{'week1points'.$count};
				$submission->creditsweek2 = $data->{'week2points'.$count};
				$submission->creditsweek3 = $data->{'week3points'.$count};
				$submission->creditsweek4 = $data->{'week4points'.$count};
				$submission->assignmentweek1 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week1assignments'.$count});
				$submission->assignmentweek2 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week2assignments'.$count});
				$submission->assignmentweek3 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week3assignments'.$count});
				$submission->assignmentweek4 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week4assignments'.$count});

				//Copy default text to each assignment that has no text.
				if ($copy_to_week1 <> '' AND $submission->assignmentweek1 == '') {
					$submission->assignmentweek1 = $copy_to_week1;
				}
				if ($copy_to_week2 <> '' AND $submission->assignmentweek2 == '') {
					$submission->assignmentweek2 = $copy_to_week2;
				}
				if ($copy_to_week3 <> '' AND $submission->assignmentweek3 == '') {
					$submission->assignmentweek3 = $copy_to_week3;
				}
				if ($copy_to_week4 <> '' AND $submission->assignmentweek4 == '') {
					$submission->assignmentweek4 = $copy_to_week4;
				}

				// Save attendance info to database.
				if (($submission->assignmentweek1<>"") OR ($submission->assignmentweek2<>"") OR ($submission->assignmentweek3<>"")
					OR ($submission->assignmentweek4<>"")) {
						try {
							$transaction = $DB->start_delegated_transaction();
							// Get the students that already have attendance recorded.
							$table = 'istudy_monthatten';
							$select = "course = :course AND teacher_id = :teacher_id AND student_id = :student_id AND month = :month";
							$params =['course'=> $submission->course, 'teacher_id'=>$submission->teacher_id, 'student_id'=>$submission->student_id, 'month'=>$submission->month];
							if ($studentattendanceid = $DB->get_record_select($table, $select, $params)) {
								$submission->id = $studentattendanceid->id;
								$submission->timemodified = $studentattendanceid->timecreated;
								$DB->update_record('istudy_monthatten', $submission, $bulk=false);
							}
							else {
									$DB->insert_record('istudy_monthatten', $submission, $returnid=true, $bulk=false);
							}
							// Assuming that one of the inserts worked, we get to the following line.
							$transaction->allow_commit();
						} catch(Exception $e) {
							$transaction->rollback($e);
							$this_student = mod_istudymonthlyattend\student_info::get_single_student($submission->student_id);
							$student_with_error = 'The entry for ' . $this_student->firstname . ' ' . $this_student->lastname . ' was not saved. An error was found in the assignment details.';
							echo $OUTPUT->notification($student_with_error);
						}
					}
			}
			echo $OUTPUT->notification('Changes saved', 'notifysuccess');
		}
		// For fall only or spring only student - save all data - include entries with no text.
		else {
			for ($count = 1; $count <= $student_count; $count++) {
				// Get the attendance data.
				$submission->student_id = $data->{'studentid'.$count};
				$submission->timecreated = time();
				$submission->creditsweek1 = $data->{'week1points'.$count};
				$submission->creditsweek2 = $data->{'week2points'.$count};
				$submission->creditsweek3 = $data->{'week3points'.$count};
				$submission->creditsweek4 = $data->{'week4points'.$count};
				$submission->assignmentweek1 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week1assignments'.$count});
				$submission->assignmentweek2 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week2assignments'.$count});
				$submission->assignmentweek3 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week3assignments'.$count});
				$submission->assignmentweek4 = mod_istudymonthlyattend\utils::remove_extra_symbols($data->{'week4assignments'.$count});

				//Copy default text to each assignment that has no text.
				if ($copy_to_week1 <> '' AND $submission->assignmentweek1 == '') {
					$submission->assignmentweek1 = $copy_to_week1;
				}
				if ($copy_to_week2 <> '' AND $submission->assignmentweek2 == '') {
					$submission->assignmentweek2 = $copy_to_week2;
				}
				if ($copy_to_week3 <> '' AND $submission->assignmentweek3 == '') {
					$submission->assignmentweek3 = $copy_to_week3;
				}
				if ($copy_to_week4 <> '' AND $submission->assignmentweek4 == '') {
					$submission->assignmentweek4 = $copy_to_week4;
				}

				try {
					$transaction = $DB->start_delegated_transaction();
					// Get the students that already have attendance recorded.
					$table = 'istudy_monthatten';
					$select = "course = :course AND teacher_id = :teacher_id AND student_id = :student_id AND month = :month";
					$params =['course'=> $submission->course, 'teacher_id'=>$submission->teacher_id, 'student_id'=>$submission->student_id, 'month'=>$submission->month];
					if ($studentattendanceid = $DB->get_record_select($table, $select, $params)) {
						$submission->id = $studentattendanceid->id;
						$submission->timemodified = $studentattendanceid->timecreated;
						$DB->update_record('istudy_monthatten', $submission, $bulk=false);
					}
					else {
							$DB->insert_record('istudy_monthatten', $submission, $returnid=true, $bulk=false);
					}
					// Assuming that one of the inserts worked, we get to the following line.
					$transaction->allow_commit();
				} catch(Exception $e) {
					$transaction->rollback($e);
					$this_student = mod_istudymonthlyattend\student_info::get_single_student($submission->student_id);
					$student_with_error = 'The entry for ' . $this_student->firstname . ' ' . $this_student->lastname . ' was not saved. An error was found in the assignment details.';
					echo $OUTPUT->notification($student_with_error);
				}
			}
			// Changes have been saved - reload the page and output the message 'Changes Saved'.
			$USER->just_saved = 'Yes';
			$enter_monthattendanceurl = new moodle_url('/mod/istudymonthlyattend/enter_monthattendance.php', array('id' => $cm->id));
			redirect($enter_monthattendanceurl, 'MONTHLY ATTENDANCE IS BEING SAVED', 0);
		}
	}
} else {
	// Form has not been submitted or there was an error.
	// Just display the form.
	$mform->set_data(array('id' => $id));
}

// Finish the page.
if ($USER->just_saved == 'Yes') {
	echo $OUTPUT->notification('Changes saved', 'notifysuccess');
	$USER->just_saved == 'No';
}
$mform->display();
echo $OUTPUT->footer();