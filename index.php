<?php
ob_start();

include 'config.php';
include 'function.php';

// Handle AJAX schedule addition
// Handle AJAX schedule addition
if (isset($_POST['ajax_add_schedule'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $professor_id  = sanitize($_POST['professor_id'] ?? '');
        $assignment_id = sanitize($_POST['assignment_id'] ?? '');
        $day           = sanitize($_POST['day'] ?? '');
        $start_time    = sanitize($_POST['start_time'] ?? '');
        $end_time      = sanitize($_POST['end_time'] ?? '');
        $room          = sanitize($_POST['room'] ?? '');

        error_log("Add Schedule Request: professor_id=$professor_id, assignment_id=$assignment_id, day=$day, start_time=$start_time, end_time=$end_time, room=$room");

        // Validate required fields
        $required = [
            'Subject' => $assignment_id,
            'Day' => $day,
            'Start time' => $start_time,
            'End time' => $end_time,
            'Room' => $room,
            'Professor' => $professor_id,
        ];

        foreach ($required as $field => $value) {
            if (empty($value)) {
                throw new Exception("$field is required");
            }
        }

        // Check for conflicts
        $room_conflict = checkRoomConflict($day, $start_time, $end_time, $room);
        if ($room_conflict) {
            $conflict_details = getConflictDetails($room_conflict);
            throw new Exception("Room conflict: " . $conflict_details);
        }

        $prof_conflict = checkProfessorConflict($day, $start_time, $end_time, $professor_id);
        if ($prof_conflict) {
            $conflict_details = getConflictDetails($prof_conflict);
            throw new Exception("Professor conflict: " . $conflict_details);
        }

        // Add the schedule
        if (!addSchedule($assignment_id, $day, $start_time, $end_time, $room)) {
            throw new Exception("Database error: Failed to save schedule");
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Schedule added successfully!',
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Add Schedule Error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
        exit;
    }
}

// Handle AJAX schedule update
if (isset($_POST['ajax_update_schedule'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $schedule_id   = sanitize($_POST['schedule_id'] ?? '');
        $assignment_id = sanitize($_POST['assignment_id'] ?? '');
        $day           = sanitize($_POST['day'] ?? '');
        $start_time    = sanitize($_POST['start_time'] ?? '');
        $end_time      = sanitize($_POST['end_time'] ?? '');
        $room          = sanitize($_POST['room'] ?? '');
        $professor_id  = sanitize($_POST['professor_id'] ?? '');

        error_log("Update Schedule Request: schedule_id=$schedule_id, professor_id=$professor_id, assignment_id=$assignment_id, day=$day, start_time=$start_time, end_time=$end_time, room=$room");

        // Validate required fields
        $required = [
            'Schedule ID' => $schedule_id,
            'Subject' => $assignment_id,
            'Day' => $day,
            'Start time' => $start_time,
            'End time' => $end_time,
            'Room' => $room,
            'Professor' => $professor_id,
        ];

        foreach ($required as $field => $value) {
            if (empty($value)) {
                throw new Exception("$field is required");
            }
        }

        // Verify the schedule belongs to the selected professor
        $sql  = "SELECT ta.professor_id FROM Schedule s 
                JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id 
                WHERE s.schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();

        if (!$schedule) {
            throw new Exception("Schedule not found");
        }

        if ($schedule['professor_id'] != $professor_id) {
            throw new Exception("You can only edit schedules for the selected professor");
        }

        // Check for conflicts (excluding the current schedule)
        $room_conflict = checkRoomConflict($day, $start_time, $end_time, $room, $schedule_id);
        if ($room_conflict) {
            $conflict_details = getConflictDetails($room_conflict);
            throw new Exception("Room conflict: " . $conflict_details);
        }

        $prof_conflict = checkProfessorConflict($day, $start_time, $end_time, $professor_id, $schedule_id);
        if ($prof_conflict) {
            $conflict_details = getConflictDetails($prof_conflict);
            throw new Exception("Professor conflict: " . $conflict_details);
        }

        // Update the schedule
        if (!updateSchedule($schedule_id, $assignment_id, $day, $start_time, $end_time, $room)) {
            throw new Exception("Database error: Failed to update schedule");
        }

        echo json_encode(['status' => 'success', 'message' => 'Schedule updated successfully']);
    } catch (Exception $e) {
        error_log("Update Schedule Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX schedule deletion
if (isset($_POST['ajax_delete_schedule'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $schedule_id  = sanitize($_POST['schedule_id'] ?? '');
        $professor_id = sanitize($_POST['professor_id'] ?? '');

        error_log("Delete request: schedule_id=$schedule_id, professor_id=$professor_id");

        if (empty($schedule_id)) {
            throw new Exception("Schedule ID is required");
        }
        if (empty($professor_id)) {
            throw new Exception("Professor ID is required");
        }

        $sql  = "SELECT ta.professor_id FROM Schedule s 
                JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id 
                WHERE s.schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();

        if (!$schedule) {
            throw new Exception("Schedule not found");
        }

        if ($schedule['professor_id'] != $professor_id) {
            throw new Exception("You can only delete schedules for the selected professor");
        }

        if (!deleteSchedule($schedule_id)) {
            throw new Exception("Database error: Failed to delete schedule");
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Schedule deleted successfully!',
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
        exit;
    }
}

// Helper function to format conflict details
function getConflictDetails($schedule)
{
    global $conn;
    // Get assignment details
    $sql  = "SELECT s.subject_code, s.title, sec.section_code, 
                   CONCAT(p.first_name, ' ', p.last_name) AS professor_name
            FROM Teaching_Assignment ta
            JOIN Subject s ON ta.subject_id = s.subject_id
            JOIN Section sec ON ta.section_id = sec.section_id
            JOIN Professor p ON ta.professor_id = p.professor_id
            WHERE ta.assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule['assignment_id']);
    $stmt->execute();
    $result  = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();

    return sprintf(
        "%s (%s) with %s in %s at %s-%s",
        $details['subject_code'],
        $details['section_code'],
        $details['professor_name'],
        $schedule['room'],
        date('h:i A', strtotime($schedule['start_time'])),
        date('h:i A', strtotime($schedule['end_time'])),
    );
}

// Get the current page from the URL
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Header
include 'template/header.php';

// Navigation (includes sidebar and opens .main-content)
include 'template/navigation.php';

// Main content (inside .main-content from navigation.php)
echo '<div class="container-fluid p-4">';
switch ($page) {
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'colleges':
        include 'pages/colleges.php';
        break;
    case 'programs':
        include 'pages/programs.php';
        break;
    case 'subjects':
        include 'pages/subjects.php';
        break;
    case 'professors':
        include 'pages/professors.php';
        break;
    case 'sections':
        include 'pages/sections.php';
        break;
    case 'students':
        include 'pages/students.php';
        break;
    case 'curriculum':
        include 'pages/curriculum.php';
        break;
    case 'teaching_assignments':
        include 'pages/teaching_assignments.php';
        break;
    case 'enrollments':
        include 'pages/enrollments.php';
        break;
    case 'coe':
        include 'pages/coe.php';
        break;
    case 'sched':
        include 'pages/schedule.php';
        break;
    default:
        include 'pages/dashboard.php';
        break;
}
echo '</div>'; // Close container-fluid
echo '</div>'; // Close .main-content
echo '</div>'; // Close .d-flex

// Footer
include 'template/footer.php';

ob_end_flush(); // Send the buffered output
?>