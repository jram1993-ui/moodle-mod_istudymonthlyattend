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
 * The main istudymonthlyattend configuration form
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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_istudymonthlyattend_choose_month_form extends \moodleform
{
	/**
	 * Add elements to form.
	 */

    public function definition()
	{
		$advisorid    	= $this->_customdata['advisorid'];
		$teacher_full_name = mod_istudymonthlyattend\utils::put_comma_in_name(mod_istudymonthlyattend\teacher_info::get_whichadvisor($advisorid));
		$mform = $this->_form;
		$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-choose_month_form.css">');
		$mform->addElement('html', '<head><title>Choose Month</title></head><body>');
		$mform->addElement('html', '<h3>Teacher: ' . $teacher_full_name . '</h3>');
		$mform->addElement('html', '<table class="searchform"><tr><th colspan="2"><h3>Choose the Term, Month & Course for monthly attendance</h3>');
		$mform->addElement('html', '<h1>Refine Your Search</h1></th></tr>');

		// Choose the term for the attendance.
		$defaultterm_set = get_config('mod_istudy','schoolterm');
		if ($defaultterm_set == 'Fall') { $defaultterm = 1; }
		else  { $defaultterm = 2; }
		// Choose the month (#1-11).
		for ($i = 1; $i <= 11; $i++) {
			if ($i <= 9) {
				$month_choice[$i] = get_string('month0' . $i, 'istudy') . ' - ' . get_config('mod_istudy', 'month0' . $i . 'week01date');
			}
			else {
				$month_choice[$i] = get_string('month' . $i, 'istudy') . ' - ' . get_config('mod_istudy', 'month' . $i . 'week01date');
			}
		}
		$mform->addElement('html', '<tr><td class="mycell2">Choose Month</td><td class="mycell2">');
		$select = $mform->addElement('select', 'monthchoice', '', $month_choice);

		// Set default month.
		$defaultmonth = mod_istudymonthlyattend\utils::get_defaultmonth();
		$select->setSelected($defaultmonth);
		$mform->addElement('html', '</td></tr>');

		// Choose active students (currently enrolled) or inactive students (dropped from the school).
		$enrollment_status = array(
			 1 => 'Active',
			 0 => 'Inactive',
		);
		$mform->addElement('html', '<tr><td class="mycell2">Enrollment Status</td><td class="mycell2">');
		$select = $mform->addElement('select', 'enrollment_status', '', $enrollment_status);
		// Set default to active.
		$select->setSelected(1);
		$mform->addElement('html', '</td></tr>');

		// Choose the course for which you will enter attendance credit.
		$mycourses = mod_istudymonthlyattend\teacher_info::get_allcourses($advisorid, $defaultterm, $defaultmonth);
		$mform->addElement('html', '<tr><td class="mycell2">Choose Course</td><td class="mycell2">');
		$select = $mform->addElement('select', 'mycourse', '', $mycourses);
		// Set default to ALL teachers.
		$select->setSelected('none');
		$mform->addElement('html', '</td></tr></table></body>');

		// RARE SCENERIO: The end of month 5 crosses over into the 2nd semester.
		// Allow month 5 to include students from the spring semester.
		$start_of_semester = strtotime(get_config('mod_istudy', 'spring_start_date'));
		$start_of_month6 = strtotime(get_config('mod_istudy', 'month06week01date'));
		// Return No since month 5 does not include spring courses.
		if ($start_of_semester == $start_of_month6) {
			// Do not include spring courses for month 5 (No = 0, Yes = 1).
			$mform->addElement('hidden', 'include_spring_courses', 0);
        	$mform->setType('include_spring_courses', PARAM_INT);
		}
		else {
			$options = array(
				0 => 'No',
				1 => 'Yes',
			);
			$select = $mform->addElement('select', 'include_spring_courses', 'Include Spring Courses for Monoth 5?', $options);
			// Set default to No.
			$select->setSelected(0);
			// IF MONTH 5 IS NOT CHOSEN, hide include_spring_courses.
			$mform->hideIf('include_spring_courses', 'monthchoice', 'neq', 5);
		}

		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
		$mform->addElement('html', '<br><h3>  </h3>');

		// Add standard buttons, common to all modules.
		$mform->addElement('html', '<div class="buttonsetup">');
		$this->add_action_buttons(true, 'Submit');
		$mform->addElement('html', '</div>');
    }
}