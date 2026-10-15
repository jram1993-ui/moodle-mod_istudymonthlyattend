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
 * Class utils
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    // Set the time to 12:00 pm for this day.
    public static function set_to_12pm($mydatetoday) {
        // Adjust the day so that the time is set to 12:00 pm.
        // $mydatetoday = ((intval($mydatetoday/86400))*86400)+(86400/2);
        return $mydatetoday;
    }

    // Get the default month for the attendance cards.
    public static function get_defaultmonth() {
        $mymonth[2] = get_config('mod_istudy', 'month02week01date');
        $mymonth[3] = get_config('mod_istudy', 'month03week01date');
        $mymonth[4] = get_config('mod_istudy', 'month04week01date');
        $mymonth[5] = get_config('mod_istudy', 'month05week01date');
        $mymonth[6] = get_config('mod_istudy', 'month06week01date');
        $mymonth[7] = get_config('mod_istudy', 'month07week01date');
        $mymonth[8] = get_config('mod_istudy', 'month08week01date');
        $mymonth[9] = get_config('mod_istudy', 'month09week01date');
        $mymonth[10] = get_config('mod_istudy', 'month10week01date');
        $mymonth[11] = get_config('mod_istudy', 'month11week01date');
        $default_month = 1;
        // Get the date  today.
        $mydatetoday = utils::set_to_12pm(time());
        // There are 86400 seconds in a day. Add 2 days so that the month will switch on the Wednesday of the first week of each month.
        $two_days = 86400+86400;
        for ($i = 2; $i <= 11; $i++) {
            // Remain in the previous month for the first 2 days of the current month.
            $current_month = strtotime($mymonth[$i]);
            $current_month_plus2 = $current_month + $two_days;
            if (($current_month_plus2 < $mydatetoday) AND ($current_month > 0)) {
                $default_month = $i;
            }
        }
        return $default_month;
    }

    /**
     * Make sure that the date is not zero.
     *
     * @param [object] $submission
     * @return string
     */
    public static function check_date($submission) {
        if ($submission > 0) {
            return date("m-d-Y", $submission);
        }
        else {
            return '';
        }
    }

    /**
     * Calculate the grade level from the graduation year.
     *
     * @param [integer] $grad_year
     * @return [integer] $my_grade_level
     */
    public static function get_gradelevel($grad_year) {
        $currentmonth = date('m');
        $currentyear = date('Y');
        if ($grad_year <= $currentyear) {
            $years_left = 0;
        }
        else if ($currentmonth > 7) {
            $years_left = $grad_year - ($currentyear + 1);
        }
        else {
            $years_left = $grad_year - $currentyear;
        }
        $my_grade_level = 12 - $years_left;
        return $my_grade_level;
    }

    /**
     * Remove extra characters from the string.
     *
     * @param [string] $snip
     * @return string
     */
    public static function remove_extra_symbols($snip) {
        $snip = str_replace("'", "’", $snip); // remove apostrophe
        $snip = str_replace("\t", "", $snip); // remove tabs
        // $snip = str_replace("\n", "", $snip); // remove new lines
        // $snip = str_replace("\r", "", $snip); // remove carriage returns
        return $snip;
    }

    /**
     * Get the user_info_field id to use in searching user_info_data.
     *
     * @param [string] $item_name
     * @return [integer]
     */
    public static function get_fieldid($item_name) {
        global $DB;
        $item = $DB->get_record('user_info_field', ['shortname' => $item_name], 'id');
        return $item->id;
    }

    /**
     * Search user_info_data for student data using the student id and user_info_field id.
     *
     * @param [integer] $student_id
     * @param [integer] $itemid
     * @return [string]
     */
    public static function get_field_data($student_id, $itemid) {
        global $DB;
        if ($DB->record_exists('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid])) {
            $search_results = $DB->get_record('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid], 'data');
            return $search_results->data;
        }
        else {
            return '';
        }
    }

    /**
     * Replace the dash in the advisor name with a comma and a space.
     *
     * @param [string] $myteacher
     * @return [string]
     */
     public static function put_comma_in_name($myteacher) {
        $postion = strrpos($myteacher,"-");
        $name_with_dash = substr($myteacher, $postion);
        $name_with_comma = ', ' . substr($name_with_dash,1);
        $new_name = str_replace($name_with_dash,$name_with_comma,$myteacher);
        return $new_name;
    }

    // Return this school year from the beginning July 1 until June 30 at the end of the school year.
    public static function get_schoolyear() {
        $month_info = new stdClass();
        // Find the beginning and the end of the current school year
        $month_today = strval(date("m"));
        // Use the current year for months July-December - subrtract 1 year from the year for January-June.
        if ($month_today >= 7) {
            $year_start = strval(date("Y"));
        }
        else {
            $year_start = strval(date("Y")) - 1;
        }
        $year_end = $year_start + 1;
        $year_start_date = mktime(0,0,0,7,1,$year_start);
        $year_end_date = mktime(0,0,0,6,30,$year_end);
        $month_info->year_start = $year_start_date;
        $month_info->year_end = $year_end_date;
        return $month_info;
    }

    // Find complete course name and info using the section number.
    public static function get_course_name($courseid, $advisorid) {
        global $DB;
        $table = 'istudycourses_teachers';
        $select = "section = :section AND teacherid = :teacherid";
        $params =['section'=>$courseid, 'teacherid'=>$advisorid];
        if ($thiscourse = $DB->get_record_select($table, $select, $params)) {
            $complete_course_name = $thiscourse->subject . '/' . $thiscourse->teacher_name . '/' . $thiscourse->section;
            return $complete_course_name;
        }
        else {
            return 'No Class/unknown-teacher/1234';
        }
    }

    // Count the number of coures for the Fall or Spring terms.
    public static function get_coursecount($schoolterm, $course_name) {
        global $DB;
        $count = 0;
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
        $week_day[1] = $schoolterm . 'monday';
        $week_day[2] = $schoolterm . 'tuesday';
        $week_day[3] = $schoolterm . 'wednesday';
        $week_day[4] = $schoolterm . 'thursday';
        $week_day[5] = $schoolterm . 'friday';

        for ($day = 1; $day <= 5; $day++) {
            for ($session = 1; $session <= $number_of_sessions; $session++) {
                $day_and_session = get_fieldid($week_day[$day] . 'session' . $session);
                $count = $count + $DB->count_records('user_info_data', ['fieldid'=>$day_and_session, 'data' => $course_name]);
            }
        }
        return $count;
    }

    // Check so see if a spring course has students - check Monday through Friday and session 1 through number of sessions.
    public static function springcourse_has_students($course_name, $course_sessionid) {
        global $DB;
        $sql = "SELECT id, fieldid, userid FROM {user_info_data} WHERE data = '" . $course_name . "'";
        $params = null;
        $mycourses = $DB->get_records_sql($sql, $params);

        foreach ($mycourses as $mycourse) {
            foreach ($course_sessionid as $this_sessionid) {
                $this_student = $DB->get_record('user', array('id' => $mycourse->userid), 'deleted, suspended', 'IGNORE_MISSING');
                if (($mycourse->fieldid == $this_sessionid) AND !($this_student->deleted) AND !($this_student->suspended)) {
                    return true;
                }
            }

        }
        return false;
    }

    // Check so see if a fall course has students - check Monday through Friday and session 1 through number of sessions.
    public static function fallcourse_has_students($course_name, $course_sessionid) {
        global $DB;
        $sql = "SELECT id, fieldid, userid FROM {user_info_data} WHERE data LIKE '%" . $course_name . "%'";
        $params = null;
        $mycourses = $DB->get_records_sql($sql, $params);
        foreach ($mycourses as $mycourse) {
            foreach ($course_sessionid as $this_sessionid) {
                $this_student = $DB->get_record('user', array('id' => $mycourse->userid), 'deleted, suspended', 'IGNORE_MISSING');
                if (($mycourse->fieldid == $this_sessionid) AND !($this_student->deleted) AND !($this_student->suspended)) {
                    return true;
                }
            }

        }
        return false;
    }

    // **Return the course info - subject, teacher, section, and room number.
    public static function get_mycourse($mycourse) {
        $course_info = new \stdClass();
        $course_info->subject = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->teacher = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->section = trim($mycourse);
        $course_info->room = teacher_info::get_advisor_room($course_info->teacher);
        return $course_info;
    }

    // Check to see that a student has a specific course - check according to month (1-11) and school term (Fall or Spring).
    public static function mycourse_exists($studentid, $course_name, $monthchoice, $schoolterm) {
        global $DB;
        // Choose the term according to the month chosen.
        if ($monthchoice < 5) {
            $schoolterm = 'fall';
        }
        else if ($monthchoice >=6) {
            $schoolterm = 'spring';
        }
        // This is needed for the database search - search courses according to term and day of the week.
        $week_day[1] = $schoolterm . 'monday';
        $week_day[2] = $schoolterm . 'tuesday';
        $week_day[3] = $schoolterm . 'wednesday';
        $week_day[4] = $schoolterm . 'thursday';
        $week_day[5] = $schoolterm . 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
        // This is needed for the database search - search courses according to term, day of the week, and session.
        for ($day = 1; $day <= 5; $day++) {
            for ($session = 1; $session <= $number_of_sessions; $session++) {
                $day_and_session = get_fieldid($week_day[$day] . 'session' . $session);
                $mycourse = $DB->get_record('user_info_data', ['userid'=>$studentid, 'fieldid'=>$day_and_session], 'data');
                // If the student has the course, return true.
                if ($mycourse->data == $course_name) {
                    return true;
                }
            }
        }
        // The student does not have the course, return false.
        return false;
    }

    // Get the resources used for the course - get the resources from the first student saved in the attendance database and taking the course.
    public static function get_myresources($courseid, $monthchoice, $advisorid) {
        global $DB;
        $resources_used = '';
        $table = 'istudy_monthatten';
        $start_of_year = utils::get_schoolyear()->year_start;
        $select = "course = :course AND month = :month AND teacher_id = :teacher_id AND timecreated > :timecreated";
        $params =['course'=>$courseid, 'month'=>$monthchoice, 'teacher_id'=>$advisorid, 'timecreated'=>$start_of_year];
        if ($myresources_used = $DB->get_records_select($table, $select, $params)) {
            foreach ($myresources_used as $firstElement) {
                $resources_used = $firstElement->resources;
                return $resources_used;
            }
        }
        return $resources_used;
    }
}