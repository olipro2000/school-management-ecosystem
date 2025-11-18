<nav class="col-md-3 col-lg-2 d-md-block advanced-sidebar sidebar collapse" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column" data-role="<?= $_SESSION['user_role'] ?>">
            <li class="nav-item mobile-nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>students.php">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>teachers.php">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'parents.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>parents.php">
                    <i class="fas fa-users"></i>
                    <span>Parents</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>classes.php">
                    <i class="fas fa-chalkboard"></i>
                    <span>Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>subjects.php">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>payments.php">
                    <i class="fas fa-money-bill"></i> School Fees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'fee_settings.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>fee_settings.php">
                    <i class="fas fa-cog"></i> Fee Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'class_promotion.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>class_promotion.php">
                    <i class="fas fa-arrow-up"></i> Class Promotion
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>announcements.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] == 'teacher'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>attendance.php">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>grades.php">
                    <i class="fas fa-graduation-cap"></i> Grades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'class_reports.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>class_reports.php">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>announcements.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] == 'parent'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'fee_balance.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>fee_balance.php">
                    <i class="fas fa-money-bill"></i> Fee Balance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'submit_payment.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>submit_payment.php">
                    <i class="fas fa-upload"></i> Submit Payment
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] == 'accountant'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>payments.php">
                    <i class="fas fa-money-bill"></i> School Fees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'verify_payments.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>verify_payments.php">
                    <i class="fas fa-check-circle"></i> Verify Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'salaries.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>salaries.php">
                    <i class="fas fa-wallet"></i> Salaries
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] == 'librarian'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>books.php">
                    <i class="fas fa-book"></i> Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'library_records.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>library_records.php">
                    <i class="fas fa-list"></i> Student Records
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] == 'student'): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'my_grades.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>my_grades.php">
                    <i class="fas fa-chart-line"></i> My Grades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>announcements.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item mobile-nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            
            <li class="nav-item mobile-nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>profile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            
            <li class="nav-item mobile-nav-item mobile-more">
                <a class="nav-link" href="#" onclick="event.preventDefault(); openMobileMenu();">
                    <i class="fas fa-bars"></i>
                    <span>Menu</span>
                </a>
            </li>
        </ul>
    </div>
</nav>