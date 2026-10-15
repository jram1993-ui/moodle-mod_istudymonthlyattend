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
class mod_istudymonthlyattend_choose_advisor_form extends \moodleform
{
	/**
     * Form definition.
     */
   	public function definition()
	{
        global $USER;

		$this_advisorid = $USER->id;
		$this_advisor_name =  mod_istudymonthlyattend\teacher_info::get_whichadvisor($this_advisorid);
		$mform = $this->_form;
		if ($this_advisor_name == 'Administrator') {
			$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-choose_month_form.css">');
			$mform->addElement('html', '<head><title>Choose Advisor</title></head><body>');
			$mform->addElement('html', '<h3>Monthly Attendance Cards - Admin Page</h3>');
			$mform->addElement('html', '<table class="searchform"><tr><th colspan="2"><h4>Choose the Advisor</h4></th></tr>');

			// Choose the Advisory Teacher
			$advisors = mod_istudymonthlyattend\teacher_info::get_advisors();
			$mform->addElement('html', '<tr><col width="50%"><col width="50%"><td class="mycell2">Choose the advisor to enter attendance for:</td><td  class="mycell2" colspan="13">');
			$select = $mform->addElement('select', 'advisorid', '', $advisors);
			// Set default to None
			$select->setSelected(0);
			$mform->addElement('html', '</td></tr></table></body>');

			$mform->addElement('hidden', 'id');
			$mform->setType('id', PARAM_INT);
			$mform->addElement('html', '<br><h3>  </h3>');

			// Add standard buttons, common to all modules.
			$mform->addElement('html', '<div class="buttonsetup">');
				$this->add_action_buttons($cancel = true, $submitlabel='Submit');
			$mform->addElement('html', '</div>');
		}
		else {
			// Hidden id number for sending the student count.
			$mform->addElement('hidden', 'advisorid', $this_advisorid);
			$mform->setType('advisorid', PARAM_INT);
		}
    }
}