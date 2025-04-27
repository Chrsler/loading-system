<?php
// pages/students.php
require_once 'function.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $program_id = intval($_POST['program_id']);
    $section_id = intval($_POST['section_id']);
    $status = sanitize($_POST['status']);
    
    // Add student
    if ($action === 'add') {
        $sql = "INSERT INTO Student (first_name, last_name, program_id, section_id, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiis", $first_name, $last_name, $program_id, $section_id, $status);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Student added successfully!</div>';
            echo '<script>window.location.href = "index.php?page=students";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    } 
    // Update student
    elseif ($action === 'edit' && $student_id > 0) {
        $sql = "UPDATE Student SET first_name = ?, last_name = ?, program_id = ?, section_id = ?, status = ? WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiisi", $first_name, $last_name, $program_id, $section_id, $status, $student_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Student updated successfully!</div>';
            echo '<script>window.location.href = "index.php?page=students";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Delete student
if ($action === 'delete' && $student_id > 0) {
    // Check if there are related records in Enrollment table
    $check_enrollment = "SELECT COUNT(*) FROM Enrollment WHERE student_id = ?";
    
    $stmt = $conn->prepare($check_enrollment);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($enrollment_count);
    $stmt->fetch();
    $stmt->close();
    
    if ($enrollment_count > 0) {
        echo '<div class="alert alert-danger">Cannot delete student because they have ' . 
             $enrollment_count . ' enrollment(s).</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql = "DELETE FROM Student WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Student deleted successfully!</div>';
            echo '<script>window.location.href = "index.php?page=students";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Display the appropriate view
if ($action === 'add') {
    $programs = getAllPrograms();
    $sections = getAllSections();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user-graduate me-2"></i>Add New Student</h1>
            <a href="index.php?page=students" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Student Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=students&action=add">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="program_id" class="form-label">Program</label>
                            <select class="form-select" id="program_id" name="program_id" required>
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['program_id']; ?>">
                                    <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
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
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Regular">Regular</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Student
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $student_id > 0) {
    $student = getRecordById('Student', 'student_id', $student_id);
    $programs = getAllPrograms();
    $sections = getAllSections();
    
    if ($student) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Student</h1>
                <a href="index.php?page=students" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Student Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=students&action=edit&id=<?php echo $student_id; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="program_id" class="form-label">Program</label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">Select Program</option>
                                    <?php foreach ($programs as $program): ?>
                                    <option value="<?php echo $program['program_id']; ?>" 
                                            <?php echo ($student['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="section_id" class="form-label">Section</label>
                                <select class="form-select" id="section_id" name="section_id" required>
                                    <option value="">Select Section</option>
                                    <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section['section_id']; ?>" 
                                            <?php echo ($student['section_id'] == $section['section_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($section['section_code'] . ' (' . $section['program_name'] . ', ' . $section['academic_year'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Regular" <?php echo ($student['STATUS'] == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="Irregular" <?php echo ($student['STATUS'] == 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Student not found.</div>';
        echo '<a href="index.php?page=students" class="btn btn-primary">Back to Students</a>';
    }
} elseif ($action === 'view' && $student_id > 0) {
    $student = getRecordById('Student', 'student_id', $student_id);
    
    
    if ($student) {
        // Get program and section information
        $program = getRecordById('Program', 'program_id', $student['program_id']);
        $section = getRecordById('Section', 'section_id', $student['section_id']);
        
        // Get enrollments for this student
        $sql = "SELECT e.*, sub.subject_code, sub.title AS subject_title, p.first_name, p.last_name 
                FROM Enrollment e 
                JOIN Subject sub ON e.subject_id = sub.subject_id 
                JOIN Professor p ON e.professor_id = p.professor_id 
                WHERE e.student_id = ? 
                ORDER BY sub.subject_code";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
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
                <h1><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <div>
                    <a href="index.php?page=students&action=edit&id=<?php echo $student_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=students" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Students
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>First Name:</strong> <?php echo htmlspecialchars($student['first_name']); ?></p>
                            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($student['last_name']); ?></p>
                            <p><strong>Program:</strong> <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($section['section_code'] . ' (' . $section['academic_year'] . ')'); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($student['STATUS']); ?></p>
                            <p><strong>Total Enrollments:</strong> <?php echo count($enrollments); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($enrollments)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Enrollments for <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Professor</th>
                                    <th>Grade</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($enrollment['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['subject_title']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
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
        echo '<div class="alert alert-danger">Student not found.</div>';
        echo '<a href="index.php?page=students" class="btn btn-primary">Back to Students</a>';
    }
} else {
    // Default list view
    $students = getAllStudents();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user-graduate me-2"></i>Students</h1>
            <a href="index.php?page=students&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Student
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Students List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Program</th>
                                <th>Section</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['status']); ?></td>
                                <td>
                                    <a href="index.php?page=students&action=view&id=<?php echo $student['student_id']; ?>" 
                                       class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=students&action=edit&id=<?php echo $student['student_id']; ?>" 
                                       class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=students&action=delete&id=<?php echo $student['student_id']; ?>" 
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