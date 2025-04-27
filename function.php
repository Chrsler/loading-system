<?php
// Generic function to fetch all records from a table
function getAllRecords($table)
{
    global $conn;
    $sql    = "SELECT * FROM $table";
    $result = $conn->query($sql);

    $records = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    return $records;
}

// Generic function to get a single record by ID
function getRecordById($table, $id_column, $id)
{
    global $conn;
    $sql    = "SELECT * FROM $table WHERE $id_column = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Generic function to delete a record
function deleteRecord($table, $id_column, $id)
{
    global $conn;
    $sql = "DELETE FROM $table WHERE $id_column = $id";
    return $conn->query($sql);
}

// Get all colleges
function getAllColleges()
{
    return getAllRecords('College');
}

// Get all programs
function getAllPrograms()
{
    global $conn;
    $sql    = "SELECT p.*, c.college_name FROM Program p 
            JOIN College c ON p.college_id = c.college_id";
    $result = $conn->query($sql);

    $programs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    return $programs;
}

// Get all subjects
function getAllSubjects()
{
    global $conn;
    $sql    = "SELECT s.*, p.subject_code as prerequisite_code 
            FROM Subject s 
            LEFT JOIN Subject p ON s.prerequisite_id = p.subject_id";
    $result = $conn->query($sql);

    $subjects = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

// Get all professors
function getAllProfessors()
{
    return getAllRecords('Professor');
}

// Get all sections
function getAllSections()
{
    global $conn;
    $sql    = "SELECT s.*, p.program_name FROM Section s 
            JOIN Program p ON s.program_id = p.program_id";
    $result = $conn->query($sql);

    $sections = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
    return $sections;
}

// Get all students
function getAllStudents()
{
    global $conn;
    $sql    = "SELECT st.*, p.program_name, s.section_code, st.status 
            FROM Student st 
            JOIN Program p ON st.program_id = p.program_id 
            JOIN Section s ON st.section_id = s.section_id";
    $result = $conn->query($sql);

    $students = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    return $students;
}

// Get all curriculum entries
function getAllCurriculum()
{
    global $conn;
    $sql    = "SELECT c.*, p.program_name, s.subject_code, s.title 
            FROM Curriculum c 
            JOIN Program p ON c.program_id = p.program_id 
            JOIN Subject s ON c.subject_id = s.subject_id";
    $result = $conn->query($sql);

    $curriculum = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $curriculum[] = $row;
        }
    }
    return $curriculum;
}

// Get all teaching assignments
function getAllTeachingAssignments()
{
    global $conn;
    $sql    = "SELECT ta.*, CONCAT(p.first_name, ' ', p.last_name) as professor_name, 
            s.subject_code, sc.section_code 
            FROM Teaching_Assignment ta 
            JOIN Professor p ON ta.professor_id = p.professor_id 
            JOIN Subject s ON ta.subject_id = s.subject_id 
            JOIN Section sc ON ta.section_id = sc.section_id";
    $result = $conn->query($sql);

    $assignments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    }
    return $assignments;
}

// Get all enrollments
function getAllEnrollments()
{
    global $conn;
    $sql    = "SELECT e.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
            sub.subject_code, sub.title, CONCAT(p.first_name, ' ', p.last_name) as professor_name 
            FROM Enrollment e 
            JOIN Student s ON e.student_id = s.student_id 
            JOIN Subject sub ON e.subject_id = sub.subject_id 
            JOIN Professor p ON e.professor_id = p.professor_id";
    $result = $conn->query($sql);

    $enrollments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $enrollments[] = $row;
        }
    }
    return $enrollments;
}

// Get all schedules for a specific teaching assignment
function getSchedulesByAssignmentId($assignment_id)
{
    global $conn;
    $sql  = "
        SELECT s.*, sub.subject_code, sec.section_code, prog.program_code, prog.program_name
        FROM Schedule s
        JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id
        JOIN Subject sub ON ta.subject_id = sub.subject_id
        JOIN Section sec ON ta.section_id = sec.section_id
        JOIN Program prog ON sec.program_id = prog.program_id
        WHERE s.assignment_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();
    return $schedules;
}

// Get a single schedule by ID
function getScheduleById($schedule_id)
{
    global $conn;
    $sql  = "SELECT * FROM Schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        $stmt->close();
        return $schedule;
    }
    $stmt->close();
    return null;
}

// Check for room conflicts
// Enhanced conflict checking functions
function checkRoomConflict($day, $start_time, $end_time, $room, $exclude_schedule_id = null)
{
    global $conn;
    $sql = "SELECT s.* FROM Schedule s 
            WHERE s.day = ? AND s.room = ? 
            AND (
                (s.start_time < ? AND s.end_time > ?) OR  -- New schedule overlaps existing
                (s.start_time < ? AND s.end_time > ?) OR  -- Existing overlaps new
                (s.start_time >= ? AND s.end_time <= ?)   -- New schedule contains existing
            )";

    if ($exclude_schedule_id) {
        $sql .= " AND s.schedule_id != ?";
    }

    $stmt = $conn->prepare($sql);
    if ($exclude_schedule_id) {
        $stmt->bind_param("ssssssssi", $day, $room, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time, $exclude_schedule_id);
    } else {
        $stmt->bind_param("ssssssss", $day, $room, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time);
    }

    $stmt->execute();
    $result   = $stmt->get_result();
    $conflict = $result->fetch_assoc();
    $stmt->close();

    return $conflict;
}

function checkProfessorConflict($day, $start_time, $end_time, $professor_id, $exclude_schedule_id = null)
{
    global $conn;
    $sql = "SELECT s.* FROM Schedule s
            JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id
            WHERE s.day = ? AND ta.professor_id = ?
            AND (
                (s.start_time < ? AND s.end_time > ?) OR
                (s.start_time < ? AND s.end_time > ?) OR
                (s.start_time >= ? AND s.end_time <= ?)
            )";

    if ($exclude_schedule_id) {
        $sql .= " AND s.schedule_id != ?";
    }

    $stmt = $conn->prepare($sql);
    if ($exclude_schedule_id) {
        $stmt->bind_param("sissssssi", $day, $professor_id, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time, $exclude_schedule_id);
    } else {
        $stmt->bind_param("sissssss", $day, $professor_id, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time);
    }

    $stmt->execute();
    $result   = $stmt->get_result();
    $conflict = $result->fetch_assoc();
    $stmt->close();

    return $conflict;
}

// Add a new schedule
function addSchedule($assignment_id, $day, $start_time, $end_time, $room)
{
    global $conn;
    $sql  = "INSERT INTO Schedule (assignment_id, day, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $assignment_id, $day, $start_time, $end_time, $room);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Update an existing schedule
function updateSchedule($schedule_id, $assignment_id, $day, $start_time, $end_time, $room)
{
    global $conn;
    $sql  = "UPDATE Schedule SET assignment_id = ?, day = ?, start_time = ?, end_time = ?, room = ? WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("issssi", $assignment_id, $day, $start_time, $end_time, $room, $schedule_id);
    $result = $stmt->execute();
    if ($result === false) {
        error_log("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    return $result;
}

// Delete a schedule
function deleteSchedule($schedule_id)
{
    return deleteRecord('Schedule', 'schedule_id', $schedule_id);
}

function getTeachingAssignmentsByProfessorId($professor_id)
{
    global $conn;
    $sql  = "SELECT ta.assignment_id, ta.professor_id, ta.subject_id, ta.section_id, 
                   p.first_name, p.last_name, 
                   s.subject_code, s.title, 
                   sec.section_code, 
                   prog.program_code, prog.program_name 
            FROM Teaching_Assignment ta
            JOIN Professor p ON ta.professor_id = p.professor_id
            JOIN Subject s ON ta.subject_id = s.subject_id
            JOIN Section sec ON ta.section_id = sec.section_id
            JOIN Program prog ON sec.program_id = prog.program_id
            WHERE ta.professor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result      = $stmt->get_result();
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
    return $assignments;
}
?>