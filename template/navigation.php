<?php
// templates/navigation.php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!-- Sidebar -->
<div class="d-flex">
    <div class="sidebar">
        <div class="p-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>"
                        href="index.php?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'colleges' ? 'active' : ''; ?>"
                        href="index.php?page=colleges">
                        <i class="fas fa-university"></i> Colleges
                    </a>
                </li>

                <!-- Curriculum with Submenu -->
                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center" href="#"
                        id="curriculum-toggle">
                        <span><i class="fas fa-clipboard-list me-2 interactive-icon"></i> Curriculum</span>
                        <i class="fas fa-chevron-down interactive-icon"></i>
                    </a>
                    <ul class="nav flex-column submenu ps-4" id="curriculum-menu">
                        <li class="nav-item">
                            <a class="nav-link py-1 submenu-item <?php echo $current_page == 'programs' ? 'active' : ''; ?>"
                                href="index.php?page=programs">
                                <i class="fas fa-graduation-cap me-2 interactive-icon"></i> Add Programs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 submenu-item <?php echo $current_page == 'curriculum' ? 'active' : ''; ?>"
                                href="index.php?page=curriculum&action=add">
                                <i class="fas fa-clipboard-list me-2 interactive-icon"></i> Add Curriculum
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- PROFESSORS SUB-MENU -->

                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'professors' ? 'active' : ''; ?>"
                        href="index.php?page=professors">
                        <i class="fas fa-chalkboard-teacher"></i> Professors
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'sections' ? 'active' : ''; ?>"
                        href="index.php?page=sections">
                        <i class="fas fa-th-large"></i> Sections
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'students' ? 'active' : ''; ?>"
                        href="index.php?page=students">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'teaching_assignments' ? 'active' : ''; ?>"
                        href="index.php?page=teaching_assignments">
                        <i class="fas fa-tasks"></i> Teaching Assignments
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'enrollments' ? 'active' : ''; ?>"
                        href="index.php?page=enrollments">
                        <i class="fas fa-clipboard-check"></i> Enrollments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'coe' ? 'active' : ''; ?>" href="index.php?page=coe">
                        <i class="fas fa-certificate"></i> COE
                    </a>
                </li>

                <!-- SCHEDULE -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'sched' ? 'active' : ''; ?>"
                        href="index.php?page=sched">
                        <i class="fa-solid fa-calendar-days"></i> SCHEDULE
                    </a>
                </li>


            </ul>
        </div>
    </div>
    <div class="main-content">
        <!-- Main content will be appended here -->

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Function to initialize a menu toggle
            function setupMenuToggle(toggleId, menuId) {
                const toggle = document.getElementById(toggleId);
                const menu = document.getElementById(menuId);
                const chevron = toggle.querySelector('.fa-chevron-down');

                // Check if any submenu item is active and show submenu if true
                if (menu.querySelector('.nav-link.active')) {
                    menu.classList.add('active');
                    chevron.classList.add('rotate-180');
                }

                // Add click event listener
                toggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    menu.classList.toggle('active');
                    chevron.classList.toggle('rotate-180');
                });
            }

            // Initialize both menus
            setupMenuToggle("curriculum-toggle", "curriculum-menu");
        });
        </script>