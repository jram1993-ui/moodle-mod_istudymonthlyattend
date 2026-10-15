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
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_istudymonthlyattend;

use stdClass;

/**
 * Class teacher_info
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_info {

    // Check to see if not a teacher or admin.
    public static function not_a_teacher_or_admin() {
        global $DB, $USER;
        $advisorid = $USER->id;
        $itemid = utils::get_fieldid('whichteacher');
        $search_results = $DB->get_record('user_info_data', ['userid'=>$advisorid, 'fieldid'=>$itemid], 'data');
        if ($search_results->data == 'Parent' OR $search_results->data == 'Student') {
            return true;
        }
        else {
            return false;
        }
    }

    // Get advisor name - use the advisorid provided.
    public static function get_whichadvisor($teacherid) {
        global $DB;
        // If this is an administrator, then return Administrator.
        $itemid = utils::get_fieldid('whichteacher');
        $search_results = $DB->get_record('user_info_data', ['userid'=>$teacherid, 'fieldid'=>$itemid], 'data');
        if ($search_results->data == 'Administrator') {
            return 'Administrator';
        }
        else{
            $this_teacher = new stdClass();
            $sql = "SELECT * FROM {istudyteachers} WHERE teacherid = " .  $teacherid;
            $params = null;
            $this_teacher = $DB->get_record_sql($sql, $params);
            $teacher_full_name = $this_teacher->lastname . '-' . $this_teacher->firstname;
            return $teacher_full_name;
        }
    }

    // Get all of the courses available at the school that the advisor teaches
    // $defaultterm = 1 (Fall) or $defaultterm = 2 (Spring).
    public static function get_allcourses($advisorid, $defaultterm, $current_month) {
        global $DB;
        $count = 0;
        $session_count = -1;
        $courselist = [];
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');

        // Get a list of all the courses this teacher can teach. Convert the long string of courses into an array ($allcourses).
        $sql = "SELECT * FROM {istudycourses_teachers} WHERE  active = true AND teacherid = " . $advisorid . " ORDER By subject ASC, teacher_name ASC";
        $params = null;
        $allcourses = $DB->get_records_sql($sql, $params);
        foreach ($allcourses as $thiscourse) {
            $count++;
            $courselist[$count] = $thiscourse->subject . '/' . $thiscourse->teacher_name . '/' . $thiscourse->section;
        }
        // Get all courses for the fall term: fall == 1
        if ($defaultterm == 1 ) {
            // Get the database id for each fall session - Monday through Friday and session 1 through number of sessions.
            for ($this_day = 1; $this_day <= 5; $this_day++) {
                for ($my_session = 1; $my_session <= $number_of_sessions; $my_session++) {
                    $session_count = $session_count + 1;
                    $course_sessionid[$session_count] = utils::get_fieldid('fall' . $week_day[$this_day] . 'session' . $my_session);
                }
            }
            // Create course list with the courses that have students.
            $previouscourse_count = 0;
            $previouscourse[] = array ();
            $my_courses['none'] = 'None';
            for ($i = 1; $i <= $count; $i++) {
                // Break up the text into course name, teacher, section, and room number.
                $course_info = utils::get_mycourse($courselist[$i]);

                $table = 'istudy_monthatten';
                $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
                $params =['course'=>$course_info->section, 'teacher_id'=>$advisorid, 'month'=>$current_month];
                // Check if attendance information has already been saved this month for this subject, add the course if true.
                if ($DB->get_records_select($table, $select, $params)) {
                    $my_courses[$course_info->section] = $courselist[$i];
                }
                // Check for Fall students taking the course
                else if (utils::fallcourse_has_students($courselist[$i], $course_sessionid)) {
                    $my_courses[$course_info->section] = $courselist[$i];
                }
                // Check for class with Fall students only and no Spring students.
                else {
                    // Check if attendance information has already been saved this semester for this subject, add the course if true.
                    $table = 'istudy_monthatten';
                    $select = "course = :course AND teacher_id = :teacher_id AND month < :month";
                    $params =['course'=>$course_info->section, 'teacher_id'=>$advisorid, 'month'=>$current_month];
                    if ($DB->get_records_select($table, $select, $params)) {
                        $previouscourse_count = $previouscourse_count + 1;
                        $temp_my_courses[$previouscourse_count] = new stdClass();
                        $temp_my_courses[$previouscourse_count]->course_section = $course_info->section;
                        $temp_my_courses[$previouscourse_count]->course_name = '--Previous Enrollments-->' . $courselist[$i]. '<--None This Month--';
                    }
                }

            }
            if ($previouscourse_count > 0) {
                for ($j = 1; $j <= $previouscourse_count; $j++) {
                    $my_courses[$temp_my_courses[$j]->course_section] = $temp_my_courses[$j]->course_name;
                }
            }
            return $my_courses;
        }
        // Get all courses for the spring term: spring == 2.
        else {
            // Get the database id for each spring session - Monday through Friday and session 1 through number of sessions.
            for ($this_day = 1; $this_day <= 5; $this_day++) {
                for ($my_session = 1; $my_session <= $number_of_sessions; $my_session++) {
                    $session_count = $session_count + 1;
                    $course_sessionid[$session_count] = utils::get_fieldid('spring' . $week_day[$this_day] . 'session' . $my_session);
                }
            }
            // Create course list with the courses that have students.
            $previouscourse_count = 0;
            $previouscourse[] = array ();
            $my_courses['none'] = 'None';
            for ($i = 1; $i <= $count; $i++) {
                // Break up the text into course name, teacher, section, and room number.
                $course_info = utils::get_mycourse($courselist[$i]);

                // Check if attendance information has already been saved this month for this subject, add the course if true.
                $table = 'istudy_monthatten';
                $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
                $params =['course'=>$course_info->section, 'teacher_id'=>$advisorid, 'month'=>$current_month];
                if ($DB->get_records_select($table, $select, $params)) {
                    $my_courses[$course_info->section] = $courselist[$i];
                }
                // Check for Spring students taking the course and don't have attendance data yet
                else if (utils::springcourse_has_students($courselist[$i], $course_sessionid)) {
                    $my_courses[$course_info->section] = $courselist[$i];
                }
                // Check for class with Fall students only and no Spring students.
                else {
                    // Check if attendance information has already been saved for this semester for this subject, add the course if true.
                    $table = 'istudy_monthatten';
                    $select = "course = :course AND teacher_id = :teacher_id AND month < :month";
                    $params =['course'=>$course_info->section, 'teacher_id'=>$advisorid, 'month'=>$current_month];
                    if ($DB->get_records_select($table, $select, $params)) {
                        $previouscourse_count = $previouscourse_count + 1;
                        $temp_my_courses[$previouscourse_count] = new stdClass();
                        $temp_my_courses[$previouscourse_count]->course_section = $course_info->section;
                        $temp_my_courses[$previouscourse_count]->course_name = '--Previous Enrollments-->' . $courselist[$i]. '<--None This Month--';
                    }
                }
            }
            // Add all of the courses found to the list - "None" is at the top of the list and the only item so far.
            if ($previouscourse_count > 0) {
                for ($j = 1; $j <= $previouscourse_count; $j++) {
                    $my_courses[$temp_my_courses[$j]->course_section] = $temp_my_courses[$j]->course_name;
                }
            }
            return $my_courses;
        }
    }

    /**
     * Gets the advisor ($myteacher) room number.
     *
     * @param [type] $myteacher
     * @return void
     */
    public static function get_advisor_room($myteacher) {
        global $DB;
        // Separate the first name and last name that are joined with a dash (-).
        $teacher_lastname = strstr($myteacher, '-', true);
        $myteacher_temp = strstr($myteacher, '-');
        $teacher_firstname = substr($myteacher_temp, 1);

        $this_teacher = new stdClass();
        $sql = "SELECT * FROM {istudyteachers} WHERE lastname = '" .  $teacher_lastname . "' AND firstname = '" .  $teacher_firstname . "'";
        $params = null;
        $this_teacher = $DB->get_record_sql($sql, $params);
        return $this_teacher->room;
    }

    /**
     * Get a single advisor.
     *
     * @param [integer] $chosen_advisor
     * @return [string]
     */
    public static function get_advisor($chosen_advisor) {
        global $DB;
        $sql = "SELECT * FROM {istudyteachers} ORDER By lastname ASC, firstname ASC";
        $params = null;
        $all_advisors = $DB->get_records_sql($sql, $params);
        // First entry on the list starts with zero ($chosen_advisor - 1).
        $teacher_lastname = $all_advisors[$chosen_advisor - 1]->lastname;
        $teacher_firstname = $all_advisors[$chosen_advisor - 1]->firstname;
        $teacher_fullname = $teacher_lastname . '-' . $teacher_firstname;
        return $teacher_fullname;
    }

    // Get a list of all advisors - name only - the list will not start with 'All' or 'None'.
    public static function get_advisor_list() {
        global $DB;
        $sql = "SELECT * FROM {istudyteachers} ORDER By lastname ASC, firstname ASC";
        $params = null;
        $all_advisors = $DB->get_records_sql($sql, $params);
        $advisor = [];
        $count = 0;
        // Create the list of advisors.
       foreach ($all_advisors as $this_advisor) {
            $count = $count + 1;
            $teacher_lastname = $this_advisor->lastname;
            $teacher_firstname = $this_advisor->firstname;
            $advisor[$count] = $teacher_lastname . '-' . $teacher_firstname;
        }
        // Return the list of advisors.
        return $advisor;
    }

    // Get a list of all advisors - name only - first option is 'None'.
    public static function get_advisors() {
        global $DB;
        $sql = "SELECT * FROM {istudyteachers} ORDER By lastname ASC, firstname ASC";
        $params = null;
        $all_advisors = $DB->get_records_sql($sql, $params);
        $advisor = [];
        $advisor[0] = 'None';
        // Create the list of advisors
       foreach ($all_advisors as $this_advisor) {
            $teacher_lastname = $this_advisor->lastname;
            $teacher_firstname = $this_advisor->firstname;
            $teacher_id = $this_advisor->teacherid;
            $advisor[$teacher_id] = $teacher_lastname . '-' . $teacher_firstname;
        }
        // Return the list of advisors.
        return $advisor;
    }
}