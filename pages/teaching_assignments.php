<?php
// pages/teaching_assignments.php
require_once 'function.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Variable to store messages
$message = '';
$message_type = '';

// Handle schedule-related actions
if ($action === 'add_schedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = sanitize($_POST['day']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $room = sanitize($_POST['room']);

    // Fetch the assignment to get the professor_id for conflict checking
    $assignment = getRecordById('Teaching_Assignment', 'assignment_id', $assignment_id);
    $professor = getRecordById('Professor', 'professor_id', $assignment['professor_id']);

    // Check for room conflicts
    $room_conflict = checkRoomConflict($day, $start_time, $end_time, $room);
    if ($room_conflict) {
        $message = "Room conflict detected";
        $message_type = "danger";
    } else {
        // Check for professor conflicts
        $professor_conflict = checkProfessorConflict($day, $start_time, $end_time, $professor['professor_id']);
        if ($professor_conflict) {
            $message = "Professor schedule conflict detected";
            $message_type = "danger";
        } else {
            // Add the schedule
            if (addSchedule($assignment_id, $day, $start_time, $end_time, $room)) {
                $message = "Schedule added successfully";
                $message_type = "success";
            } else {
                $message = "Failed to add schedule";
                $message_type = "danger";
            }
        }
    }

    // Redirect to the view page
    header("Location: index.php?page=teaching_assignments&action=view&id=$assignment_id" . ($message ? "&message=" . urlencode($message) . "&message_type=$message_type" : ""));
    exit;
}

if ($action === 'delete_schedule') {
    $schedule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (deleteSchedule($schedule_id)) {
        $message = "Schedule deleted successfully";
        $message_type = "success";
    } else {
        $message = "Failed to delete schedule";
        $message_type = "danger";
    }

    // Redirect to the view page
    header("Location: index.php?page=teaching_assignments&action=view&id=$assignment_id" . ($message ? "&message=" . urlencode($message) . "&message_type=$message_type" : ""));
    exit;
}

if ($action === 'edit_schedule') {
    $schedule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $schedule = getScheduleById($schedule_id);

    if (!$schedule) {
        $message = "Schedule not found";
        $message_type = "danger";
        header("Location: index.php?page=teaching_assignments&action=view&id=$assignment_id&message=" . urlencode($message) . "&message_type=$message_type");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $day = sanitize($_POST['day']);
        $start_time = sanitize($_POST['start_time']);
        $end_time = sanitize($_POST['end_time']);
        $room = sanitize($_POST['room']);

        // Fetch the assignment to get the professor_id for conflict checking
        $assignment = getRecordById('Teaching_Assignment', 'assignment_id', $assignment_id);
        $professor = getRecordById('Professor', 'professor_id', $assignment['professor_id']);

        // Check for room conflicts (exclude current schedule)
        $room_conflict = checkRoomConflict($day, $start_time, $end_time, $room, $schedule_id);
        if ($room_conflict) {
            $message = "Room conflict detected";
            $message_type = "danger";
        } else {
            // Check for professor conflicts (exclude current schedule)
            $professor_conflict = checkProfessorConflict($day, $start_time, $end_time, $professor['professor_id'], $schedule_id);
            if ($professor_conflict) {
                $message = "Professor schedule conflict detected";
                $message_type = "danger";
            } else {
                // Update the schedule
                if (updateSchedule($schedule_id, $assignment_id, $day, $start_time, $end_time, $room)) {
                    $message = "Schedule updated successfully";
                    $message_type = "success";
                    header("Location: index.php?page=teaching_assignments&action=view&id=$assignment_id&message=" . urlencode($message) . "&message_type=$message_type");
                    exit;
                } else {
                    $message = "Failed to update schedule";
                    $message_type = "danger";
                }
            }
        }
    }

    // If we reach here, either the form is being displayed or there was an error
    ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit me-2"></i>Edit Schedule</h1>
        <a href="index.php?page=teaching_assignments&action=view&id=<?php echo $assignment_id; ?>"
            class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Edit Schedule</h5>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST"
                action="index.php?page=teaching_assignments&action=edit_schedule&id=<?php echo $schedule_id; ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label for="edit_day">Day</label>
                        <select name="day" id="edit_day" class="form-control" required>
                            <option value="">Select Day</option>
                            <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                foreach ($days as $day) {
                                    $selected = $schedule['day'] === $day ? 'selected' : '';
                                    echo "<option value='$day' $selected>$day</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_start_time">Start Time</label>
                        <select name="start_time" id="edit_start_time" class="form-control" required>
                            <option value="">Select Start Time</option>
                            <?php
                                $times = [
                                    '07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
                                    '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00',
                                    '19:00:00', '20:00:00'
                                ];
                                foreach ($times as $time) {
                                    $selected = $schedule['start_time'] === $time ? 'selected' : '';
                                    $display_time = date('h:i A', strtotime($time));
                                    echo "<option value='$time' $selected>$display_time</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_end_time">End Time</label>
                        <select name="end_time" id="edit_end_time" class="form-control" required>
                            <option value="">Select End Time</option>
                            <?php
                                $end_times = [
                                    '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00',
                                    '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00', '19:00:00',
                                    '20:00:00', '21:00:00'
                                ];
                                foreach ($end_times as $time) {
                                    $selected = $schedule['end_time'] === $time ? 'selected' : '';
                                    $display_time = date('h:i A', strtotime($time));
                                    echo "<option value='$time' $selected>$display_time</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_room">Room</label>
                        <select name="room" id="edit_room" class="form-control" required>
                            <option value="">Select Room</option>
                            <?php
                                $rooms = array_merge(
                                    ['Lab 1', 'Lab 2', 'Lab 3'],
                                    array_map(function($i) { return "20$i"; }, range(1, 10)),
                                    array_map(function($i) { return "30$i"; }, range(1, 10)),
                                    array_map(function($i) { return "40$i"; }, range(1, 10))
                                );
                                foreach ($rooms as $room) {
                                    $selected = $schedule['room'] === $room ? 'selected' : '';
                                    echo "<option value='$room' $selected>$room</option>";
                                }
                                ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Update Schedule</button>
            </form>
        </div>
    </div>
</div>
<?php
    // No need for exit here since we're using output buffering
}

// Handle form submissions for teaching assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $professor_id = intval($_POST['professor_id']);
    $subject_ids = isset($_POST['subject_id']) ? $_POST['subject_id'] : []; // Array of subject IDs
    $section_id = intval($_POST['section_id']);
    
    if ($action === 'add') {
        $success = true;
        foreach ($subject_ids as $subject_id) {
            $sql = "INSERT INTO Teaching_Assignment (professor_id, subject_id, section_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $professor_id, $subject_id, $section_id);
            
            if (!$stmt->execute()) {
                $success = false;
                $message = "Error: " . $conn->error;
                $message_type = "danger";
                break;
            }
            $stmt->close();
        }
        
        if ($success) {
            $message = "Teaching assignments added successfully!";
            $message_type = "success";
            header("Location: index.php?page=teaching_assignments&message=" . urlencode($message) . "&message_type=$message_type");
            exit;
        }
    } elseif ($action === 'edit' && $assignment_id > 0) {
        $subject_id = intval($_POST['subject_id']); // Assuming single subject for simplicity
        $sql = "UPDATE Teaching_Assignment SET professor_id = ?, subject_id = ?, section_id = ? WHERE assignment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $professor_id, $subject_id, $section_id, $assignment_id);
        
        if ($stmt->execute()) {
            $message = "Teaching assignment updated successfully!";
            $message_type = "success";
            header("Location: index.php?page=teaching_assignments&message=" . urlencode($message) . "&message_type=$message_type");
            exit;
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

if ($action === 'delete' && $assignment_id > 0) {
    $sql = "DELETE FROM Teaching_Assignment WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    if ($stmt->execute()) {
        $message = "Teaching assignment deleted successfully!";
        $message_type = "success";
        header("Location: index.php?page=teaching_assignments&message=" . urlencode($message) . "&message_type=$message_type");
        exit;
    } else {
        $message = "Error: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

if ($action === 'add') {
    // Check if professor_id is passed in the URL
    if (isset($_GET['professor_id'])) {
        $professor_id = $_GET['professor_id'];
    } else {
        // Handle the case where professor_id is not provided
        die("Professor ID is missing.");
    }

    $professors = getAllProfessors();
    $subjects = getAllSubjects();
    $sections = getAllSections();
    ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tasks me-2"></i>Add New Teaching Assignment</h1>
        <a href="index.php?page=professors&action=view&id=<?php echo $professor_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Teaching Assignment Information</h5>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post" action="index.php?page=teaching_assignments&action=add" id="assignmentForm">
                <div class="mb-3">
                    <label for="professor_id" class="form-label">Professor</label>
                    <select class="form-select" id="professor_id" name="professor_id" required>
                        <option value="">Select Professor</option>
                        <?php foreach ($professors as $professor): ?>
                        <option value="<?php echo $professor['professor_id']; ?>"
                            <?php echo ($professor['professor_id'] == $professor_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="section_id" class="form-label">Section</label>
                    <select class="form-select" id="section_id" name="section_id" required>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['section_id']; ?>">
                            <?php echo htmlspecialchars($section['section_code'] . ' (' . $section['program_name'] . ', ' . $section['academic_year'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Search Subjects:</label>
                    <input type="text" class="form-control" id="subjectSearch" placeholder="Search subjects...">
                    <div id="subjectSuggestions" style="display: none;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subjects (Maximum 10):</label>
                    <div class="form-check subject-list" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($subjects as $subject): ?>
                        <div class="subject-item"
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                            <div class="subject-checkbox">
                                <input class="form-check-input subject-checkbox" type="checkbox" name="subject_id[]"
                                    value="<?php echo $subject['subject_id']; ?>"
                                    id="subject_<?php echo $subject['subject_id']; ?>" style="margin: 3px;">
                                <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text text-muted">Selected: <span id="selectedCount">0</span>/10</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Teaching Assignment
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.subject-list {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.subject-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
const subjects = <?php echo json_encode($subjects); ?>;

function setupSearch(inputId, suggestionsId, itemClass, actionsClass) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);

    if (!input || !suggestions) return;

    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const items = document.getElementsByClassName(itemClass);

        Array.from(items).forEach(item => {
            const label = item.getElementsByTagName('label')[0];
            const text = label.textContent.toLowerCase();
            item.style.display = searchTerm.length === 0 || text.includes(searchTerm) ? 'flex' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Setup search
    if (document.getElementById('subjectSearch')) {
        setupSearch('subjectSearch', 'subjectSuggestions', 'subject-item', 'subject-actions');
    }

    // Checkbox limit
    const checkboxes = document.querySelectorAll('.subject-checkbox');
    const maxSelections = 10;
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const checkedCount = document.querySelectorAll('.subject-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        checkboxes.forEach(checkbox => {
            if (!checkbox.checked && checkedCount >= maxSelections) {
                checkbox.disabled = true;
            } else {
                checkbox.disabled = false;
            }
        });
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCount);
    });

    // Initial count update
    updateCount();
});
</script>
<?php
} elseif ($action === 'edit' && $assignment_id > 0) {
    $assignment = getRecordById('Teaching_Assignment', 'assignment_id', $assignment_id);
    $professors = getAllProfessors();
    $subjects = getAllSubjects();
    $sections = getAllSections();
    
    if ($assignment) {
        ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit me-2"></i>Edit Teaching Assignment</h1>
        <a href="index.php?page=teaching_assignments" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Teaching Assignments
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Teaching Assignment Information</h5>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post"
                action="index.php?page=teaching_assignments&action=edit&id=<?php echo $assignment_id; ?>">
                <div class="mb-3">
                    <label for="professor_id" class="form-label">Professor</label>
                    <select class="form-select" id="professor_id" name="professor_id" required>
                        <option value="">Select Professor</option>
                        <?php foreach ($professors as $professor): ?>
                        <option value="<?php echo $professor['professor_id']; ?>"
                            <?php echo ($assignment['professor_id'] == $professor['professor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="subject_id" class="form-label">Subject</label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>"
                            <?php echo ($assignment['subject_id'] == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="section_id" class="form-label">Section</label>
                    <select class="form-select" id="section_id" name="section_id" required>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['section_id']; ?>"
                            <?php echo ($assignment['section_id'] == $section['section_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($section['section_code'] . ' (' . $section['program_name'] . ', ' . $section['academic_year'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Teaching Assignment
                </button>
            </form>
        </div>
    </div>
</div>
<?php
    } else {
        echo '<div class="alert alert-danger">Teaching assignment not found.</div>';
        echo '<a href="index.php?page=teaching_assignments" class="btn btn-primary">Back to Teaching Assignments</a>';
    }
} elseif ($action === 'view' && $assignment_id > 0) {
    $assignment = getRecordById('Teaching_Assignment', 'assignment_id', $assignment_id);
    
    if ($assignment) {
        $professor = getRecordById('Professor', 'professor_id', $assignment['professor_id']);
        $subject = getRecordById('Subject', 'subject_id', $assignment['subject_id']);
        $section = getRecordById('Section', 'section_id', $assignment['section_id']);
        $program = getRecordById('Program', 'program_id', $section['program_id']);
        
        $sql = "SELECT e.*, s.first_name, s.last_name 
                FROM Enrollment e 
                JOIN Student s ON e.student_id = s.student_id 
                WHERE e.subject_id = ? AND s.section_id = ? 
                ORDER BY s.last_name, s.first_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $assignment['subject_id'], $assignment['section_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollments = [];
        while ($row = $result->fetch_assoc()) {
            $enrollments[] = $row;
        }
        $stmt->close();
        ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tasks me-2"></i>Teaching Assignment</h1>
        <div>
            <a href="index.php?page=teaching_assignments&action=edit&id=<?php echo $assignment_id; ?>"
                class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="index.php?page=professors&action=view&id=<?php echo $assignment['professor_id']; ?>"
                class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Assignment Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Professor:</strong>
                        <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?></p>
                    <p><strong>Subject:</strong>
                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?></p>
                    <p><strong>Section:</strong> <?php echo htmlspecialchars($section['section_code']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Program:</strong>
                        <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?></p>
                    <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($section['academic_year']); ?></p>
                    <p><strong>Total Enrolled Students:</strong> <?php echo count($enrollments); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- SCHEDULE -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Schedule</h5>
        </div>
        <div class="card-body">
            <!-- Schedule Form -->
            <div class="row mt-4">
                <div class="col-12">
                    <h6>Add Schedule</h6>
                    <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_GET['message_type']); ?>">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST"
                        action="index.php?page=teaching_assignments&action=add_schedule&id=<?php echo $assignment['assignment_id']; ?>">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="add_day">Day</label>
                                <select name="day" id="add_day" class="form-control" required>
                                    <option value="">Select Day</option>
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                    foreach ($days as $day) {
                                        echo "<option value='$day'>$day</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="add_start_time">Start Time</label>
                                <select name="start_time" id="add_start_time" class="form-control" required>
                                    <option value="">Select Start Time</option>
                                    <?php
                                    $times = [
                                        '07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
                                        '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00',
                                        '19:00:00', '20:00:00', '21:00:00'
                                    ];
                                    foreach ($times as $time) {
                                        $display_time = date('h:i A', strtotime($time));
                                        echo "<option value='$time'>$display_time</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="add_end_time">End Time</label>
                                <select name="end_time" id="add_end_time" class="form-control" required>
                                    <option value="">Select End Time</option>
                                    <?php
                                    $end_times = [
                                        '07:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', 
                                        '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00', 
                                        '19:00:00', '20:00:00', '21:00:00'
                                    ];
                                    foreach ($end_times as $time) {
                                        $display_time = date('h:i A', strtotime($time));
                                        echo "<option value='$time'>$display_time</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="add_room">Room</label>
                                <select name="room" id="add_room" class="form-control" required>
                                    <option value="">Select Room</option>
                                    <?php
                                    $rooms = array_merge(
                                        ['Lab 1', 'Lab 2', 'Lab 3'],
                                        array_map(function($i) { return "20$i"; }, range(1, 10)),
                                        array_map(function($i) { return "30$i"; }, range(1, 10)),
                                        array_map(function($i) { return "40$i"; }, range(1, 10))
                                    );
                                    foreach ($rooms as $room) {
                                        echo "<option value='$room'>$room</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Add Schedule</button>
                    </form>
                </div>
            </div>

            <!-- Timetable -->
            <div class="row mt-4">
                <div class="col-12">
                    <h6>Timetable (All Subjects for
                        <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>)</h6>
                    <?php
                    // Fetch all teaching assignments for this professor
                    $all_assignments = getTeachingAssignmentsByProfessorId($assignment['professor_id']);
                    $schedules = [];
                    foreach ($all_assignments as $assign) {
                        $assignment_schedules = getSchedulesByAssignmentId($assign['assignment_id']);
                        $schedules = array_merge($schedules, $assignment_schedules);
                    }

                    if (empty($schedules)) {
                        echo '<p>No schedules found for this professor.</p>';
                    } else {
                        echo '<p>Found ' . count($schedules) . ' schedules across all subjects:</p>';
                        echo '<pre>';
                        print_r($schedules);
                        echo '</pre>';
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <?php foreach ($days as $day): ?>
                                    <th><?php echo $day; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $time_slots = [
                                    '07:00:00-08:00:00' => '7am',
                                    '08:00:00-09:00:00' => '8am',
                                    '09:00:00-10:00:00' => '9am',
                                    '10:00:00-11:00:00' => '10am',
                                    '11:00:00-12:00:00' => '11am',
                                    '12:00:00-13:00:00' => '12pm',
                                    '13:00:00-14:00:00' => '1pm',
                                    '14:00:00-15:00:00' => '2pm',
                                    '15:00:00-16:00:00' => '3pm',
                                    '16:00:00-17:00:00' => '4pm',
                                    '17:00:00-18:00:00' => '5pm',
                                    '18:00:00-19:00:00' => '6pm',
                                    '19:00:00-20:00:00' => '7pm',
                                    '20:00:00-21:00:00' => '8pm',
                                    '21:00:00-22:00:00' => '9pm'
                                ];
                                $colors = ['#FF9999', '#99FF99', '#9999FF', '#FFFF99', '#FF99FF'];
                                $color_index = 0;

                                foreach ($time_slots as $time_range => $display_time): ?>
                                <tr>
                                    <td><?php echo $display_time; ?></td>
                                    <?php foreach ($days as $day): ?>
                                    <td>
                                        <?php
                                        list($slot_start, $slot_end) = explode('-', $time_range);
                                        $slot_start_ts = strtotime($slot_start);
                                        $slot_end_ts = strtotime($slot_end);

                                        // Collect all overlapping schedules for this slot
                                        $overlapping_schedules = [];
                                        foreach ($schedules as $schedule) {
                                            $sched_start_ts = strtotime($schedule['start_time']);
                                            $sched_end_ts = strtotime($schedule['end_time']);
                                            if ($schedule['DAY'] === $day && 
                                                $sched_start_ts < $slot_end_ts && 
                                                $sched_end_ts > $slot_start_ts) {
                                                $overlapping_schedules[] = $schedule;
                                            }
                                        }

                                        if (!empty($overlapping_schedules)) {
                                            foreach ($overlapping_schedules as $schedule) {
                                                $color = $colors[$color_index % count($colors)];
                                                $color_index++;
                                                echo "<div style='background-color: $color; padding: 5px; margin-bottom: 5px;'>";
                                                echo htmlspecialchars($schedule['subject_code']) . "<br>";
                                                echo "Section: " . htmlspecialchars($schedule['section_code']) . "<br>";
                                                echo "Program: " . htmlspecialchars($schedule['program_code']) . "<br>";
                                                echo "Room: " . htmlspecialchars($schedule['room']) . "<br>";
                                                echo "<a href='index.php?page=teaching_assignments&action=edit_schedule&id=" . $schedule['schedule_id'] . "' class='btn btn-sm btn-warning'>Edit</a> ";
                                                echo "<a href='index.php?page=teaching_assignments&action=delete_schedule&id=" . $schedule['schedule_id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                                                echo "</div>";
                                            }
                                        } else {
                                            echo "-";
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END OF SCHEDULE -->

    <?php if (!empty($enrollments)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Enrolled Students</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($enrollment['grade'] ?: 'N/A'); ?></td>
                            <td>
                                <a href="index.php?page=enrollments&action=view&id=<?php echo $enrollment['enrollment_id']; ?>"
                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Enrollment">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
    } else {
        echo '<div class="alert alert-danger">Teaching assignment not found.</div>';
        echo '<a href="index.php?page=teaching_assignments" class="btn btn-primary">Back to Teaching Assignments</a>';
    }
} else {
    $assignments = getAllTeachingAssignments();
    ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-nowrap"><i class="fas fa-tasks me-2"></i>Teaching Assignments</h1>
        <div class="d-flex justify-content-end gap-2">
            <!-- <a href="index.php?page=teaching_assignments&action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Teaching Assignment
                </a> -->
            <a href="index.php?page=professors" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Teaching Assignments List</h5>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_GET['message_type']); ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover data-table">
                    <thead>
                        <tr>
                            <th>Assignment ID</th>
                            <th>Professor</th>
                            <th>Subject</th>
                            <th>Section</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo $assignment['assignment_id']; ?></td>
                            <td><?php echo htmlspecialchars($assignment['professor_name']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['section_code']); ?></td>
                            <td>
                                <a href="index.php?page=teaching_assignments&action=edit&id=<?php echo $assignment['assignment_id']; ?>"
                                    class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=teaching_assignments&action=delete&id=<?php echo $assignment['assignment_id']; ?>"
                                    class="btn btn-sm btn-danger delete-record" data-bs-toggle="tooltip" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
}
?>