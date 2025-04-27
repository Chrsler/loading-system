
<?php
// pages/programs.php
// Ensure functions.php is included (typically done in index.php, but verify)
// require_once 'functions.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$program_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = sanitize($_POST['program_name']);
    $program_code = sanitize($_POST['program_code']);
    $college_id = intval($_POST['college_id']);
    
    // Add program
    if ($action === 'add') {
        $sql = "INSERT INTO Program (program_name, program_code, college_id) 
                VALUES ('$program_name', '$program_code', $college_id)";
        
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Program added successfully!</div>';
            // Redirect to list after success
            echo '<script>window.location.href = "index.php?page=programs";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    } 
    // Update program
    elseif ($action === 'edit' && $program_id > 0) {
        $sql = "UPDATE Program SET 
                program_name = '$program_name', 
                program_code = '$program_code', 
                college_id = $college_id 
                WHERE program_id = $program_id";
        
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Program updated successfully!</div>';
            // Redirect to list after success
            echo '<script>window.location.href = "index.php?page=programs";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}

// Delete program
if ($action === 'delete' && $program_id > 0) {
    // Check if there are related records in other tables
    $check_section = "SELECT * FROM Section WHERE program_id = $program_id";
    $check_student = "SELECT * FROM Student WHERE program_id = $program_id";
    $check_curriculum = "SELECT * FROM Curriculum WHERE program_id = $program_id";
    
    $has_section = $conn->query($check_section)->num_rows > 0;
    $has_student = $conn->query($check_student)->num_rows > 0;
    $has_curriculum = $conn->query($check_curriculum)->num_rows > 0;
    
    if ($has_section || $has_student || $has_curriculum) {
        echo '<div class="alert alert-danger">Cannot delete program because it is referenced by other records.</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql = "DELETE FROM Program WHERE program_id = $program_id";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Program deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        // Redirect to list after delete
        echo '<script>window.location.href = "index.php?page=programs";</script>';
    }
}

// Display the appropriate view
if ($action === 'add') {
    // Get all colleges for dropdown
    $colleges = getAllColleges();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-graduation-cap me-2"></i>Add New Program</h1>
            <a href="index.php?page=programs" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Programs
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Program Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=programs&action=add">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="program_name" class="form-label">Program Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="program_code" class="form-label">Program Code</label>
                            <input type="text" class="form-control" id="program_code" name="program_code" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="college_id" class="form-label">College</label>
                        <select class="form-select" id="college_id" name="college_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo $college['college_id']; ?>">
                                <?php echo htmlspecialchars($college['college_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Program
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $program_id > 0) {
    // Get the program to edit
    $program = getRecordById('Program', 'program_id', $program_id);
    
    // Get all colleges for dropdown
    $colleges = getAllColleges();
    
    if ($program) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Program</h1>
                <a href="index.php?page=programs" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Programs
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Program Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=programs&action=edit&id=<?php echo $program_id; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="program_name" class="form-label">Program Name</label>
                                <input type="text" class="form-control" id="program_name" name="program_name" 
                                    value="<?php echo htmlspecialchars($program['program_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="program_code" class="form-label">Program Code</label>
                                <input type="text" class="form-control" id="program_code" name="program_code" 
                                    value="<?php echo htmlspecialchars($program['program_code']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="college_id" class="form-label">College</label>
                            <select class="form-select" id="college_id" name="college_id" required>
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" 
                                    <?php echo ($program['college_id'] == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($college['college_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Program
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Program not found.</div>';
        echo '<a href="index.php?page=programs" class="btn btn-primary">Back to Programs</a>';
    }
} elseif ($action === 'view' && $program_id > 0) {
    // Get the program details
    $program = getRecordById('Program', 'program_id', $program_id);
    
    // Get college information
    $college = null;
    if ($program) {
        $college = getRecordById('College', 'college_id', $program['college_id']);
    }
    
    if ($program && $college) {
        // Get curriculum information with prerequisites
        $sql = "SELECT c.*, s.subject_code, s.title AS subject_title, s.units, s.prerequisite_id, 
                p.subject_code AS prerequisite_code 
                FROM Curriculum c 
                JOIN Subject s ON c.subject_id = s.subject_id 
                LEFT JOIN Subject p ON s.prerequisite_id = p.subject_id 
                WHERE c.program_id = $program_id 
                ORDER BY c.year_level, c.semester, s.subject_code";
        $result = $conn->query($sql);
        $curriculum = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $curriculum[] = $row;
            }
        }
        
        // Get sections for this program
        $sql = "SELECT * FROM Section WHERE program_id = $program_id ORDER BY academic_year, section_code";
        $result = $conn->query($sql);
        $sections = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        }
        
        // Count students in this program
        $sql = "SELECT COUNT(*) as student_count FROM Student WHERE program_id = $program_id";
        $result = $conn->query($sql);
        $student_count = 0;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $student_count = $row['student_count'];
        }
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($program['program_code']); ?></h1>
                <div>
                    <a href="index.php?page=programs&action=edit&id=<?php echo $program_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=programs" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Programs
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Program Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Program Name:</strong> <?php echo htmlspecialchars($program['program_name']); ?></p>
                            <p><strong>Program Code:</strong> <?php echo htmlspecialchars($program['program_code']); ?></p>
                            <p><strong>College:</strong> <?php echo htmlspecialchars($college['college_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Sections:</strong> <?php echo count($sections); ?></p>
                            <p><strong>Total Students:</strong> <?php echo $student_count; ?></p>
                            <p><strong>Total Subjects in Curriculum:</strong> <?php echo count($curriculum); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($curriculum)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Curriculum</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Group subjects by year and semester
                    $grouped = [];
                    $total_program_units = 0;
                    foreach ($curriculum as $item) {
                        $year = $item['year_level'];
                        $sem = $item['semester'];
                        if (!isset($grouped[$year])) {
                            $grouped[$year] = [];
                        }
                        if (!isset($grouped[$year][$sem])) {
                            $grouped[$year][$sem] = [];
                        }
                        $grouped[$year][$sem][] = $item;
                        $total_program_units += $item['units']; // Calculate total program units
                    }
                    ?>
                    <?php
                    // Display grouped curriculum
                    foreach ($grouped as $year => $semesters): ?>
                        <h4 class="mt-3">Year <?php echo $year; ?></h4>
                        <?php foreach ($semesters as $sem => $subjects): ?>
                            <h5 class="mt-2">Semester <?php echo $sem; ?></h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover curriculum-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%;">Subject Code</th>
                                            <th style="width: 40%;">Title</th>
                                            <th style="width: 20%;">Units</th>
                                            <th style="width: 20%;">Prerequisite</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $semester_units = 0;
                                        foreach ($subjects as $subject): 
                                            $semester_units += floatval($subject['units']);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_title']); ?></td>
                                            <td><?php echo $subject['units']; ?></td>
                                            <td><?php echo !empty($subject['prerequisite_code']) ? htmlspecialchars($subject['prerequisite_code']) : 'None'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success">
                                            <td colspan="2" class="text-end"><strong>Total Units</strong></td>
                                            <td class="text-center"><strong><?php echo number_format($semester_units, 2); ?></strong></td>
                                            <td></td> <!-- Empty cell to maintain structure under Prerequisite -->
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <div class="mt-4">
                        <h5>Overall Total Units: <?php echo $total_program_units; ?></h5>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($sections)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sections</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Section Code</th>
                                    <th>Academic Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sections as $section): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($section['section_code']); ?></td>
                                    <td><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                    <td>
                                        <a href="index.php?page=sections&action=view&id=<?php echo $section['section_id']; ?>" 
                                           class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Section">
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
        echo '<div class="alert alert-danger">Program not found.</div>';
        echo '<a href="index.php?page=programs" class="btn btn-primary">Back to Programs</a>';
    }
} else {
    // Default list view
    $programs = getAllPrograms();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-graduation-cap me-2"></i>Programs</h1>
            <a href="index.php?page=programs&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Program
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Programs List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Program Code</th>
                                <th>Program Name</th>
                                <th>College</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($program['program_code']); ?></td>
                                <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($program['college_name']); ?></td>
                                <td>
                                    <a href="index.php?page=programs&action=view&id=<?php echo $program['program_id']; ?>" 
                                       class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=programs&action=edit&id=<?php echo $program['program_id']; ?>" 
                                       class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=programs&action=delete&id=<?php echo $program['program_id']; ?>" 
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
<style>
    .curriculum-table {
        width: 100%;
    }
    .curriculum-table th,
    .curriculum-table td {
        vertical-align: middle;
    }
    .curriculum-table th:nth-child(1),
    .curriculum-table td:nth-child(1) {
        text-align: left;
    }
    .curriculum-table th:nth-child(2) {
        text-align: center; /* Center only the Title header */
    }
    .curriculum-table td:nth-child(2) {
        text-align: left; /* Keep subject titles left-aligned */
    }
    .curriculum-table th:nth-child(3),
    .curriculum-table td:nth-child(3) {
        text-align: center;
    }
    .curriculum-table th:nth-child(4),
    .curriculum-table td:nth-child(4) {
        text-align: center; /* Center the Prerequisite column */
    }
    .curriculum-table tr.table-success td:last-child {
        text-align: center;
    }
</style>