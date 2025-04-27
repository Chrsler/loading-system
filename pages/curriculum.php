<?php // pages/curriculum.php
require_once 'function.php';
$action        = isset($_GET['action']) ? $_GET['action'] : 'add'; // Default to 'add' instead of 'list'
$curriculum_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = intval($_POST['program_id']);
    $year_level = intval($_POST['year_level']);
    $semester   = intval($_POST['semester']);

    // Add curriculum entry
    if ($action === 'add') {
        $subject_ids = $_POST['subject_id'] ?? []; // This will be an array of subject IDs
        foreach ($subject_ids as $subject_id) {
            $sql  = "INSERT INTO Curriculum (program_id, subject_id, year_level, semester) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $program_id, $subject_id, $year_level, $semester);
            if (!$stmt->execute()) {
                echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
            }
            $stmt->close();
        }
        echo '<div class="alert alert-success">Curriculum entries added successfully!</div>';
        echo '<script>window.location.href = "index.php?page=curriculum&action=add";</script>';
    }
    // Update curriculum entry
    elseif ($action === 'edit' && $curriculum_id > 0) {
        $subject_ids = $_POST['subject_id'] ?? [];

        // First delete existing subjects for this curriculum
        $sql  = "DELETE FROM Curriculum WHERE curriculum_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $curriculum_id);
        $stmt->execute();
        $stmt->close();

        // Then insert the new selections
        foreach ($subject_ids as $subject_id) {
            $sql  = "INSERT INTO Curriculum (curriculum_id, program_id, subject_id, year_level, semester) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiii", $curriculum_id, $program_id, $subject_id, $year_level, $semester);
            if (!$stmt->execute()) {
                echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
                $stmt->close();
                exit;
            }
            $stmt->close();
        }

        echo '<div class="alert alert-success">Curriculum entry updated successfully!</div>';
        echo '<script>window.location.href = "index.php?page=curriculum&action=add";</script>';
    }
}

// Delete curriculum entry
if ($action === 'delete' && $curriculum_id > 0) {
    $sql  = "DELETE FROM Curriculum WHERE curriculum_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $curriculum_id);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success">Curriculum entry deleted successfully!</div>';
        echo '<script>window.location.href = "index.php?page=curriculum&action=add";</script>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
    $stmt->close();
} 

// Delete subject
if ($action === 'delete_subject' && isset($_GET['subject_id'])) {
    $subject_id = intval($_GET['subject_id']);
    
    if ($subject_id > 0) {
        try {
            // Check if subject is used in curriculum
            $check_sql = "SELECT COUNT(*) FROM Curriculum WHERE subject_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $subject_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_row()[0];
            $check_stmt->close();

            if ($count > 0) {
                echo '<div class="alert alert-danger">Cannot delete subject because it is used in ' . $count . ' curriculum entries.</div>';
            } else {
                // Delete the subject
                $delete_sql = "DELETE FROM Subject WHERE subject_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $subject_id);
                
                if ($delete_stmt->execute()) {
                    echo '<div class="alert alert-success">Subject deleted successfully!</div>';
                    echo '<script>window.location.href = "index.php?page=curriculum&action=add";</script>';
                } else {
                    echo '<div class="alert alert-danger">Error deleting subject: ' . $conn->error . '</div>';
                }
                $delete_stmt->close();
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Invalid subject ID.</div>';
    }
}

// Display the appropriate view
if ($action === 'add') {
    $programs = getAllPrograms();
    $subjects = getAllSubjects();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-clipboard-list me-2"></i>Add New Curriculum Entry</h1>
            <div>
                <a href="index.php?page=curriculum&action=list" class="btn btn-danger me-2">
                    <i class="fas fa-list me-2"></i>View Curriculum List
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Curriculum Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=curriculum&action=add">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addSubjectModal">
                            <i class="fas fa-plus me-2"></i>Add New Subject
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Search Subjects:</label>
                        <input type="text" class="form-control" id="subjectSearch" placeholder="Search subjects...">
                        <div id="subjectSuggestions" style="display: none;"></div>
                    </div>

                    <!-- CHECK LIST OF SUBJECTS --> 
                    <div class="mb-3">
                        <label class="form-label">Subjects:</label>
                        <div class="form-check subject-list" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($subjects as $subject): ?>
                                <div class="subject-item" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                    <div class="subject-checkbox">
                                        <input class="form-check-input" type="checkbox" name="subject_id[]"
                                            value="<?php echo $subject['subject_id']; ?>"
                                            id="subject_<?php echo $subject['subject_id']; ?>">
                                        <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                                        </label>
                                    </div>
                                    <br>
                                    <div class="subject-actions">
                                        <a href="index.php?page=subjects&action=view&id=<?php echo $subject['subject_id']; ?>" 
                                           class="btn btn-sm btn-info me-1"
                                           data-bs-toggle="tooltip" title="View Subject">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger delete-subject"
                                                data-subject-id="<?php echo $subject['subject_id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete Subject">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Curriculum Entry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD Subjects MODAL -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addSubjectModalLabel">
                    <i class="fas fa-plus me-2"></i>Add New Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addSubjectForm" method="post" action="index.php?page=subjects&action=add_modal">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="subject_code_modal" class="form-label fw-bold">Subject Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control shadow-sm" id="subject_code_modal" name="subject_code" required placeholder=" ">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="title_modal" class="form-label fw-bold">Title</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-book"></i></span>
                                <input type="text" class="form-control shadow-sm" id="title_modal" name="title" required placeholder=" ">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="units_modal" class="form-label fw-bold">Units</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                <input type="number" class="form-control shadow-sm" id="units_modal" name="units" min="1" max="6" required placeholder=" ">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="prerequisite_search" class="form-label fw-bold">Prerequisite</label>
                            <div class="input-group position-relative">
                                <input type="text" class="form-control shadow-sm" id="prerequisite_search" placeholder="Search subjects..." autocomplete="off">
                                <input type="hidden" name="prerequisite_id" id="prerequisite_id">
                                <div id="prerequisite_suggestions" class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; top: 100%; left: 0;"></div>
                            </div>
                            <div id="noResultsMessage" class="text-muted mt-2" style="display: none;">No subjects found.</div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="description_modal" class="form-label fw-bold">Description</label>
                        <textarea class="form-control shadow-sm" id="description_modal" name="description" rows="3" placeholder="Enter subject description..."></textarea>
                    </div>
                    <div id="subject_modal_message" class="mt-3"></div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary save-subject-modal-btn">
                            <i class="fas fa-save me-2"></i>Save Subject
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}


if ($action === 'edit' && $curriculum_id > 0) {
    $curriculum = getRecordById('Curriculum', 'curriculum_id', $curriculum_id);
    $programs   = getAllPrograms();
    $subjects   = getAllSubjects();

    $current_subjects_sql = "SELECT subject_id FROM Curriculum WHERE curriculum_id = ?";
    $stmt                 = $conn->prepare($current_subjects_sql);
    $stmt->bind_param("i", $curriculum_id);
    $stmt->execute();
    $result              = $stmt->get_result();
    $current_subject_ids = [];
    while ($row = $result->fetch_assoc()) {
        $current_subject_ids[] = $row['subject_id'];
    }
    $stmt->close();

    if ($curriculum) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Curriculum Entry</h1>
                <div>
                    <a href="index.php?page=curriculum&action=list" class="btn btn-info me-2">
                        <i class="fas fa-list me-2"></i>View Curriculum List
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Curriculum Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=curriculum&action=edit&id=<?php echo $curriculum_id; ?>">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="program_id" class="form-label">Program</label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">Select Program</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?php echo $program['program_id']; ?>" <?php echo ($curriculum['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($curriculum['year_level'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1" <?php echo ($curriculum['semester'] == 1) ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo ($curriculum['semester'] == 2) ? 'selected' : ''; ?>>2</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Search Subjects:</label>
                            <input type="text" class="form-control" id="subjectSearchS" placeholder="Search subjects...">
                            <div id="subjectSuggestionsS" style="display: none;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subjects:</label>
                            <div class="form-check subject-list" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="subject-item" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                        <div class="subject-checkbox">
                                            <input class="form-check-input" type="radio" name="subject_id[]"
                                                value="<?php echo $subject['subject_id']; ?>"
                                                id="subject_<?php echo $subject['subject_id']; ?>"
                                                <?php echo in_array($subject['subject_id'], $current_subject_ids) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>">
                                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                                            </label>
                                        </div>
                                        <br>
                                        <div class="subject-actions"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Curriculum Entry
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Curriculum entry not found.</div>';
        echo '<a href="index.php?page=curriculum&action=add" class="btn btn-primary">Back to Add Curriculum</a>';
    }
} elseif ($action === 'view' && $curriculum_id > 0) {
    $curriculum = getRecordById('Curriculum', 'curriculum_id', $curriculum_id);
    if ($curriculum) {
        $program      = getRecordById('Program', 'program_id', $curriculum['program_id']);
        $subject      = getRecordById('Subject', 'subject_id', $curriculum['subject_id']);
        $prerequisite = $subject['prerequisite_id'] ? getRecordById('Subject', 'subject_id', $subject['prerequisite_id']) : null;

        $sql  = "SELECT ta.*, p.first_name, p.last_name, sec.section_code, sec.academic_year 
                FROM Teaching_Assignment ta 
                JOIN Professor p ON ta.professor_id = p.professor_id 
                JOIN Section sec ON ta.section_id = sec.section_id 
                WHERE ta.subject_id = ? 
                ORDER BY sec.academic_year DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $curriculum['subject_id']);
        $stmt->execute();
        $result               = $stmt->get_result();
        $teaching_assignments = [];
        while ($row = $result->fetch_assoc()) {
            $teaching_assignments[] = $row;
        }
        $stmt->close();
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-clipboard-list me-2"></i>Curriculum Entry</h1>
                <div>
                    <a href="index.php?page=curriculum&action=edit&id=<?php echo $curriculum_id; ?>"
                        class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=curriculum&action=add" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Add Curriculum
                    </a>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Curriculum Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Program:</strong>
                                <?php echo htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']); ?></p>
                            <p><strong>Subject:</strong>
                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?></p>
                            <p><strong>Year Level:</strong> <?php echo $curriculum['year_level']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Semester:</strong> <?php echo $curriculum['semester']; ?></p>
                            <p><strong>Units:</strong> <?php echo $subject['units']; ?></p>
                            <p><strong>Prerequisite:</strong>
                                <?php echo $prerequisite ? htmlspecialchars($prerequisite['subject_code'] . ' - ' . $prerequisite['title']) : 'None'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($teaching_assignments)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Teaching Assignments for <?php echo htmlspecialchars($subject['subject_code']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Professor</th>
                                        <th>Section Code</th>
                                        <th>Academic Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teaching_assignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
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
        echo '<div class="alert alert-danger">Curriculum entry not found.</div>';
        echo '<a href="index.php?page=curriculum&action=add" class="btn btn-primary">Back to Add Curriculum</a>';
    }
} elseif ($action === 'list') {
    $curriculum_entries = getAllCurriculum();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-clipboard-list me-2"></i>Curriculum List</h1>
            <a href="index.php?page=curriculum&action=add" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Add Curriculum
            </a>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Curriculum List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Curriculum ID</th>
                                <th>Program</th>
                                <th>Subject</th>
                                <th>Year Level</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($curriculum_entries as $entry): ?>
                                <tr>
                                    <td><?php echo $entry['curriculum_id']; ?></td>
                                    <td><?php echo htmlspecialchars($entry['program_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['subject_code'] . ' - ' . $entry['title']); ?></td>
                                    <td><?php echo $entry['year_level']; ?></td>
                                    <td><?php echo $entry['semester']; ?></td>
                                    <td>
                                        <a href="index.php?page=curriculum&action=view&id=<?php echo $entry['curriculum_id']; ?>"
                                            class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=curriculum&action=edit&id=<?php echo $entry['curriculum_id']; ?>"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=curriculum&action=delete&id=<?php echo $entry['curriculum_id']; ?>"
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

if ($action === 'get_subjects_options') {
    $subjects = getAllSubjects();
    $output   = '';
    foreach ($subjects as $subject) {
        $output .= '<div class="subject-item">';
        $output .= '<input class="form-check-input" type="checkbox" name="subject_id[]" value="' . $subject['subject_id'] . '" id="subject_' . $subject['subject_id'] . '">';
        $output .= '<label class="form-check-label" for="subject_' . $subject['subject_id'] . '">';
        $output .= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']);
        $output .= '</label><br>';
        $output .= '</div>';
    }
    echo $output;
    exit();
}
?>

<!-- EDIT Subject MODAL -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSubjectForm" method="post" action="index.php?page=curriculum&action=edit_subject">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_subject_code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="edit_units" name="units" min="1" max="6" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_prerequisite_id" class="form-label">Prerequisite</label>
                            <select class="form-select" id="edit_prerequisite_id" name="prerequisite_id">
                                <option value="">None</option>
                                <?php 
                                $subjects = getAllSubjects(); // Ensure subjects are fetched here
                                foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                    </div>
                    <div id="edit_subject_message"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Consolidated JavaScript -->
<script>
// Store subjects data from PHP
const subjects = <?php echo json_encode($subjects); ?>;

// Generic search handler
function setupSearch(inputId, suggestionsId, itemClass, actionsClass, hiddenId = null) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    const noResults = document.getElementById('noResultsMessage');

    if (!input) {
        console.warn(`Input element with ID '${inputId}' not found`);
        return;
    }
    if (!suggestions) {
        console.warn(`Suggestions element with ID '${suggestionsId}' not found`);
        return;
    }

    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let matches = [];

        if (searchTerm.length > 0) {
            matches = subjects.filter(subject => 
                subject.subject_code.toLowerCase().includes(searchTerm) || 
                subject.title.toLowerCase().includes(searchTerm)
            );
        }

        if (hiddenId) {
            suggestions.innerHTML = '';
            if (matches.length > 0) {
                matches.forEach(subject => {
                    const item = document.createElement('div');
                    item.className = 'dropdown-item';
                    item.style.cursor = 'pointer';
                    item.textContent = `${subject.subject_code} - ${subject.title}`;
                    item.dataset.id = subject.subject_id;
                    item.addEventListener('click', function() {
                        input.value = this.textContent;
                        const hiddenInput = document.getElementById(hiddenId);
                        if (hiddenInput) hiddenInput.value = this.dataset.id;
                        suggestions.innerHTML = '';
                        suggestions.classList.remove('show');
                    });
                    suggestions.appendChild(item);
                });
                suggestions.classList.add('show');
                if (noResults) noResults.style.display = 'none';
            } else {
                suggestions.innerHTML = '';
                suggestions.classList.remove('show');
                if (noResults) noResults.style.display = 'block';
            }
        } else {
            const items = document.getElementsByClassName(itemClass);
            let visibleItems = 0;

            Array.from(items).forEach(item => {
                const label = item.getElementsByTagName('label')[0];
                const text = label.textContent.toLowerCase();
                const actions = item.getElementsByClassName(actionsClass)[0];

                if (searchTerm.length === 0 || text.includes(searchTerm)) {
                    item.style.display = 'flex';
                    if (actions) actions.style.display = 'inline-flex';
                    visibleItems++;
                } else {
                    item.style.display = 'none';
                    if (actions) actions.style.display = 'none';
                }
            });

            if (noResults) {
                noResults.style.display = visibleItems === 0 ? 'block' : 'none';
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (input && suggestions && !input.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.classList.remove('show');
        }
    });
}

// Initialize everything after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Search handlers
    if (document.getElementById('subjectSearch')) {
        setupSearch('subjectSearch', 'subjectSuggestions', 'subject-item', 'subject-actions');
    }
    if (document.getElementById('subjectSearchS')) {
        setupSearch('subjectSearchS', 'subjectSuggestionsS', 'subject-item', 'subject-actions');
    }
    if (document.getElementById('prerequisite_search')) {
        setupSearch('prerequisite_search', 'prerequisite_suggestions', null, null, 'prerequisite_id');
    }

    // Add Subject Form submission
    const addSubjectForm = document.getElementById('addSubjectForm');
    if (addSubjectForm) {
        addSubjectForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('subject_modal_message').innerHTML = data;
                this.reset();
                $('#addSubjectModal').modal('hide');
                document.getElementById('subject_modal_message').innerHTML = '';
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('subject_modal_message').innerHTML = '<div class="alert alert-danger">Error submitting form. Please try again.</div>';
            });
        });
    }

    // Delete subject handler
    document.querySelectorAll('.delete-subject').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const subjectId = this.getAttribute('data-subject-id');
            
            if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                fetch(`index.php?page=curriculum&action=delete_subject&subject_id=${subjectId}`, {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const successAlert = doc.querySelector('.alert-success');
                    if (successAlert) {
                        this.closest('.subject-item').remove();
                        const messageContainer = document.createElement('div');
                        messageContainer.className = 'alert alert-success mt-3';
                        messageContainer.textContent = 'Subject deleted successfully!';
                        document.querySelector('.card-body').prepend(messageContainer);
                        setTimeout(() => messageContainer.remove(), 3000);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        const errorAlert = doc.querySelector('.alert-danger');
                        if (errorAlert) {
                            const errorContainer = document.createElement('div');
                            errorContainer.className = 'alert alert-danger mt-3';
                            errorContainer.textContent = errorAlert.textContent;
                            document.querySelector('.card-body').prepend(errorContainer);
                            setTimeout(() => errorContainer.remove(), 5000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'alert alert-danger mt-3';
                    errorContainer.textContent = 'Error deleting subject. Please try again.';
                    document.querySelector('.card-body').prepend(errorContainer);
                    setTimeout(() => errorContainer.remove(), 5000);
                });
            }
        });
    });

    // Edit Subject Form submission (Note: This requires an edit button to populate data, which is missing in your HTML)
    const editSubjectForm = document.getElementById('editSubjectForm');
    if (editSubjectForm) {
        editSubjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('edit_subject_message').innerHTML = data;
                $('#editSubjectModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                document.getElementById('edit_subject_message').innerHTML =
                    '<div class="alert alert-danger">Error: ' + error + '</div>';
            });
        });
    }

    // Clear modal messages on hide
    $('#addSubjectModal').on('hidden.bs.modal', function() {
        document.getElementById('subject_modal_message').innerHTML = '';
    });
});
</script>

<!-- Updated Custom CSS -->
<style>
    .input-group .form-control { 
        transition: border-color 0.3s ease, box-shadow 0.3s ease; 
    }
    .input-group .form-control:focus { 
        border-color: #8B2D2D; 
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); 
    }
    .subject-list { 
        background: #f8f9fa; 
        border-radius: 5px; 
    }
    .subject-item { 
        transition: background-color 0.2s ease; 
    }
    .subject-item:hover { 
        background-color: #e9ecef; 
    }
    .subject-item.highlight { 
        background-color: #e7f1ff; 
    }
    .modal-content { 
        border-radius: 10px; 
    }
    .modal-header { 
        border-bottom: none; 
    }
    .modal-footer { 
        padding-top: 0; 
    }
    .btn-primary { 
        transition: background-color 0.3s ease, transform 0.2s ease; 
    }
    .btn-primary:hover { 
        background-color: #8B2D2D; 
        transform: translateY(-2px); 
    }
    .btn-outline-secondary { 
        transition: border-color 0.3s ease, color 0.3s ease; 
    }
    .btn-outline-secondary:hover { 
        border-color: #6c757d; 
        color: #6c757d; 
    }
    .dropdown-item { 
        padding: 8px 12px; 
        transition: background-color 0.2s; 
    }
    .dropdown-item:hover { 
        background-color: #f8f9fa; 
    }
    .dropdown-menu.show { 
        display: block; 
        border: 1px solid #ced4da; 
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); 
        border-radius: 4px; 
        background-color: #fff; 
    }
    .input-group { 
        position: relative; 
    }
    #prerequisite_suggestions { 
        position: absolute; 
        z-index: 1000; 
        top: 100%; 
        left: 0; 
        right: 0; 
        max-height: 200px; 
        overflow-y: auto; 
        border: 1px solid #ced4da; 
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); 
        background-color: #fff; 
    }
</style>