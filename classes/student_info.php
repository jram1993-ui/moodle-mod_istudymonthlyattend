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
 * Class student_info
 *
 * @package    mod_istudymonthlyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_info {

    // Get specific student information.
    public static function get_student_info($student_id) {
        global $DB;
        $student_info = new stdClass();
        if ($student_temp = $DB->get_record('user', ['id' => $student_id] , 'firstname, lastname, deleted, suspended')) {
            $student_temp = $DB->get_record('user', ['id' => $student_id] , 'firstname, lastname, deleted, suspended');
            $student_info->firstname = $student_temp->firstname;
            $student_info->lastname = $student_temp->lastname;
            $student_info->startdate = utils::get_field_data($student_id, utils::get_fieldid('startdate'));
            $student_info->deleted = $student_temp->deleted;
            $student_info->suspended = $student_temp->suspended;
            $student_info->schoolid = utils::get_field_data($student_id, utils::get_fieldid('schoolid'));
            $graduation_year = utils::get_field_data($student_id, utils::get_fieldid('graduationyear'));
            $student_info->grade = utils::get_gradelevel($graduation_year);
            return $student_info;
        }
        else {
            $student_info->credits = 0;
            $student_info->suspended = true;
            $student_info->deleted = true;
            return $student_info;
        }
    }

    // Check so see if the student attended class this week.
    public static function student_attended_class($studentid, $advisorid, $courseid, $this_monday, $this_friday) {
        global $DB;
        $sunday_before = $this_monday - 86400;
        $saturday_after = $this_friday + 86400;
        if (is_numeric($studentid)) {
            $table = 'istudy_dailyattend_enter';
            $select = "student_id = :student_id AND teacher_id = :teacher_id AND course = :course AND attendance_date >= :attendance_date1 AND attendance_date <= :attendance_date2 AND attendance_status >= :attendance_status";
            $params =['student_id'=>$studentid, 'teacher_id'=>$advisorid, 'course'=>$courseid, 'attendance_date1'=>$sunday_before, 'attendance_date2'=>$saturday_after, 'attendance_status'=>2];
            if ($this_student = $DB->get_records_select($table, $select, $params)) {
                $my_attendance_status = 'red';
                foreach ($this_student AS $my_student) {
                    if ($my_student->attendance_status == 2 OR $my_student->attendance_status == 3) {
                        // The student was present.
                        return 'green';
                    }
                    else if ($my_student->attendance_status == 4) {
                        // The student was tardy.
                        $my_attendance_status = 'yellow';
                    }
                }
                return $my_attendance_status;
            }
            else {
                // The student was absent.
                return 'red';
            }
        }
        else {
            // The student was absent.
            return 'red';
        }
    }
    // Use the studentid to get the student first name and lastname - also include if the account has been deleted or suspended.
    public static function get_single_student($studentid) {
        global $DB;
        $mystudent = $DB->get_record('user', ['id' => $studentid], 'firstname, lastname, deleted, suspended');
        return $mystudent;
    }
    // Find all of the students in a course using courseid and advisorid.
    public static function get_students($courseid, $course_name, $advisorid, $monthchoice, $schoolterm, $enrollment_status, $include_spring_courses) {
        global $DB;
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');

        // Find the year for the semester the month lies in - add 1 to the year if we are in the spring semester
        // OR (month5 carried over into the spring semester - month5 has weeks in the fall and weeks in the spring).
        $year_for_semester = date("Y", utils::get_schoolyear()->year_start);
        if (($include_spring_courses == 1) OR ($schoolterm == 'spring'))  {
            $year_for_semester = $year_for_semester + 1;
        }
        // Get the database id for each fall session - the first item in the array will start with 0.
        $session_count = -1;
        if ($schoolterm == 'fall' ) {
            for ($this_day = 1; $this_day <= 5; $this_day++) {
                for ($my_session = 1; $my_session <= $number_of_sessions; $my_session++) {
                    $session_count = $session_count + 1;
                    $course_sessionid[$session_count] = utils::get_fieldid('fall' . $week_day[$this_day] . 'session' . $my_session);
                }
            }
        }
        // Get the database id for each spring session.
        else {
            for ($this_day = 1; $this_day <= 5; $this_day++) {
                for ($my_session = 1; $my_session <= $number_of_sessions; $my_session++) {
                    $session_count = $session_count + 1;
                    $course_sessionid[$session_count] = utils::get_fieldid('spring' . $week_day[$this_day] . 'session' . $my_session);
                }
            }
        }
        // Get all of the student with an active enrollment (currently enrolled in the school).
        if ($enrollment_status == 1) {
            $count = -1;
            // Get the students that already have attendance recorded.
            $table = 'istudy_monthatten';
            $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
            $params =['course'=>$courseid, 'teacher_id'=>$advisorid, 'month'=>$monthchoice];
            $studentattendances = $DB->get_records_select($table, $select, $params);
            foreach ($studentattendances as $studentattendance) {
                $temp_saved_student = student_info::get_student_info($studentattendance->student_id);
                // Don't include students that have been deleted or suspended.
                if (($temp_saved_student->deleted == false) AND ($temp_saved_student->suspended == false)) {
                    $count = $count +1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $studentattendance->student_id;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->resources = $studentattendance->resources;
                    $students_found[$count]->creditsweek1 = $studentattendance->creditsweek1;
                    $students_found[$count]->assigmentweek1 = $studentattendance->assignmentweek1;
                    $students_found[$count]->creditsweek2 = $studentattendance->creditsweek2;
                    $students_found[$count]->assigmentweek2 = $studentattendance->assignmentweek2;
                    $students_found[$count]->creditsweek3 = $studentattendance->creditsweek3;
                    $students_found[$count]->assigmentweek3 = $studentattendance->assignmentweek3;
                    $students_found[$count]->creditsweek4 = $studentattendance->creditsweek4;
                    $students_found[$count]->assigmentweek4 = $studentattendance->assignmentweek4;
                }
            }
            // Get the students that are in the course, but have no saved attendance.
            $already_saved_count = $count;
            // Get the id of all students that are taking the course.
            $sql = "SELECT id, fieldid, userid FROM {user_info_data} WHERE data LIKE '%" . $course_name . "%'";
            $params = null;
            $students_without_attendance = $DB->get_records_sql($sql, $params);
            foreach ($students_without_attendance as $this_student) {
                // If a student has already been saved, skip this student.
                $already_saved = false;
                if ($already_saved_count > -1) {
                    for ($j = 0; $j <= $already_saved_count; $j++) {
                        if ($students_found[$j]->id == $this_student->userid){
                            $already_saved = true;
                        }
                    }
                }
                // Check that the student is taking the course in the current semester.
                $taking_course = false;
                for ($i = 0; $i <= $session_count; $i++) {
                    if ($this_student->fieldid == $course_sessionid[$i]) {
                        $taking_course = true;
                    }
                }
                $temp_saved_student = student_info::get_student_info($this_student->userid);
                // Only add students to the list if they don't have any attendance data for the day and if they have not been deleted or suspended.
                If (($already_saved == false) and ($taking_course == true) and ($temp_saved_student->deleted == false) and ($temp_saved_student->suspended == false)) {
                    $count = $count +1;
                    $already_saved_count = $already_saved_count + 1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $this_student->userid;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->resources = '';
                    $students_found[$count]->creditsweek1 = 0;
                    $students_found[$count]->assigmentweek1 = '';
                    $students_found[$count]->creditsweek2 = 0;
                    $students_found[$count]->assigmentweek2 = '';
                    $students_found[$count]->creditsweek3 = 0;
                    $students_found[$count]->assigmentweek3 = '';
                    $students_found[$count]->creditsweek4 = 0;
                    $students_found[$count]->assigmentweek4 = '';
                }
            }
        }
        // Get all of the students that are inactive (currently not enrolled in the school).
        elseif ($enrollment_status == 0 AND ($monthchoice == 1 OR $monthchoice == 6)) {
            $count = -1;
            // Get the students that already have attendance recorded.
            $table = 'istudy_monthatten';
            $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
            $params =['course'=>$courseid, 'teacher_id'=>$advisorid, 'month'=>$monthchoice];
            $studentattendances = $DB->get_records_select($table, $select, $params);
            foreach ($studentattendances as $studentattendance) {
                $temp_saved_student = student_info::get_student_info($studentattendance->student_id);
                if ($temp_saved_student->suspended == true AND $temp_saved_student->deleted == false) {
                    $count = $count +1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $studentattendance->student_id;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->resources = $studentattendance->resources;
                    $students_found[$count]->creditsweek1 = $studentattendance->creditsweek1;
                    $students_found[$count]->assigmentweek1 = $studentattendance->assignmentweek1;
                    $students_found[$count]->creditsweek2 = $studentattendance->creditsweek2;
                    $students_found[$count]->assigmentweek2 = $studentattendance->assignmentweek2;
                    $students_found[$count]->creditsweek3 = $studentattendance->creditsweek3;
                    $students_found[$count]->assigmentweek3 = $studentattendance->assignmentweek3;
                    $students_found[$count]->creditsweek4 = $studentattendance->creditsweek4;
                    $students_found[$count]->assigmentweek4 = $studentattendance->assignmentweek4;
                }
            }
            // Get the students that are in the course, but have no saved attendance.
            $already_saved_count = $count;
            // Get the id of all students that are taking the course.
            $sql = "SELECT id, fieldid, userid FROM {user_info_data} WHERE data = '" . $course_name . "'";
            $params = null;
            $students_without_attendance = $DB->get_records_sql($sql, $params);
            foreach ($students_without_attendance as $this_student) {
                // If a student has already been saved, skip this student.
                $already_saved = false;
                if ($already_saved_count > -1) {
                    for ($j = 0; $j <= $already_saved_count; $j++) {
                        if ($students_found[$j]->id == $this_student->userid){
                            $already_saved = true;
                        }
                    }
                }
                // Check that the student is taking the course in the current semester.
                $taking_course = false;
                for ($i = 0; $i <= $session_count; $i++) {
                    if ($this_student->fieldid == $course_sessionid[$i]) {
                        $taking_course = true;
                    }
                }
                $temp_saved_student = student_info::get_student_info($this_student->userid);
                // Only add students to the list if they don't have any attendance data for the day and if they have not been deleted or suspended.
                If (($already_saved == false) and ($taking_course == true) and ($temp_saved_student->deleted == false) and ($temp_saved_student->suspended == true)) {
                    $count = $count +1;
                    $already_saved_count = $already_saved_count + 1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $this_student->userid;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->resources = '';
                    $students_found[$count]->creditsweek1 = 0;
                    $students_found[$count]->assigmentweek1 = '';
                    $students_found[$count]->creditsweek2 = 0;
                    $students_found[$count]->assigmentweek2 = '';
                    $students_found[$count]->creditsweek3 = 0;
                    $students_found[$count]->assigmentweek3 = '';
                    $students_found[$count]->creditsweek4 = 0;
                    $students_found[$count]->assigmentweek4 = '';
                }
            }
        }
        // Get all of the students that are inactive or have been dropped from the course this month and had the class the previous month (exclude month 1).
        else {
            $count = -1;
            // Get the students that already have attendance recorded.
            $table = 'istudy_monthatten';
            $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
            $params =['course'=>$courseid, 'teacher_id'=>$advisorid, 'month'=>$monthchoice];
            $studentattendances = $DB->get_records_select($table, $select, $params);
            foreach ($studentattendances as $studentattendance) {
                $temp_saved_student = student_info::get_student_info($studentattendance->student_id);
                if ($temp_saved_student->suspended == true AND $temp_saved_student->deleted == false) {
                    $count = $count +1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $studentattendance->student_id;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->resources = $studentattendance->resources;
                    $students_found[$count]->creditsweek1 = $studentattendance->creditsweek1;
                    $students_found[$count]->assigmentweek1 = $studentattendance->assignmentweek1;
                    $students_found[$count]->creditsweek2 = $studentattendance->creditsweek2;
                    $students_found[$count]->assigmentweek2 = $studentattendance->assignmentweek2;
                    $students_found[$count]->creditsweek3 = $studentattendance->creditsweek3;
                    $students_found[$count]->assigmentweek3 = $studentattendance->assignmentweek3;
                    $students_found[$count]->creditsweek4 = $studentattendance->creditsweek4;
                    $students_found[$count]->assigmentweek4 = $studentattendance->assignmentweek4;
                }
            }
            // Get the students that are in the course, but have no saved attendance.
            $already_saved_count = $count;
            // Get the id of all students that are taking the course.
            $sql = "SELECT id, fieldid, userid FROM {user_info_data} WHERE data = '" . $course_name . "'";
            $params = null;
            $students_without_attendance = $DB->get_records_sql($sql, $params);
            foreach ($students_without_attendance as $this_student) {
                // If a student has already been saved, skip this student.
                $already_saved = false;
                if ($already_saved_count > -1) {
                    for ($j = 0; $j <= $already_saved_count; $j++) {
                        if ($students_found[$j]->id == $this_student->userid){
                            $already_saved = true;
                        }
                    }
                }
                // Check that the student is taking the course in the current semester.
                $taking_course = false;
                for ($i = 0; $i <= $session_count; $i++) {
                    if ($this_student->fieldid == $course_sessionid[$i]) {
                        $taking_course = true;
                    }
                }
                $temp_saved_student = student_info::get_student_info($this_student->userid);
                // Only add students to the list if they don't have any attendance data for the day and if they have not been deleted or suspended.
                If (($already_saved == false) and ($taking_course == true) and ($temp_saved_student->deleted == false) and ($temp_saved_student->suspended == true)) {
                    $count = $count +1;
                    $already_saved_count = $already_saved_count + 1;
                    $students_found[$count] = new stdClass();
                    $students_found[$count]->id = $this_student->userid;
                    $students_found[$count]->firstname = $temp_saved_student->firstname;
                    $students_found[$count]->lastname = $temp_saved_student->lastname;
                    $students_found[$count]->startdate = $temp_saved_student->startdate;
                    $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                    $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                    $students_found[$count]->grade = $temp_saved_student->grade;
                    $students_found[$count]->resources = '';
                    $students_found[$count]->creditsweek1 = 0;
                    $students_found[$count]->assigmentweek1 = '';
                    $students_found[$count]->creditsweek2 = 0;
                    $students_found[$count]->assigmentweek2 = '';
                    $students_found[$count]->creditsweek3 = 0;
                    $students_found[$count]->assigmentweek3 = '';
                    $students_found[$count]->creditsweek4 = 0;
                    $students_found[$count]->assigmentweek4 = '';
                }
            }
            // Get the students that completed or dropped the course during the current month.
            // The course was removed before monthly attendance was added so we'll get the course from last month.
            $already_saved_count = $count;
            $last_month = $monthchoice - 1;
            $table = 'istudy_monthatten';
            $select = "course = :course AND teacher_id = :teacher_id AND month = :month";
            $params =['course'=>$courseid, 'teacher_id'=>$advisorid, 'month'=> $last_month];
            $students_last_month = $DB->get_records_select($table, $select, $params);
            foreach ($students_last_month as $this_student) {
                // Only add the student if student does not already have attendance entered this month.
                $table = 'istudy_monthatten';
                $select = "course = :course AND teacher_id = :teacher_id AND student_id = :student_id AND month = :month";
                $params =['course'=>$courseid, 'teacher_id'=>$advisorid, 'student_id'=>$this_student->student_id, 'month'=>$monthchoice];
                // If the student has no attendance this month, add the student to the list.
                if (!($students_last_month = $DB->get_records_select($table, $select, $params))) {
                     // If a student has already been saved, skip this student.
                    $already_saved = false;
                    if ($already_saved_count > -1) {
                        for ($j = 0; $j <= $already_saved_count; $j++) {
                            if ($students_found[$j]->id == $this_student->student_id){
                                $already_saved = true;
                            }
                        }
                    }
                    $temp_saved_student = student_info::get_student_info($this_student->student_id);
                    // Only add students to the list if they don't have any attendance data for the day and if they have not been deleted or suspended.
                    If (($already_saved == false) and ($temp_saved_student->deleted == false)) {
                        $count = $count +1;
                        $already_saved_count = $already_saved_count + 1;
                        $students_found[$count] = new stdClass();
                        $students_found[$count]->id = $this_student->student_id;
                        $students_found[$count]->firstname = $temp_saved_student->firstname;
                        $students_found[$count]->lastname = $temp_saved_student->lastname;
                        $students_found[$count]->startdate = $temp_saved_student->startdate;
                        $students_found[$count]->fullname = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                        $students_found[$count]->schoolid = $temp_saved_student->schoolid;
                        $students_found[$count]->grade = $temp_saved_student->grade;
                        $students_found[$count]->resources = '';
                        $students_found[$count]->creditsweek1 = 0;
                        $students_found[$count]->assigmentweek1 = '';
                        $students_found[$count]->creditsweek2 = 0;
                        $students_found[$count]->assigmentweek2 = '';
                        $students_found[$count]->creditsweek3 = 0;
                        $students_found[$count]->assigmentweek3 = '';
                        $students_found[$count]->creditsweek4 = 0;
                        $students_found[$count]->assigmentweek4 = '';
                    }
                }
            }
        }
        // Sort the students alphabetically.
        for ($i=0; $i<=$count; $i++){
            $val = $students_found[$i];
            $j = $i-1;
            while($j>=0 && $students_found[$j]->fullname > $val->fullname){
                $students_found[$j+1] = $students_found[$j];
                $j--;
            }
            $students_found[$j+1] = $val;
        }
        if ($count == -1) {
            return 'No Students';
        }
        else {
            return $students_found;
        }
    }
}