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
require_once(__DIR__.'/choose_advisor_form.php');

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

// If the user is a teacher (advisor) and not an administrator, jump to choose month page.
$advisorid = $USER->id;
$advisor_user_status = mod_istudymonthlyattend\teacher_info::get_whichadvisor($advisorid);

if (mod_istudymonthlyattend\teacher_info::not_a_teacher_or_admin()) {
	echo '<h1>You are denied access to this page...</h1>';
	$url = new \moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url, 'Returning to Course Page', 5);
}
else if ($advisor_user_status <> 'Administrator') {
	$USER->advisorid = $USER->id;
	// The monthly attendance has not been saved yet. Set to Yes after the first save.
	$USER->just_saved = 'No';
	$choose_monthurl = new \moodle_url('/mod/istudymonthlyattend/choose_month.php', array('id' => $cm->id));
	redirect($choose_monthurl);
}

$PAGE->set_url('/mod/istudymonthlyattend/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Output starts here.
echo $OUTPUT->header();

// Display form.
$mform = new mod_istudymonthlyattend_choose_advisor_form();
$mform->set_data($istudymonthlyattendid);
if ($mform->is_cancelled()) {
	// Cancelled forms redirect to the course main page.
	$url = new \moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url, 'Returning to Course Page', 1);
} else if ($data = $mform->get_data()) {
	if ($data->advisorid == 0) {
		echo $OUTPUT->notification('You must choose an advisor');
	}
	else {
		// Get the advisor id from the advisor name chosen.
		$USER->advisorid = $data->advisorid;
		// The monthly attendance has not been saved yet. Set to Yes after the first save.
		$USER->just_saved = 'No';
		$choose_monthurl = new \moodle_url('/mod/istudymonthlyattend/choose_month.php', array('id' => $cm->id));
		redirect($choose_monthurl);
	}
} else {
	// Form has not been submitted or there was an error.
	// Just display the form.
	$mform->set_data(array('id' => $id));
}
$mform->display();
echo $OUTPUT->footer();