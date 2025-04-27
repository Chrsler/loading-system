<?php
// templates/header.php
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TCU Curriculum</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <!-- Custom CSS -->
        <style>
        :root {
            --primary-color: #DC3545;
            --secondary-color: #8B2D2D;
            --light-color: #ecf0f1;
            --dark-color: #8B2D2D;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
            padding-top: 56px;
            color: #333;
        }

        .navbar {
            background-color: var(--secondary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .d-flex {
            width: 100%;
        }

        .sidebar {
            background-color: var(--dark-color);
            color: white;
            height: calc(100vh - 56px);
            position: fixed;
            width: 220px;
            overflow-y: auto;
            transition: all 0.3s;
            flex-shrink: 0;
            /* Prevent sidebar from shrinking */
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 24px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            /* Take up remaining space */
            margin-left: 220px;
            /* Match sidebar width */
            padding: 20px;
        }

        .card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
            padding: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .dashboard-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            border-left-width: 8px;
        }

        .dashboard-card.card-colleges {
            border-left-color: var(--primary-color);
        }

        .dashboard-card.card-programs {
            border-left-color: var(--success-color);
        }

        .dashboard-card.card-subjects {
            border-left-color: var(--warning-color);
        }

        .dashboard-card.card-students {
            border-left-color: var(--danger-color);
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 10px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .submenu-item {
            font-size: 0.85rem !important;
        }

        .interactive-icon {
            transition: all 0.3s ease;
        }

        .interactive-icon:hover {
            color: #007bff;
            transform: scale(1.2);
        }

        .rotate-180 {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }

        .submenu {
            display: none;
        }

        .submenu.active {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .stat-icon {
                display: none;
            }
        }
        </style>
    </head>

    <body>
        <!-- Navbar -->
        <nav class="navbar navbar-dark navbar-expand-lg fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <img src="images/tculogo.svg" alt="TCU Logo"
                        style="height: 40px; margin-right: 0.5rem; vertical-align: middle;">
                    TCU Curriculum
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> Administrator
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-1"></i> Profile</a>
                                </li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-1"></i> Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-1"></i>
                                        Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>