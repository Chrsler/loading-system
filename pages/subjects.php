<?php // pages/subjects.php
$action     = isset($_GET['action']) ? $_GET['action'] : 'list';
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code    = sanitize($_POST['subject_code']);
    $title           = sanitize($_POST['title']);
    $units           = intval($_POST['units']);
    $description     = sanitize($_POST['description']);
    $prerequisite_id = !empty($_POST['prerequisite_id']) ? intval($_POST['prerequisite_id']) : NULL;

    // Add subject - MODAL ACTION
    if ($action === 'add_modal') {
        $sql = "INSERT INTO Subject (subject_code, title, units, description, prerequisite_id) VALUES ('$subject_code', '$title', $units, '$description', " . ($prerequisite_id ? "$prerequisite_id" : "NULL") . ")";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Subject added successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        exit(); // Stop further execution to prevent full page load for modal action

    }
    // Add subject
    if ($action === 'add') {
        $sql = "INSERT INTO Subject (subject_code, title, units, description, prerequisite_id) VALUES ('$subject_code', '$title', $units, '$description', " . ($prerequisite_id ? "$prerequisite_id" : "NULL") . ")";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Subject added successfully!</div>';
            // Redirect to list after success - REMOVED REDIRECT FOR MODAL, BUT KEEP FOR REGULAR ADD
            echo '<script>window.location.href = "index.php?page=subjects";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
    // Update subject
    elseif ($action === 'edit' && $subject_id > 0) {
        $sql = "UPDATE Subject SET subject_code = '$subject_code', title = '$title', units = $units, description = '$description', prerequisite_id = " . ($prerequisite_id ? "$prerequisite_id" : "NULL") . " WHERE subject_id = $subject_id";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Subject updated successfully!</div>';
            // Redirect to list after success
            echo '<script>window.location.href = "index.php?page=subjects&action=view&id=' . $subject_id . '";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}

// Delete subject
if ($action === 'delete' && $subject_id > 0) {
    // Check if there are related records in Curriculum, Teaching_Assignment, or Enrollment tables
    $check_curriculum   = "SELECT * FROM Curriculum WHERE subject_id = $subject_id";
    $check_teaching     = "SELECT * FROM Teaching_Assignment WHERE subject_id = $subject_id";
    $check_enrollment   = "SELECT * FROM Enrollment WHERE subject_id = $subject_id";
    $check_prerequisite = "SELECT * FROM Subject WHERE prerequisite_id = $subject_id";
    $has_curriculum     = $conn->query($check_curriculum)->num_rows > 0;
    $has_teaching       = $conn->query($check_teaching)->num_rows > 0;
    $has_enrollment     = $conn->query($check_enrollment)->num_rows > 0;
    $has_prerequisite   = $conn->query($check_prerequisite)->num_rows > 0;

    if ($has_curriculum || $has_teaching || $has_enrollment || $has_prerequisite) {
        echo '<div class="alert alert-danger">Cannot delete subject because it is referenced by other records.</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql = "DELETE FROM Subject WHERE subject_id = $subject_id";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="alert alert-success">Subject deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        // Redirect to list after delete
        echo '<script>window.location.href = "index.php?page=subjects";</script>';
    }
}

// Display the appropriate view
if ($action === 'add') {
    // Get all subjects for prerequisites dropdown
    $subjects = getAllSubjects();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i>Add New Subject</h1>
            <a href="index.php?page=subjects" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Subjects
            </a>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Subject Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=subjects&action=add">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subject_code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="units" name="units" min="1" max="6" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prerequisite_id" class="form-label">Prerequisite</label>
                            <select class="form-select" id="prerequisite_id" name="prerequisite_id">
                                <option value="">None</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo $subject['subject_code'] . ' - ' . $subject['title']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Subject
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $subject_id > 0) {
    // Get the subject to edit
    $subject = getRecordById('Subject', 'subject_id', $subject_id);
    // Get all subjects for prerequisites dropdown
    $subjects = getAllSubjects();
    if ($subject) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Subject</h1>
                <a href="index.php?page=subjects&action=view&id=<?php echo $subject['subject_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Subject Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=subjects&action=edit&id=<?php echo $subject_id; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="subject_code" class="form-label">Subject Code</label>
                                <input type="text" class="form-control" id="subject_code" name="subject_code"
                                    value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?php echo htmlspecialchars($subject['title']); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="units" class="form-label">Units</label>
                                <input type="number" class="form-control" id="units" name="units" min="1" max="6"
                                    value="<?php echo $subject['units']; ?>" required>
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
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="4"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Subject
                        </button>
                    </form>
                </div>
            </div>
        </div>


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



        <?php
    } else {
        echo '<div class="alert alert-danger">Subject not found.</div>';
        echo '<a href="index.php?page=subjects" class="btn btn-primary">Back to Subjects</a>';
    }
} elseif ($action === 'view' && $subject_id > 0) {
    // Get the subject details
    $subject = getRecordById('Subject', 'subject_id', $subject_id);
    if ($subject) {
        // Get prerequisite information if any
        $prerequisite = null;
        if (!empty($subject['prerequisite_id'])) {
            $prerequisite = getRecordById('Subject', 'subject_id', $subject['prerequisite_id']);
        }
        // Get related curriculum
        $sql       = "SELECT c.*, p.program_name FROM Curriculum c JOIN Program p ON c.program_id = p.program_id WHERE c.subject_id = $subject_id";
        $result    = $conn->query($sql);
        $curricula = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $curricula[] = $row;
            }
        }
        ?>

        <!-- PER SUBJECT DETAILS -->
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-book me-2"></i>Subject Details</h1>
                <div>
                    <a href="index.php?page=subjects&action=edit&id=<?php echo $subject_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=curriculum&action=add" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Curriculum
                    </a>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subject Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($subject['subject_code']); ?></p>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($subject['title']); ?></p>
                            <p><strong>Units:</strong> <?php echo $subject['units']; ?></p>
                            <p><strong>Prerequisite:</strong>
                                <?php echo $prerequisite ? htmlspecialchars($prerequisite['subject_code'] . ' - ' . $prerequisite['title']) : 'None'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Description:</strong></p>
                            <div class="border p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($subject['description'] ?: 'No description available.')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($curricula)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Programs Including This Subject</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Program</th>
                                        <th>Year Level</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curricula as $curriculum): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($curriculum['program_name']); ?></td>
                                            <td><?php echo $curriculum['year_level']; ?></td>
                                            <td><?php echo $curriculum['semester']; ?></td>
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
        echo '<div class="alert alert-danger">Subject not found.</div>';
        echo '<a href="index.php?page=subjects" class="btn btn-primary">Back to Subjects</a>';
    }
} else { // Default list view
    $subjects = getAllSubjects();
    ?>
    <!-- SUBJECTS LIST -->
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i>Subjects</h1>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Subjects List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Title</th>
                                <th>Units</th>
                                <th>Prerequisite</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['title']); ?></td>
                                    <td><?php echo $subject['units']; ?></td>
                                    <td>
                                        <?php echo !empty($subject['prerequisite_code']) ? htmlspecialchars($subject['prerequisite_code']) : 'None'; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?page=subjects&action=view&id=<?php echo $subject['subject_id']; ?>"
                                            class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=subjects&action=edit&id=<?php echo $subject['subject_id']; ?>"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=subjects&action=delete&id=<?php echo $subject['subject_id']; ?>"
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