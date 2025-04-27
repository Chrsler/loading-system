<?php
// pages/dashboard.php

// Count records in each table
$college_count    = count(getAllColleges());
$program_count    = count(getAllPrograms());
$subject_count    = count(getAllSubjects());
$professor_count  = count(getAllProfessors());
$section_count    = count(getAllSections());
$student_count    = count(getAllStudents());
$curriculum_count = count(getAllCurriculum());
$assignment_count = count(getAllTeachingAssignments());
$enrollment_count = count(getAllEnrollments());
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card card-colleges">
                <div class="card-body">
                    <h5 class="card-title">Colleges</h5>
                    <h2 class="mb-0"><?php echo $college_count; ?></h2>
                    <i class="fas fa-university stat-icon text-primary"></i>
                    <p class="card-text"><a href="index.php?page=colleges" class="text-decoration-none">View Details
                            &rarr;</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card card-programs">
                <div class="card-body">
                    <h5 class="card-title">Programs</h5>
                    <h2 class="mb-0"><?php echo $program_count; ?></h2>
                    <i class="fas fa-graduation-cap stat-icon text-success"></i>
                    <p class="card-text"><a href="index.php?page=programs" class="text-decoration-none">View Details
                            &rarr;</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card card-subjects">
                <div class="card-body">
                    <h5 class="card-title">Subjects</h5>
                    <h2 class="mb-0"><?php echo $subject_count; ?></h2>
                    <i class="fas fa-book stat-icon text-warning"></i>
                    <p class="card-text"><a href="index.php?page=subjects" class="text-decoration-none">View Details
                            &rarr;</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card card-students">
                <div class="card-body">
                    <h5 class="card-title">Students</h5>
                    <h2 class="mb-0"><?php echo $student_count; ?></h2>
                    <i class="fas fa-user-graduate stat-icon text-danger"></i>
                    <p class="card-text"><a href="index.php?page=students" class="text-decoration-none">View Details
                            &rarr;</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics Overview</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <tbody>
                                <tr>
                                    <th width="60%">Total Professors</th>
                                    <td><?php echo $professor_count; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Sections</th>
                                    <td><?php echo $section_count; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Curriculum Entries</th>
                                    <td><?php echo $curriculum_count; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Teaching Assignments</th>
                                    <td><?php echo $assignment_count; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Enrollments</th>
                                    <td><?php echo $enrollment_count; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php?page=students&action=add" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add New Student
                        </a>
                        <a href="index.php?page=subjects&action=add" class="btn btn-success">
                            <i class="fas fa-book-medical me-2"></i>Add New Subject
                        </a>
                        <a href="index.php?page=enrollments&action=add" class="btn btn-info">
                            <i class="fas fa-clipboard-check me-2"></i>New Enrollment
                        </a>
                        <a href="index.php?page=teaching_assignments&action=add" class="btn btn-warning">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Assign Teacher
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>