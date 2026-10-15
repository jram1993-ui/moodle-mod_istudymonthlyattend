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
require_once(dirname(__FILE__).'/choose_month_form.php');

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

$advisorid = $USER->advisorid;
$formparams = array('advisorid' => $advisorid);

// Output starts here.
echo $OUTPUT->header();

// Display form.
$mform = new \mod_istudymonthlyattend_choose_month_form(null, $formparams);
$mform->set_data($istudymonthlyattendid);
if ($mform->is_cancelled()) {
	// Cancelled forms redirect to the course main page.
	$url = new \moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url);
} else if ($data = $mform->get_data()) {
	if ($data->mycourse <> 'none') {
		$month_chosen = $data->monthchoice;
		// Choose fall for months 1-5 (fall = 1);
		if ($month_chosen <= 5) {
			$USER->schoolterm = 1;
		}
		// Choose spring for months 6-11 (spring = 2);
		else {
			$USER->schoolterm = 2;
		}
		$USER->monthchoice = $month_chosen;
		$USER->mycourse = $data->mycourse;
		$USER->enrollment_status = $data->enrollment_status;
		$USER->include_spring_courses = $data->include_spring_courses;
		$enter_monthattendanceurl = new \moodle_url('/mod/istudymonthlyattend/enter_monthattendance.php', array('id' => $cm->id));
		redirect($enter_monthattendanceurl);
	}
	else {
		echo $OUTPUT->notification('You must choose a course');
	}

} else {
	// Form has not been submitted or there was an error
	// Just display the form
	$mform->set_data(array('id' => $id));
}

$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$mform->display();
echo $OUTPUT->footer();