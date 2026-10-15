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
 * The main istudymonthlyattend configuration form used for entering attendance credit
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Module instance settings form
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_istudymonthlyattend_enter_monthattendance_form extends \moodleform
{

    public function definition()
	{
        $mform       = $this->_form;
		$courseid    = trim($this->_customdata['course']);
		$monthchoice = trim($this->_customdata['monthchoice']);
		$advisorid = $this->_customdata['myteacher'];
		$enrollment_status = $this->_customdata['enrollment_status'];
		$include_spring_courses = $this->_customdata['include_spring_courses'];
		$course_name = mod_istudymonthlyattend\utils::get_course_name($courseid, $advisorid);
		$mycourse_info = mod_istudymonthlyattend\utils::get_mycourse($course_name);
		$hour_limit = 8;

		// $schoolterm is 1 for fall and 2 for spring - summer school is not used to monthly attendance credit.
		$schoolterm = $this->_customdata['schoolterm'];
		$this_schoolterm[1] = 'fall';
		$this_schoolterm[2] = 'spring';
		$mystudents = mod_istudymonthlyattend\student_info::get_students($courseid, $course_name, $advisorid, $monthchoice, $this_schoolterm[$schoolterm], $enrollment_status, $include_spring_courses);

		$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-enter_month_attendance_form.css">');
		$mform->addElement('html', '<head><title>Monthly Attendance</title></head><body>');
		$mform->addElement('html', '<h5><b>MONTH ' . $monthchoice . ' - ' . $mycourse_info->subject . ' ('. $mycourse_info->section . ')</b></h5>');
		$mform->addElement('html', '<h5>' . mod_istudymonthlyattend\utils::put_comma_in_name($mycourse_info->teacher) . ' - Room ' . $mycourse_info->room . '</h5><br>');
		if ($enrollment_status == 0) {
			$mform->addElement('html', '<h4>Inactive Students</h4><br>');
		}

		// Resources used
		$resourcesused = mod_istudymonthlyattend\utils::get_myresources($courseid, $monthchoice, $advisorid);
		$mform->addElement('html', '<br><h5>RESOURCES</h5>');
		$mform->addElement('textarea', 'resources', null, 'wrap="virtual" rows="1" cols="60"');
		$mform->setDefault('resources', $resourcesused);
		$mform->setType('resources', PARAM_TEXT);
		$mform->addElement('html', '<br><h3>  </h3>');

		// Get istudymonthlyattend config settings for each month and week1assignments.
		if ($monthchoice < 10) {
			$config_monthchoice = '0'.$monthchoice;
		}
		else {
			$config_monthchoice = $monthchoice;
		}
		// Get the start date for each week of the month - text form.
		$week1_date = get_config('mod_istudy','month'.$config_monthchoice.'week01date');
		$week2_date = get_config('mod_istudy','month'.$config_monthchoice.'week02date');
		$week3_date = get_config('mod_istudy','month'.$config_monthchoice.'week03date');
		$week4_date = get_config('mod_istudy','month'.$config_monthchoice.'week04date');
		$week1_points = get_config('mod_istudy','month'.$config_monthchoice.'week01');
		$week2_points = get_config('mod_istudy','month'.$config_monthchoice.'week02');
		$week3_points = get_config('mod_istudy','month'.$config_monthchoice.'week03');
		$week4_points = get_config('mod_istudy','month'.$config_monthchoice.'week04');
		$week1_description = 'WEEK 1 - ' . $week1_date . ' (' . $week1_points . ' days)';
		$week2_description = 'WEEK 2 - ' . $week2_date . ' (' . $week2_points . ' days)';
		$week3_description = 'WEEK 3 - ' . $week3_date . ' (' . $week3_points . ' days)';
		$week4_description = 'WEEK 4 - ' . $week4_date . ' (' . $week4_points . ' days)';

		// Find the actual date for Monday and Friday of each week.
		$four_days = 86400+86400+86400+86400;
		$week1_monday = mod_istudymonthlyattend\utils::set_to_12pm(strtotime($week1_date));
		$week1_friday = $week1_monday + $four_days;
		$week2_monday = mod_istudymonthlyattend\utils::set_to_12pm(strtotime($week2_date));
		$week2_friday = $week2_monday + $four_days;
		$week3_monday = mod_istudymonthlyattend\utils::set_to_12pm(strtotime($week3_date));
		$week3_friday = $week3_monday + $four_days;
		$week4_monday = mod_istudymonthlyattend\utils::set_to_12pm(strtotime($week4_date));
		$week4_friday = $week4_monday + $four_days;

		$mform->addElement('html', '<table style="width:100%">');
		$mform->addElement('html', '<tr><th width="2%">#</th>');
		$mform->addElement('html', '<th width="14%">Student Name</th>');
		$mform->addElement('html', '<th width="21%">' . $week1_description . '</th>');
		$mform->addElement('html', '<th width="21%">' . $week2_description . '</th>');
		$mform->addElement('html', '<th width="21%">' . $week3_description . '</th>');
		$mform->addElement('html', '<th width="21%">' . $week4_description . '</th></tr>');

		// Description of coursework - copy text to each empty cell fo the corresponding week.
		$mform->addElement('html', '<tr class="textcopy"><td></td><td>Coursework entered here will be copied to all empty text areas below</td>');
		// Week 1 description of coursework.
		$mform->addElement('html', '<td>');
		$mform->addElement('textarea', 'copy_to_week1', '', 'wrap="virtual" rows="3" cols="30"');
		$mform->setDefault('copy_to_week1', '');
		$mform->setType('copy_to_week1', PARAM_TEXT);
		$mform->addElement('html', '</td>');

		// Week 2 description of coursework.
		$mform->addElement('html', '<td>');
		$mform->addElement('textarea', 'copy_to_week2', '', 'wrap="virtual" rows="3" cols="30"');
		$mform->setDefault('copy_to_week2', '');
		$mform->setType('copy_to_week2', PARAM_TEXT);
		$mform->addElement('html', '</td>');

		// Week 3 description of coursework.
		$mform->addElement('html', '<td>');
		$mform->addElement('textarea', 'copy_to_week3', '', 'wrap="virtual" rows="3" cols="30"');
		$mform->setDefault('copy_to_week3', '');
		$mform->setType('copy_to_week3', PARAM_TEXT);
		$mform->addElement('html', '</td>');

		// Week 4 description of coursework.
		$mform->addElement('html', '<td>');
		$mform->addElement('textarea', 'copy_to_week4', '', 'wrap="virtual" rows="3" cols="30"');
		$mform->setDefault('copy_to_week4', '');
		$mform->setType('copy_to_week4', PARAM_TEXT);
		$mform->addElement('html', '</td>');

		$mform->addElement('html', '</tr>');

		// List the students.  Enter the points earned and a description of the assignment for each of the 4 weeks.
		$count = 0;
		$attendance_background['red'] = 'h6';
		$attendance_background['green'] = 'h4';
		$attendance_background['yellow'] = 'h3';
		if ($mystudents <> 'No Students') {
			foreach ($mystudents as $mystudent) {
				$count = $count + 1;
				$student_id = $mystudent->id;
				$studentname = '<b>' . $mystudent->lastname.', '.$mystudent->firstname . ' (' . $mystudent->grade  . ')</b> - ID#' . $mystudent->schoolid .
					'<br><i><u>Start Date:</u> ' . mod_istudymonthlyattend\utils::check_date($mystudent->startdate) . '</i>';
				$studentid = 'studentid' . $count;
				$mform->addElement('html', '<tr><td rowspan="2" class="mycell">' . $count . '</td><td rowspan="2" class="mycell">' . $studentname . '</td>');

				// Student ID
				$mform->addElement('hidden', $studentid, $student_id);
				$mform->setType($studentid, PARAM_INT);

				// Week Points
				$week1points = 'week1points'.$count;
				$week2points = 'week2points'.$count;
				$week3points = 'week3points'.$count;
				$week4points = 'week4points'.$count;

				// Week 1 Points Radio Buttons start here.
				$radioarray = array();
				$radioarray[] = $mform->createElement('radio', $week1points, '', 0, 0, ['class' => 'radiobuttons-zero']);
				$radioarray[] = $mform->createElement('radio', $week1points, '', 4, 4, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week1points, '', 8, 8, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week1points, '', 12, 12, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week1points, '', 16, 16, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week1points, '', 20, 20, ['class' => 'radiobuttons-20hours']);
				for ($this_hour = 1; $this_hour <= $hour_limit; $this_hour++) {
					$half_hour = $this_hour - 0.5;
					$radioarray[] = $mform->createElement('radio', $week1points, '', $half_hour, $half_hour, ['class' => 'radiobuttons-lightblue']);
					if (!($this_hour == 4 OR $this_hour == 8 OR $this_hour == 12 OR $this_hour == 16 OR $this_hour == 20)) {
						$radioarray[] = $mform->createElement('radio', $week1points, '', $this_hour, $this_hour, ['class' => 'radiobuttons-blue']);
					}
				}
				// This is the attendance background color (red=absent, yellow=tardy, greeen=attended).
				$this_background = $attendance_background[mod_istudymonthlyattend\student_info::student_attended_class($student_id, $advisorid, $courseid, $week1_monday, $week1_friday)];

				$mform->addElement('html', '<td class="money"><' . $this_background . '>' . $week1_description . '</' . $this_background . '>');
					$mform->addGroup($radioarray, 'radioar', '', array(' '), false);
					$mform->setDefault($week1points, $mystudent->creditsweek1);
					$mform->setType($week2points, PARAM_FLOAT);
				$mform->addElement('html', '</td>');

				// Week 2 Points Radio Buttons start here.
				$radioarray = array();
				$radioarray[] = $mform->createElement('radio', $week2points, '', 0, 0, ['class' => 'radiobuttons-zero']);
				$radioarray[] = $mform->createElement('radio', $week2points, '', 4, 4, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week2points, '', 8, 8, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week2points, '', 12, 12, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week2points, '', 16, 16, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week2points, '', 20, 20, ['class' => 'radiobuttons-20hours']);
				for ($this_hour = 1; $this_hour <= $hour_limit; $this_hour++) {
					$half_hour = $this_hour - 0.5;
					$radioarray[] = $mform->createElement('radio', $week2points, '', $half_hour, $half_hour, ['class' => 'radiobuttons-lightblue']);
					if (!($this_hour == 4 OR $this_hour == 8 OR $this_hour == 12 OR $this_hour == 16 OR $this_hour == 20)) {
						$radioarray[] = $mform->createElement('radio', $week2points, '', $this_hour, $this_hour, ['class' => 'radiobuttons-blue']);
					}
				}
				// This is the attendance background color (red=absent, yellow=tardy, greeen=attended).
				$this_background = $attendance_background[mod_istudymonthlyattend\student_info::student_attended_class($student_id, $advisorid, $courseid, $week2_monday, $week2_friday)];

				$mform->addElement('html', '<td class="money"><' . $this_background . '>' . $week2_description . '</' . $this_background . '>');
					$mform->addGroup($radioarray, 'radioar', '', array(' '), false);
					$mform->setDefault($week2points, $mystudent->creditsweek2);
					$mform->setType($week2points, PARAM_FLOAT);
				$mform->addElement('html', '</td>');

				// Week 3 Points Radio Buttons start here.
				$radioarray = array();
				$radioarray[] = $mform->createElement('radio', $week3points, '', 0, 0, ['class' => 'radiobuttons-zero']);
				$radioarray[] = $mform->createElement('radio', $week3points, '', 4, 4, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week3points, '', 8, 8, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week3points, '', 12, 12, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week3points, '', 16, 16, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week3points, '', 20, 20, ['class' => 'radiobuttons-20hours']);
				for ($this_hour = 1; $this_hour <= $hour_limit; $this_hour++) {
					$half_hour = $this_hour - 0.5;
					$radioarray[] = $mform->createElement('radio', $week3points, '', $half_hour, $half_hour, ['class' => 'radiobuttons-lightblue']);
					if (!($this_hour == 4 OR $this_hour == 8 OR $this_hour == 12 OR $this_hour == 16 OR $this_hour == 20)) {
						$radioarray[] = $mform->createElement('radio', $week3points, '', $this_hour, $this_hour, ['class' => 'radiobuttons-blue']);
					}
				}
				// This is the attendance background color (red=absent, yellow=tardy, greeen=attended).
				$this_background = $attendance_background[mod_istudymonthlyattend\student_info::student_attended_class($student_id, $advisorid, $courseid, $week3_monday, $week3_friday)];

				$mform->addElement('html', '<td class="money"><' . $this_background . '>' . $week3_description . '</' . $this_background . '>');
					$mform->addGroup($radioarray, 'radioar', '', array(' '), false);
					$mform->setDefault($week3points, $mystudent->creditsweek3);
					$mform->setType($week3points, PARAM_FLOAT);
				$mform->addElement('html', '</td>');

				// Week 4 Points Radio Buttons start here.
				$radioarray = array();
				$radioarray[] = $mform->createElement('radio', $week4points, '', 0, 0, ['class' => 'radiobuttons-zero']);
				$radioarray[] = $mform->createElement('radio', $week4points, '', 4, 4, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week4points, '', 8, 8, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week4points, '', 12, 12, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week4points, '', 16, 16, ['class' => 'radiobuttons-fullday']);
				$radioarray[] = $mform->createElement('radio', $week4points, '', 20, 20, ['class' => 'radiobuttons-20hours']);
				for ($this_hour = 1; $this_hour <= $hour_limit; $this_hour++) {
					$half_hour = $this_hour - 0.5;
					$radioarray[] = $mform->createElement('radio', $week4points, '', $half_hour, $half_hour, ['class' => 'radiobuttons-lightblue']);
					if (!($this_hour == 4 OR $this_hour == 8 OR $this_hour == 12 OR $this_hour == 16 OR $this_hour == 20)) {
						$radioarray[] = $mform->createElement('radio', $week4points, '', $this_hour, $this_hour, ['class' => 'radiobuttons-blue']);
					}
				}
				// This is the attendance background color (red=absent, yellow=tardy, greeen=attended).
				$this_background = $attendance_background[mod_istudymonthlyattend\student_info::student_attended_class($student_id, $advisorid, $courseid, $week4_monday, $week4_friday)];

				$mform->addElement('html', '<td class="money"><' . $this_background . '>' . $week4_description . '</' . $this_background . '>');
					$mform->addGroup($radioarray, 'radioar', '', array(' '), false);
					$mform->setDefault($week4points, $mystudent->creditsweek4);
					$mform->setType($week4points, PARAM_FLOAT);
				$mform->addElement('html', '</td>');

				$mform->addElement('html', '</tr>');

				$mform->addElement('html', '<tr class="even">');

				// Week 1 description of coursework.
				$mform->addElement('html', '<td>');
				$week1assignments = 'week1assignments'.$count;
					$mform->addElement('textarea', $week1assignments, '', 'wrap="virtual" rows="3" cols="30"');
					$week1default = $mystudent->assigmentweek1;
					$mform->setDefault($week1assignments, $week1default);
					$mform->setType($week1assignments, PARAM_TEXT);
				$mform->addElement('html', '</td>');

				// Week 2 description of coursework.
				$mform->addElement('html', '<td>');
				$week2assignments = 'week2assignments'.$count;
					$mform->addElement('textarea', $week2assignments, '', 'wrap="virtual" rows="3" cols="30"');
					$week2default = $mystudent->assigmentweek2;
					$mform->setDefault($week2assignments, $week2default);
					$mform->setType($week2assignments, PARAM_TEXT);
				$mform->addElement('html', '</td>');

				// Week 3 description of coursework.
				$mform->addElement('html', '<td>');
				$week3assignments = 'week3assignments'.$count;
					$mform->addElement('textarea', $week3assignments, '', 'wrap="virtual" rows="3" cols="30"');
					$week3default = $mystudent->assigmentweek3;
					$mform->setDefault($week3assignments, $week3default);
					$mform->setType($week3assignments, PARAM_TEXT);
				$mform->addElement('html', '</td>');

				// Week 4 description of coursework.
				$mform->addElement('html', '<td>');
				$week4assignments = 'week4assignments'.$count;
					$mform->addElement('textarea', $week4assignments, '', 'wrap="virtual" rows="3" cols="30"');
					$week4default = $mystudent->assigmentweek4;
					$mform->setDefault($week4assignments, $week4default);
					$mform->setType($week4assignments, PARAM_TEXT);
				$mform->addElement('html', '</td>');

				$mform->addElement('html', '</tr>');
			}
		}
		$mform->addElement('html', '</table></body>');

		if ($count == 0) {
			echo '<h2>There are no students in this course</h2>';
			// Add standard buttons, common to all modules.
			$mform->addElement('html', '<br><h3></h3>');
			$this->add_action_buttons($cancel = true);
		}
		else {
			// Hidden id number for sending the count back.
			$mform->addElement('hidden', 'teacher_id', $advisorid);
			$mform->setType('teacher_id', PARAM_INT);
			$mform->addElement('hidden', 'courseid', $courseid);
			$mform->setType('courseid', PARAM_INT);
			$mform->addElement('hidden', 'count', $count);
			$mform->setType('count', PARAM_INT);
			$mform->addElement('hidden', 'schoolterm', $schoolterm);
			$mform->setType('schoolterm', PARAM_INT);
			$mform->addElement('hidden', 'monthchoice', $monthchoice);
			$mform->setType('monthchoice', PARAM_INT);
			$mform->addElement('hidden', 'enrollment_status', $enrollment_status);
			$mform->setType('enrollment_status', PARAM_INT);

			// Hidden id number for sending the data back.
			$mform->addElement('hidden', 'id');
			$mform->setType('id', PARAM_INT);

			// Add standard buttons, common to all modules.
			$mform->addElement('html', '<br><h3></h3>');
			$this->add_action_buttons($cancel = true, $submitlabel='Save changes');
		}
	}
}