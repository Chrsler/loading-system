<?php
// Keep all the existing PHP code at the top unchanged
// Fetch all students for the search dropdown
$students = getAllStudents();

// Handle form submission
$selected_student  = null;
$enrolled_subjects = [];
$total_units       = 0;
$section_data      = null;
$program_data      = null;
$year_level        = null;
$semester          = null;

// Get all possible year levels from the curriculum
$year_levels = getYearLevels();
// Get all possible semesters (typically 1 and 2, maybe summer)
$semesters = [1, 2]; // Can be expanded to include summer semester if needed

if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
    $student_id = (int) sanitize($_POST['student_id']);
    $year_level = isset($_POST['year_level']) ? (int) sanitize($_POST['year_level']) : null;
    $semester = isset($_POST['semester']) ? (int) sanitize($_POST['semester']) : null;

    // Get student data
    global $conn;
    $sql = "SELECT st.*, p.program_name, p.program_id, s.section_code 
            FROM Student st 
            JOIN Program p ON st.program_id = p.program_id 
            JOIN Section s ON st.section_id = s.section_id
            WHERE st.student_id = $student_id";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $selected_student = $result->fetch_assoc();

        // If year level and semester are selected, get the enrolled subjects
        if ($year_level !== null && $semester !== null) {
            // Add year_level and semester to student data
            $selected_student['year_level'] = $year_level;
            $selected_student['semester'] = $semester;

            // Get enrolled subjects based on program, year level, and semester
            $enrolled_subjects = getEnrolledSubjects($selected_student['program_id'], $year_level, $semester);

            // Calculate total units
            foreach ($enrolled_subjects as $subject) {
                $total_units += $subject['units'];
            }
        }
    }
}

// Function to get all year levels from the curriculum
function getYearLevels() {
    global $conn;
    $sql = "SELECT DISTINCT year_level FROM Curriculum ORDER BY year_level";
    $result = $conn->query($sql);
    
    $year_levels = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $year_levels[] = $row['year_level'];
        }
    }
    return $year_levels;
}

// Function to get enrolled subjects based on program, year level, and semester
function getEnrolledSubjects($program_id, $year_level, $semester)
{
    global $conn;
    $program_id = (int) $program_id;
    $year_level = (int) $year_level;
    $semester = (int) $semester;

    $sql = "SELECT s.subject_code, s.title, s.units 
            FROM Subject s 
            JOIN Curriculum c ON s.subject_id = c.subject_id 
            WHERE c.program_id = $program_id AND c.year_level = $year_level AND c.semester = $semester
            ORDER BY s.subject_code";
    $result = $conn->query($sql);

    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h2><i class="fas fa-certificate"></i> Certificate Of Enrollment</h2>
        </div>
        <div class="card-body">
            <form method="POST" class="mb-4">
                <div class="form-group row mb-3">
                    <label for="student_id" class="col-sm-2 col-form-label">Select Student:</label>
                    <div class="col-sm-8">
                        <select name="student_id" id="student_id" class="form-control select2" required>
                            <option value="">--Select Student--</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>"
                                    data-first-name="<?php echo $student['first_name']; ?>"
                                    data-last-name="<?php echo $student['last_name']; ?>"
                                    data-middle-name="<?php echo $student['middle_name'] ?? ''; ?>"
                                    <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                    <?php echo $student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['program_name'] . ' - ' . $student['section_code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group row mb-3">
                    <label for="year_level" class="col-sm-2 col-form-label">Year Level:</label>
                    <div class="col-sm-4">
                        <select name="year_level" id="year_level" class="form-control" required>
                            <option value="">--Select Year Level--</option>
                            <?php foreach ($year_levels as $year): ?>
                                <option value="<?php echo $year; ?>"
                                    <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == $year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <label for="semester" class="col-sm-2 col-form-label">Semester:</label>
                    <div class="col-sm-4">
                        <select name="semester" id="semester" class="form-control" required>
                            <option value="">--Select Semester--</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo $sem; ?>"
                                    <?php echo (isset($_POST['semester']) && $_POST['semester'] == $sem) ? 'selected' : ''; ?>>
                                    <?php echo $sem; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group row">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generate COE</button>
                    </div>
                </div>
            </form>

            <?php if ($selected_student): ?>
                <div id="certificate-content">
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Note:</strong> Click the Print COE button to open a printable version in a new tab. The document will display both copies on a single page.</p>
                    </div>

                    <!-- Display the certificate on the main page -->
                    <div class="on-page-certificate mb-4">
                        <div class="text-center mb-4">
                            <h3>CERTIFICATE OF ENROLLMENT</h3>
                            <h4>Academic Year <?php echo date('Y') . '-' . (date('Y') + 1); ?></h4>
                        </div>

                        <div class="student-details mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Student ID:</strong> <?php echo $selected_student['student_id']; ?></p>
                                    <p><strong>Name:</strong>
                                        <?php echo $selected_student['last_name'] . ', ' . $selected_student['first_name']; ?>
                                    </p>
                                    <p><strong>Status:</strong> <?php echo $selected_student['STATUS']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Program:</strong> <?php echo $selected_student['program_name']; ?></p>
                                    <p><strong>Section:</strong> <?php echo $selected_student['section_code']; ?></p>
                                    <p><strong>Year Level:</strong> <?php echo $selected_student['year_level']; ?></p>
                                    <p><strong>Semester:</strong> <?php echo $selected_student['semester']; ?></p>
                                </div>
                            </div>
                        </div>

                        <h5>Enrolled Subjects</h5>
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Title</th>
                                    <th class="text-center">Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrolled_subjects)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No subjects found for this student's program, year level, and semester.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($enrolled_subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['title']); ?></td>
                                            <td class="text-center"><?php echo $subject['units']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                                    <td class="text-center"><strong><?php echo $total_units; ?> Units</strong></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="mt-5 text-center">
                            <div class="row">
                                <div class="col-md-6">
                                    <p>Certified by:</p>
                                    <div class="mt-4">
                                        <p><strong>____________________</strong></p>
                                        <p>Registrar</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p>Date Issued:</p>
                                    <div class="mt-4">
                                        <p><strong><?php echo date('F d, Y'); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-4">
                        <button onclick="printCertificate()" class="btn btn-success btn-lg"><i class="fas fa-print"></i> Print COE</button>
                    </div>
                
                    <!-- Hidden div with content for the printable page -->
                    <div id="printable-content" style="display: none;">
                        <!-- This content will be copied to the new window -->
                        <div class="print-container">
                            <!-- First half - Registrar's Copy -->
                            <div class="certificate-half">
                                <div class="text-center mb-2">
                                    <h3>CERTIFICATE OF ENROLLMENT</h3>
                                    <h4>Academic Year <?php echo date('Y') . '-' . (date('Y') + 1); ?></h4>
                                    <p><em>Registrar's Copy</em></p>
                                </div>

                                <div class="student-details mb-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Student ID:</strong> <?php echo $selected_student['student_id']; ?></p>
                                            <p class="mb-1"><strong>Name:</strong>
                                                <?php echo $selected_student['last_name'] . ', ' . $selected_student['first_name']; ?>
                                            </p>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo $selected_student['STATUS']; ?></p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Program:</strong> <?php echo $selected_student['program_name']; ?></p>
                                            <p class="mb-1"><strong>Section:</strong> <?php echo $selected_student['section_code']; ?></p>
                                            <p class="mb-1"><strong>Year Level:</strong> <?php echo $selected_student['year_level']; ?></p>
                                            <p class="mb-1"><strong>Semester:</strong> <?php echo $selected_student['semester']; ?></p>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-2 mb-1">Enrolled Subjects</h5>
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Subject Code</th>
                                            <th>Title</th>
                                            <th class="text-center">Units</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enrolled_subjects)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No subjects found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($enrolled_subjects as $subject): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($subject['title']); ?></td>
                                                    <td class="text-center"><?php echo $subject['units']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                                            <td class="text-center"><strong><?php echo $total_units; ?> Units</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="mt-3 text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1">Certified by:</p>
                                            <div class="mt-2">
                                                <p class="mb-0"><strong>____________________</strong></p>
                                                <p>Registrar</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1">Date Issued:</p>
                                            <div class="mt-2">
                                                <p class="mb-0"><strong><?php echo date('F d, Y'); ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <!-- Second half - Student's Copy -->
                            <div class="certificate-half">
                                <div class="text-center mb-2">
                                    <h3>CERTIFICATE OF ENROLLMENT</h3>
                                    <h4>Academic Year <?php echo date('Y') . '-' . (date('Y') + 1); ?></h4>
                                    <p><em>Student's Copy</em></p>
                                </div>

                                <div class="student-details mb-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Student ID:</strong> <?php echo $selected_student['student_id']; ?></p>
                                            <p class="mb-1"><strong>Name:</strong>
                                                <?php echo $selected_student['last_name'] . ', ' . $selected_student['first_name']; ?>
                                            </p>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo $selected_student['STATUS']; ?></p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Program:</strong> <?php echo $selected_student['program_name']; ?></p>
                                            <p class="mb-1"><strong>Section:</strong> <?php echo $selected_student['section_code']; ?></p>
                                            <p class="mb-1"><strong>Year Level:</strong> <?php echo $selected_student['year_level']; ?></p>
                                            <p class="mb-1"><strong>Semester:</strong> <?php echo $selected_student['semester']; ?></p>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-2 mb-1">Enrolled Subjects</h5>
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Subject Code</th>
                                            <th>Title</th>
                                            <th class="text-center">Units</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enrolled_subjects)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No subjects found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($enrolled_subjects as $subject): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($subject['title']); ?></td>
                                                    <td class="text-center"><?php echo $subject['units']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                                            <td class="text-center"><strong><?php echo $total_units; ?> Units</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="mt-3 text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1">Certified by:</p>
                                            <div class="mt-2">
                                                <p class="mb-0"><strong>____________________</strong></p>
                                                <p>Registrar</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1">Date Issued:</p>
                                            <div class="mt-2">
                                                <p class="mb-0"><strong><?php echo date('F d, Y'); ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Select2 CSS and JS for searchable dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for the student dropdown
        $('#student_id').select2({
            placeholder: "Search for a student...",
            allowClear: true,
            matcher: function(params, data) {
                // If no search term, return all results
                if ($.trim(params.term) === '') {
                    return data;
                }

                // Normalize search term and data
                var term = params.term.toLowerCase();
                var firstName = data.element.getAttribute('data-first-name').toLowerCase();
                var lastName = data.element.getAttribute('data-last-name').toLowerCase();
                var middleName = data.element.getAttribute('data-middle-name').toLowerCase();
                var fullName = (lastName + ', ' + firstName + ' ' + middleName).toLowerCase();

                // Check if the search term matches any part of the name
                if (firstName.includes(term) || lastName.includes(term) || middleName.includes(term) || fullName.includes(term)) {
                    return data;
                }

                // If no match, return null
                return null;
            }
        });
    });
    
    // Function to open a new window and print the certificate
    function printCertificate() {
        // Get the content from the hidden div
        var content = document.getElementById('printable-content').innerHTML;
        
        // Create a new window
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // Write the HTML content to the new window
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Certificate of Enrollment</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                <style>
                    @media print {
                        @page {
                            size: portrait;
                            margin: 0.5cm;
                        }
                        
                        body {
                            padding: 0;
                            margin: 0;
                        }
                    }
                    
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                    }
                    
                    h3 {
                        font-size: 16px;
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    
                    h4 {
                        font-size: 14px;
                        margin-bottom: 5px;
                    }
                    
                    h5 {
                        font-size: 13px;
                        font-weight: bold;
                    }
                    
                    .print-container {
                        width: 100%;
                        max-width: 8.5in;
                        margin: 0 auto;
                    }
                    
                    .certificate-half {
                        padding: 10px;
                        border: 1px solid #000;
                    }
                    
                    .divider {
                        border-top: 2px dashed #000;
                        margin: 10px 0;
                        position: relative;
                    }
                    
                    .divider:after {
                        content: "✂️ Cut Here";
                        position: absolute;
                        left: 50%;
                        top: -10px;
                        transform: translateX(-50%);
                        background: #fff;
                        padding: 0 10px;
                        font-size: 12px;
                        color: #666;
                    }
                    
                    .table-sm {
                        font-size: 11px;
                    }
                    
                    .table-sm th, .table-sm td {
                        padding: 0.2rem;
                    }
                    
                    /* Print button visible only in browser, not when printing */
                    .print-btn {
                        display: block;
                        text-align: center;
                        margin: 20px auto;
                    }
                    
                    @media print {
                        .print-btn {
                            display: none;
                        }
                        
                        .divider:after {
                            color: #000;
                            background: none;
                        }
                    }
                </style>
            </head>
            <body>
                <button onclick="window.print()" class="btn btn-primary print-btn">Print Certificate</button>
                ${content}
            </body>
            </html>
        `);
        
        // Focus on the new window
        printWindow.focus();
    }
</script>

<style>
    /* Fix for Select2 input styling */
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 5px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    #printable-content {
        display: none;
    }
    
    .on-page-certificate {
        border: 1px solid #ddd;
        padding: 20px;
        margin-top: 20px;
        background-color: #f9f9f9;
    }
    
    @media screen {
        .on-page-certificate {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    }
</style>