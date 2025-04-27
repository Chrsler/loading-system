<?php
// pages/colleges.php
require_once 'function.php';

$action     = isset($_GET['action']) ? $_GET['action'] : 'list';
$college_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college_name = sanitize($_POST['college_name']);

    // Add college
    if ($action === 'add') {
        $sql  = "INSERT INTO College (college_name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $college_name);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">College added successfully!</div>';
            echo '<script>window.location.href = "index.php?page=colleges";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    // Update college
    elseif ($action === 'edit' && $college_id > 0) {
        $sql  = "UPDATE College SET college_name = ? WHERE college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $college_name, $college_id);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">College updated successfully!</div>';
            echo '<script>window.location.href = "index.php?page=colleges";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Delete college
if ($action === 'delete' && $college_id > 0) {
    // Check if there are related records in Program table
    $check_program = "SELECT COUNT(*) FROM Program WHERE college_id = ?";
    $stmt          = $conn->prepare($check_program);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $stmt->bind_result($program_count);
    $stmt->fetch();
    $stmt->close();

    if ($program_count > 0) {
        echo '<div class="alert alert-danger">Cannot delete college because it has ' . $program_count . ' related program(s).</div>';
        $action = 'list'; // Revert to list view
    } else {
        $sql  = "DELETE FROM College WHERE college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $college_id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">College deleted successfully!</div>';
            echo '<script>window.location.href = "index.php?page=colleges";</script>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Helper function to get the total number of programs for a college
function getProgramCount($college_id) {
    global $conn;
    $sql  = "SELECT COUNT(*) FROM Program WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// Display the appropriate view
if ($action === 'add') {
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-university me-2"></i>Add New College</h1>
            <a href="index.php?page=colleges" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Colleges
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">College Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=colleges&action=add">
                    <div class="mb-3">
                        <label for="college_name" class="form-label">College Name</label>
                        <input type="text" class="form-control" id="college_name" name="college_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save College
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'edit' && $college_id > 0) {
    $college = getRecordById('College', 'college_id', $college_id);

    if ($college) {
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit College</h1>
                <a href="index.php?page=colleges" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Colleges
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit College Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=colleges&action=edit&id=<?php echo $college_id; ?>">
                        <div class="mb-3">
                            <label for="college_name" class="form-label">College Name</label>
                            <input type="text" class="form-control" id="college_name" name="college_name"
                                value="<?php echo htmlspecialchars($college['college_name']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update College
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">College not found.</div>';
        echo '<a href="index.php?page=colleges" class="btn btn-primary">Back to Colleges</a>';
    }
} elseif ($action === 'view' && $college_id > 0) {
    $college = getRecordById('College', 'college_id', $college_id);

    if ($college) {
        // Get related programs
        $sql  = "SELECT program_id, program_code, program_name 
                FROM Program 
                WHERE college_id = ? 
                ORDER BY program_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $college_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
        $stmt->close();

        // Count total programs
        $program_count = count($programs);

        // Count students in programs under this college
        $sql  = "SELECT COUNT(*) as student_count 
                FROM Student s 
                JOIN Program p ON s.program_id = p.program_id 
                WHERE p.college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $college_id);
        $stmt->execute();
        $stmt->bind_result($student_count);
        $stmt->fetch();
        $stmt->close();
        ?>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-university me-2"></i><?php echo htmlspecialchars($college['college_name']); ?></h1>
                <div>
                    <a href="index.php?page=colleges&action=edit&id=<?php echo $college_id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="index.php?page=colleges" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Colleges
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">College Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>College Name:</strong> <?php echo htmlspecialchars($college['college_name']); ?></p>
                            <p><strong>College ID:</strong> <?php echo $college_id; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Programs:</strong> <?php echo $program_count; ?></p>
                            <p><strong>Total Students:</strong> <?php echo $student_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($programs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Programs under <?php echo htmlspecialchars($college['college_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Program Code</th>
                                        <th>Program Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $program): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($program['program_code']); ?></td>
                                            <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                                            <td>
                                                <a href="index.php?page=programs&action=view&id=<?php echo $program['program_id']; ?>"
                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Program">
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
        echo '<div class="alert alert-danger">College not found.</div>';
        echo '<a href="index.php?page=colleges" class="btn btn-primary">Back to Colleges</a>';
    }
} else {
    // Default list view
    $colleges = getAllColleges();
    ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-university me-2"></i>Colleges</h1>
            <a href="index.php?page=colleges&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New College
            </a>
        </div>

        <!-- Search Bar -->
        <div class="search-bar mb-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" placeholder="Search..." id="searchInput" onkeyup="filterTable()">
            </div>
        </div>

        <!-- Colleges Section -->
        <div class="colleges-section">
            <h2></h2>
            <div class="row row-cols-1 row-cols-md-4 g-4">
                <?php foreach ($colleges as $college): ?>
                    <div class="col">
                        <a href="index.php?page=colleges&action=view&id=<?php echo $college['college_id']; ?>" class="college-card-link">
                            <div class="card college-card" style="background: <?php echo getCollegeColor($college['college_id']); ?>;">
                                <div class="card-body text-center">
                                    <img src="<?php echo getCollegeLogo($college['college_id']); ?>" alt="College Logo" class="college-emblem mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h5>
                                    <p class="card-text"><?php echo getProgramCount($college['college_id']); ?> Programs</p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- JavaScript for filtering -->
        <script>
            function filterTable() {
                var input = document.getElementById("searchInput").value.toLowerCase();
                var cards = document.getElementsByClassName("college-card");

                for (var i = 0; i < cards.length; i++) {
                    var text = cards[i].getElementsByTagName("h5")[0].innerText.toLowerCase();
                    if (text.indexOf(input) > -1) {
                        cards[i].parentElement.style.display = "";
                    } else {
                        cards[i].parentElement.style.display = "none";
                    }
                }
            }
        </script>

        <!-- Inline CSS to set uniform tile sizes and remove text decoration -->
        <style>
            .college-card {
                width: 100%; /* Full width of the column */
                height: 200px; /* Fixed height for uniformity */
                display: flex;
                flex-direction: column;
                justify-content: center;
                color: #FFFFFF; /* White text */
            }
            .college-card-link {
                text-decoration: none; /* Remove underline */
                color: inherit; /* Inherit text color from parent */
            }
            .college-card-link:hover .college-card {
                filter: brightness(85%); /* Darken gradient on hover */
                cursor: pointer; /* Change cursor to indicate clickable */
            }
            .college-emblem {
                width: 70px; /* Increased from 50px to 70px for slightly bigger logos */
                height: 70px; /* Increased from 50px to 70px */
                object-fit: contain; /* Ensure image fits without distortion */
            }
        </style>
    </div>
    <?php
}
?>

<?php
// Helper function to assign gradient colors based on college_id
function getCollegeColor($college_id)
{
    switch ($college_id) {
        case 1: // Arts & Sciences
            return 'linear-gradient(to bottom right, #4A4A4A, #7A7A7A)'; 
        case 3: // Business Management
            return 'linear-gradient(to bottom right, #F4D03F, #C78320)'; 
        case 6: // Criminal Justice
            return 'linear-gradient(to bottom right,#8b2525, #550000)'; 
        case 2: // Education
            return 'linear-gradient(to bottom right,rgb(250, 62, 62), #C62828)'; 
        case 7: // Hospitality & Tourism Management
            return 'linear-gradient(to bottom right,rgb(7, 145, 9), #055106)'; 
        case 5: // Information & Communication Technology
            return 'linear-gradient(to bottom right, #8B008B, #43166B)'; 
        case 4: // Graduate Studies
            return 'linear-gradient(to bottom right,rgb(196, 83, 83), #8E3D3D)'; 
        default:
            return 'linear-gradient(to bottom right, #FFFFFF, #E0E0E0)'; 
            // White to light gray
    }
}

// Helper function to assign logos based on college_id
function getCollegeLogo($college_id)
{
    $logo_path = 'images/'; // Directory where logos are stored
    switch ($college_id) {
        case 1: return $logo_path . 'arts.png'; // Arts & Sciences
        case 3: return $logo_path . 'business.png'; // Business Management
        case 6: return $logo_path . 'crim.png'; // Criminal Justice
        case 2: return $logo_path . 'educ.png'; // Education
        case 7: return $logo_path . 'hm.png'; // Hospitality & Tourism Management
        case 5: return $logo_path . 'cict.png'; // Information & Communication Technology
        case 4: return $logo_path . 'gs.png'; // Graduate Studies
        default: return $logo_path . 'default.png'; // Default logo for new or unrecognized colleges
    }
}
?>