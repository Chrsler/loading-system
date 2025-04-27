<?php
// pages/sections.php
require_once 'function.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$section_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = intval($_POST['program_id']);
    $section_code = sanitize($_POST['section_code']);
    $academic_year = sanitize($_POST['academic_year']);
    $errors = [];
    if ($program_id <= 0) $errors[] = "Please select a valid program.";
    if (strlen($section_code) > 20) $errors[] = "Section code must be 20 characters or less.";
    if (strlen($academic_year) > 10 || !preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
        $errors[] = "Academic year must be in format YYYY-YYYY (e.g., 2023-2024).";
    }
    if (empty($errors)) {
    
    // Add section
    if ($action === 'add') {
        $sql = "INSERT INTO Section (program_id, section_code, academic_year) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $program_id, $section_code, $academic_year);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Section added successfully!</div>';
            echo '<script>window.location.href = "index.php?page=sections";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    } 
    // Update section
    elseif ($action === 'edit' && $section_id > 0) {
        $sql = "UPDATE Section SET program_id = ?, section_code = ?, academic_year = ? WHERE section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $program_id, $section_code, $academic_year, $section_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Section updated successfully!</div>';
            echo '<script>window.location.href = "index.php?page=sections";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    } else {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) echo "<li>$error</li>";
        echo '</ul></div>';
    }
}

// Delete section
if ($action === 'delete' && $section_id > 0) {
    // Check if there are related records in Student or Teaching_Assignment tables
    $check_student = "SELECT COUNT(*) FROM Student WHERE section_id = ?";
    $check_teaching = "SELECT COUNT(*) FROM Teaching_Assignment WHERE section_id = ?";
    
    $stmt = $conn->prepare($check_student);
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $stmt->bind_result($student_count);
    $stmt->fetch();
    $stmt->close();
    
    $stmt = $conn->prepare($check_teaching);
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $stmt->bind_result($teaching_count);
    $stmt->fetch();
    $stmt->close();
    
    if ($student_count > 0 || $teaching_count > 0) {
        echo '<div class="alert alert-danger">Cannot delete section because it has ' . 
             $student_count . ' student(s) and ' . $teaching_count . ' teaching assignment(s).</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql = "DELETE FROM Section WHERE section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Section deleted successfully!</div>';
            echo '<script>window.location.href = "index.php?page=sections";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Display the appropriate view
if ($action === 'add') {
    $programs = getAllPrograms();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-th-large me-2"></i>Add New Section</h1>
            <a href="index.php?page=sections" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Sections
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Section Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=sections&action=add">
                    <div class="mb-3">
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="section_code" class="form-label">Section Code</label>
                            <input type="text" class="form-control" id="section_code" name="section_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                   placeholder="e.g., 2023-2024" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Section
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $section_id > 0) {
    $section = getRecordById('Section', 'section_id', $section_id);
    $programs = getAllPrograms();
    
    if ($section) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Section</h1>
                <a href="index.php?page=sections" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Sections
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Section Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=sections&action=edit&id=<?php echo $section_id; ?>">
                        <div class="mb-3">
                            <label for="program_id" class="form-label">Program</label>
                            <select class="form-select" id="program_id" name="program_id" required>
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['program_id']; ?>" 
                                        <?php echo ($section['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="section_code" class="form-label">Section Code</label>
                                <input type="text" class="form-control" id="section_code" name="section_code" 
                                       value="<?php echo htmlspecialchars($section['section_code']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                       value="<?php echo htmlspecialchars($section['academic_year']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Section
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Section not found.</div>';
        echo '<a href="index.php?page=sections" class="btn btn-primary">Back to Sections</a>';
    }
} elseif ($action === 'view' && $section_id > 0) {
    $section = getRecordById('Section', 'section_id', $section_id);
    
    if ($section) {
        // Get program information
        $program = getRecordById('Program', 'program_id', $section['program_id']);
        
        // Get students in this section
        $sql = "SELECT student_id, first_name, last_name, status 
                FROM Student 
                WHERE section_id = ? 
                ORDER BY last_name, first_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        
        // Get teaching assignments for this section
        $sql = "SELECT ta.*, p.first_name, p.last_name, sub.subject_code, sub.title AS subject_title 
                FROM Teaching_Assignment ta 
                JOIN Professor p ON ta.professor_id = p.professor_id 
                JOIN Subject sub ON ta.subject_id = sub.subject_id 
                WHERE ta.section_id = ? 
                ORDER BY sub.subject_code";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $teaching_assignments = [];
        while ($row = $result->fetch_assoc()) {
            $teaching_assignments[] = $row;
        }
        $stmt->close();
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-th-large me-2"></i><?php echo htmlspecialchars($section['section_code']); ?></h1>
                <div>
                    <a href="index.php?page=sections&action=edit&id=<?php echo $section_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=sections" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Sections
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Section Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Section Code:</strong> <?php echo htmlspecialchars($section['section_code']); ?></p>
                            <p><strong>Program:</strong> <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?></p>
                            <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($section['academic_year']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Section ID:</strong> <?php echo $section_id; ?></p>
                            <p><strong>Total Students:</strong> <?php echo count($students); ?></p>
                            <p><strong>Total Teaching Assignments:</strong> <?php echo count($teaching_assignments); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($students)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Students in <?php echo htmlspecialchars($section['section_code']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
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
                                    <td><?php echo htmlspecialchars($student['status']); ?></td>
                                    <td>
                                        <a href="index.php?page=students&action=view&id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Student">
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
            
            <?php if (!empty($teaching_assignments)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Teaching Assignments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Professor</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teaching_assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                    <td>
                                        <a href="index.php?page=teaching_assignments&action=view&id=<?php echo $assignment['assignment_id']; ?>" 
                                           class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Assignment">
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
        echo '<div class="alert alert-danger">Section not found.</div>';
        echo '<a href="index.php?page=sections" class="btn btn-primary">Back to Sections</a>';
    }
} else {
    // Default list view
    $sections = getAllSections();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-th-large me-2"></i>Sections</h1>
            <a href="index.php?page=sections&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Section
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sections List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Section ID</th>
                                <th>Program</th>
                                <th>Section Code</th>
                                <th>Academic Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sections as $section): ?>
                            <tr>
                                <td><?php echo $section['section_id']; ?></td>
                                <td><?php echo htmlspecialchars($section['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                <td>
                                    <a href="index.php?page=sections&action=view&id=<?php echo $section['section_id']; ?>" 
                                       class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=sections&action=edit&id=<?php echo $section['section_id']; ?>" 
                                       class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=sections&action=delete&id=<?php echo $section['section_id']; ?>" 
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