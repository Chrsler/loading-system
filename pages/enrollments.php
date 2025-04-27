<?php
// pages/enrollments.php
require_once 'function.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$enrollment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $professor_id = intval($_POST['professor_id']);
    $grade = sanitize($_POST['grade']) ?: null; // Allow null grade
    
    // Add enrollment
    if ($action === 'add') {
        $sql = "INSERT INTO Enrollment (student_id, subject_id, professor_id, grade) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $student_id, $subject_id, $professor_id, $grade);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Enrollment added successfully!</div>';
            echo '<script>window.location.href = "index.php?page=enrollments";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    } 
    // Update enrollment
    elseif ($action === 'edit' && $enrollment_id > 0) {
        $sql = "UPDATE Enrollment SET student_id = ?, subject_id = ?, professor_id = ?, grade = ? WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisi", $student_id, $subject_id, $professor_id, $grade, $enrollment_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Enrollment updated successfully!</div>';
            echo '<script>window.location.href = "index.php?page=enrollments";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Delete enrollment
if ($action === 'delete' && $enrollment_id > 0) {
    $sql = "DELETE FROM Enrollment WHERE enrollment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $enrollment_id);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success">Enrollment deleted successfully!</div>';
        echo '<script>window.location.href = "index.php?page=enrollments";</script>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Display the appropriate view
if ($action === 'add') {
    $students = getAllStudents();
    $subjects = getAllSubjects();
    $professors = getAllProfessors();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-clipboard-check me-2"></i>Add New Enrollment</h1>
            <a href="index.php?page=enrollments" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Enrollments
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Enrollment Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=enrollments&action=add">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['program_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="professor_id" class="form-label">Professor</label>
                        <select class="form-select" id="professor_id" name="professor_id" required>
                            <option value="">Select Professor</option>
                            <?php foreach ($professors as $professor): ?>
                            <option value="<?php echo $professor['professor_id']; ?>">
                                <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grade" class="form-label">Grade (optional)</label>
                        <input type="text" class="form-control" id="grade" name="grade" maxlength="10" placeholder="e.g., A, 85">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Enrollment
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $enrollment_id > 0) {
    $enrollment = getRecordById('Enrollment', 'enrollment_id', $enrollment_id);
    $students = getAllStudents();
    $subjects = getAllSubjects();
    $professors = getAllProfessors();
    
    if ($enrollment) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Enrollment</h1>
                <a href="index.php?page=enrollments" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Enrollments
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Enrollment Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=enrollments&action=edit&id=<?php echo $enrollment_id; ?>">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>" 
                                        <?php echo ($enrollment['student_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['program_name'] . ')'); ?>
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
                                        <?php echo ($enrollment['subject_id'] == $subject['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="professor_id" class="form-label">Professor</label>
                            <select class="form-select" id="professor_id" name="professor_id" required>
                                <option value="">Select Professor</option>
                                <?php foreach ($professors as $professor): ?>
                                <option value="<?php echo $professor['professor_id']; ?>" 
                                        <?php echo ($enrollment['professor_id'] == $professor['professor_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade (optional)</label>
                            <input type="text" class="form-control" id="grade" name="grade" maxlength="10" 
                                   value="<?php echo htmlspecialchars($enrollment['grade'] ?? ''); ?>" placeholder="e.g., A, 85">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Enrollment
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Enrollment not found.</div>';
        echo '<a href="index.php?page=enrollments" class="btn btn-primary">Back to Enrollments</a>';
    }
} elseif ($action === 'view' && $enrollment_id > 0) {
    $enrollment = getRecordById('Enrollment', 'enrollment_id', $enrollment_id);
    
    if ($enrollment) {
        // Get related information
        $student = getRecordById('Student', 'student_id', $enrollment['student_id']);
        $subject = getRecordById('Subject', 'subject_id', $enrollment['subject_id']);
        $professor = getRecordById('Professor', 'professor_id', $enrollment['professor_id']);
        $program = getRecordById('Program', 'program_id', $student['program_id']);
        $section = getRecordById('Section', 'section_id', $student['section_id']);
        
        // Get curriculum info if available
        $sql = "SELECT * FROM Curriculum WHERE program_id = ? AND subject_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $student['program_id'], $enrollment['subject_id']);
        $stmt->execute();
        $curriculum = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-clipboard-check me-2"></i>Enrollment Details</h1>
                <div>
                    <a href="index.php?page=enrollments&action=edit&id=<?php echo $enrollment_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=enrollments" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Enrollments
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Enrollment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?></p>
                            <p><strong>Professor:</strong> <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Program:</strong> <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?></p>
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($section['section_code'] . ' (' . $section['academic_year'] . ')'); ?></p>
                            <p><strong>Grade:</strong> <?php echo htmlspecialchars($enrollment['grade'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <?php if ($curriculum): ?>
                    <div class="mt-3">
                        <p><strong>Curriculum Info:</strong> Year <?php echo $curriculum['year_level']; ?>, Semester <?php echo $curriculum['semester']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Enrollment not found.</div>';
        echo '<a href="index.php?page=enrollments" class="btn btn-primary">Back to Enrollments</a>';
    }
} else {
    // Default list view
    $enrollments = getAllEnrollments();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-clipboard-check me-2"></i>Enrollments</h1>
            <a href="index.php?page=enrollments&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Enrollment
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Enrollments List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Enrollment ID</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Professor</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo $enrollment['enrollment_id']; ?></td>
                                <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['subject_code'] . ' - ' . $enrollment['title']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['professor_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['grade'] ?: 'N/A'); ?></td>
                                <td>
                                    <a href="index.php?page=enrollments&action=view&id=<?php echo $enrollment['enrollment_id']; ?>" 
                                       class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=enrollments&action=edit&id=<?php echo $enrollment['enrollment_id']; ?>" 
                                       class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=enrollments&action=delete&id=<?php echo $enrollment['enrollment_id']; ?>" 
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