<?php
// pages/professors.php
require_once 'function.php';

$action       = isset($_GET['action']) ? $_GET['action'] : 'list';
$professor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name  = sanitize($_POST['last_name']);
    $department = sanitize($_POST['department']);

    // Add professor
    if ($action === 'add') {
        $sql  = "INSERT INTO Professor (first_name, last_name, department) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $first_name, $last_name, $department);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Professor added successfully!</div>';
            echo '<script>window.location.href = "index.php?page=professors";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    // Update professor
    elseif ($action === 'edit' && $professor_id > 0) {
        $sql  = "UPDATE Professor SET first_name = ?, last_name = ?, department = ? WHERE professor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $first_name, $last_name, $department, $professor_id);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Professor updated successfully!</div>';
            echo '<script>window.location.href = "index.php?page=professors";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Delete professor
if ($action === 'delete' && $professor_id > 0) {
    // Check if there are related records in Teaching_Assignment or Enrollment tables
    $check_teaching   = "SELECT COUNT(*) FROM Teaching_Assignment WHERE professor_id = ?";
    $check_enrollment = "SELECT COUNT(*) FROM Enrollment WHERE professor_id = ?";

    $stmt = $conn->prepare($check_teaching);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $stmt->bind_result($teaching_count);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare($check_enrollment);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $stmt->bind_result($enrollment_count);
    $stmt->fetch();
    $stmt->close();

    if ($teaching_count > 0 || $enrollment_count > 0) {
        echo '<div class="alert alert-danger">Cannot delete professor because they have ' .
            $teaching_count . ' teaching assignment(s) and ' . $enrollment_count . ' enrollment(s).</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql  = "DELETE FROM Professor WHERE professor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $professor_id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Professor deleted successfully!</div>';
            echo '<script>window.location.href = "index.php?page=professors";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Display the appropriate view
if ($action === 'add') {
    ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chalkboard-teacher me-2"></i>Add New Professor</h1>
        <a href="index.php?page=professors" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Professors
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Professor Information</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=professors&action=add">
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
                <div class="mb-3">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" required>

                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Professor
                </button>
            </form>
        </div>
    </div>
</div>
<?php
} elseif ($action === 'edit' && $professor_id > 0) {
    $professor = getRecordById('Professor', 'professor_id', $professor_id);

    if ($professor) {
        ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit me-2"></i>Edit Professor</h1>
        <a href="index.php?page=professors" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Professors
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Professor Information</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=professors&action=edit&id=<?php echo $professor_id; ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            value="<?php echo htmlspecialchars($professor['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                            value="<?php echo htmlspecialchars($professor['last_name']); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department"
                        value="<?php echo htmlspecialchars($professor['department']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Professor
                </button>
            </form>
        </div>
    </div>
</div>
<?php
    } else {
        echo '<div class="alert alert-danger">Professor not found.</div>';
        echo '<a href="index.php?page=professors" class="btn btn-primary">Back to Professors</a>';
    }
} elseif ($action === 'view' && $professor_id > 0) {
    $professor = getRecordById('Professor', 'professor_id', $professor_id);

    if ($professor) {
        // Get teaching assignments
        $sql  = "SELECT ta.*, sub.subject_code, sub.title AS subject_title, sec.section_code, sec.academic_year 
                FROM Teaching_Assignment ta 
                JOIN Subject sub ON ta.subject_id = sub.subject_id 
                JOIN Section sec ON ta.section_id = sec.section_id 
                WHERE ta.professor_id = ? 
                ORDER BY sec.academic_year DESC, sub.subject_code";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $professor_id);
        $stmt->execute();
        $result               = $stmt->get_result();
        $teaching_assignments = [];
        while ($row = $result->fetch_assoc()) {
            $teaching_assignments[] = $row;
        }
        $stmt->close();

        // Count enrollments
        $sql  = "SELECT COUNT(*) as enrollment_count 
                FROM Enrollment 
                WHERE professor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $professor_id);
        $stmt->execute();
        $stmt->bind_result($enrollment_count);
        $stmt->fetch();
        $stmt->close();
        ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i
                class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
        </h1>
        <div>
            <!-- <a href="index.php?page=professors&action=edit&id=<?php echo $professor_id; ?>"
                        class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a> -->

            <a href="index.php?page=teaching_assignments&action=add&professor_id=<?php echo $professor_id; ?>"
                class="btn btn-primary">
                <i class="fa-solid fa-print me-2"></i>Print Schedule
            </a>

            <a href="index.php?page=teaching_assignments&action=add&professor_id=<?php echo $professor_id; ?>"
                class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Teaching Assignment
            </a>
            <a href="index.php?page=professors" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Professors
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Professor Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>First Name:</strong> <?php echo htmlspecialchars($professor['first_name']); ?></p>
                    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($professor['last_name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($professor['department']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Professor ID:</strong> <?php echo $professor_id; ?></p>
                    <p><strong>Total Teaching Assignments:</strong> <?php echo count($teaching_assignments); ?></p>
                    <p><strong>Total Enrollments:</strong> <?php echo $enrollment_count; ?></p>
                </div>
            </div>
        </div>
    </div>

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
                            <th>Section Code</th>
                            <th>Academic Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teaching_assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['subject_title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['section_code']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['academic_year']); ?></td>
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
        echo '<div class="alert alert-danger">Professor not found.</div>';
        echo '<a href="index.php?page=professors" class="btn btn-primary">Back to Professors</a>';
    }
} else {
    // Default list view
    $professors = getAllProfessors();
    ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chalkboard-teacher me-2"></i>Professors</h1>
        <a href="index.php?page=teaching_assignments" class="btn btn-primary">
            <i class="fa-solid fa-list-ol me-2"></i>View List
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Professors List</h5>

            <!-- ADD PROFESSOR BUTTON -->
            <a href="index.php?page=professors&action=add" class="btn btn-dark-red">
                <i class="fas fa-plus me-2"></i>Add New Professor
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover data-table">
                    <thead>
                        <tr>
                            <th>Professor ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professors as $professor): ?>
                        <tr>
                            <td><?php echo $professor['professor_id']; ?></td>
                            <td><?php echo htmlspecialchars($professor['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($professor['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($professor['department']); ?></td>
                            <td>
                                <a href="index.php?page=professors&action=view&id=<?php echo $professor['professor_id']; ?>"
                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=professors&action=edit&id=<?php echo $professor['professor_id']; ?>"
                                    class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=professors&action=delete&id=<?php echo $professor['professor_id']; ?>"
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
.btn-dark-red {
    background-color: #8B2D2D !important;
    /* Dark Red */
    border-color: #8B0000 !important;
    color: white !important;
}

.btn-dark-red:hover {
    background-color: #600000 !important;
    /* Darker Red on Hover */
    border-color: #600000 !important;
}
</style>