<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'librarian') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

if ($_POST && isset($_POST['add_record'])) {
    $sql = "INSERT INTO student_library_records (student_id, book_title, author, issue_date, due_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$_POST['student_id'], $_POST['book_title'], $_POST['author'], $_POST['issue_date'], $_POST['due_date'], $_SESSION['user_id']])) {
        $success = "Library record added successfully!";
    } else {
        $error = "Failed to add record.";
    }
}

if ($_POST && isset($_POST['return_book'])) {
    $sql = "UPDATE student_library_records SET status = 'returned', return_date = ?, fine_amount = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$_POST['return_date'], $_POST['fine_amount'], $_POST['record_id']])) {
        $success = "Book returned successfully!";
    }
}

// Get all students for dropdown
$students = $db->query("SELECT s.id, s.student_id, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.status = 'active'")->fetchAll();

// Get all books for autocomplete
$books = $db->query("SELECT id, title, author FROM books ORDER BY title")->fetchAll();

// Get library records
$records = $db->query("SELECT lr.*, s.student_id, u.name as student_name FROM student_library_records lr 
                       JOIN students s ON lr.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       ORDER BY lr.created_at DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Library Records</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                    <i class="fas fa-plus"></i> Add Record
                </button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Fine</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record['student_name']) ?><br><small><?= $record['student_id'] ?></small></td>
                            <td><?= htmlspecialchars($record['book_title']) ?></td>
                            <td><?= htmlspecialchars($record['author']) ?></td>
                            <td><?= date('M d, Y', strtotime($record['issue_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                            <td><?= $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : '-' ?></td>
                            <td>$<?= number_format($record['fine_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $record['status'] == 'returned' ? 'success' : ($record['status'] == 'overdue' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($record['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($record['status'] == 'issued'): ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#returnModal<?= $record['id'] ?>">
                                    Return
                                </button>
                                
                                <!-- Return Modal -->
                                <div class="modal fade" id="returnModal<?= $record['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Return Book</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Return Date</label>
                                                        <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Fine Amount</label>
                                                        <input type="number" name="fine_amount" class="form-control" step="0.01" value="0">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="return_book" class="btn btn-primary">Return Book</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Library Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" id="studentSearch" class="form-control" placeholder="Type student name or ID..." autocomplete="off">
                        <input type="hidden" name="student_id" id="studentId" required>
                        <div id="studentSuggestions" class="list-group position-absolute" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book Title</label>
                        <input type="text" id="bookSearch" name="book_title" class="form-control" placeholder="Type book title..." autocomplete="off" required>
                        <div id="bookSuggestions" class="list-group position-absolute" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" id="authorInput" name="author" class="form-control" readonly style="background: #e9ecef;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_record" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const students = <?= json_encode($students) ?>;
const books = <?= json_encode($books) ?>;
const searchInput = document.getElementById('studentSearch');
const studentIdInput = document.getElementById('studentId');
const suggestions = document.getElementById('studentSuggestions');
const bookSearch = document.getElementById('bookSearch');
const authorInput = document.getElementById('authorInput');
const bookSuggestions = document.getElementById('bookSuggestions');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    
    if (query.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    
    const filtered = students.filter(s => 
        s.name.toLowerCase().includes(query) || 
        s.student_id.toLowerCase().includes(query)
    );
    
    if (filtered.length > 0) {
        suggestions.innerHTML = filtered.map(s => 
            `<a href="#" class="list-group-item list-group-item-action" data-id="${s.id}" data-name="${s.name}" data-sid="${s.student_id}">
                ${s.name} <small class="text-muted">(${s.student_id})</small>
            </a>`
        ).join('');
        suggestions.style.display = 'block';
    } else {
        suggestions.innerHTML = '<div class="list-group-item">No students found</div>';
        suggestions.style.display = 'block';
    }
});

suggestions.addEventListener('click', function(e) {
    e.preventDefault();
    const item = e.target.closest('a');
    if (item) {
        searchInput.value = item.dataset.name + ' (' + item.dataset.sid + ')';
        studentIdInput.value = item.dataset.id;
        suggestions.style.display = 'none';
    }
});

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
    }
    if (!bookSearch.contains(e.target) && !bookSuggestions.contains(e.target)) {
        bookSuggestions.style.display = 'none';
    }
});

// Book autocomplete
bookSearch.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    
    if (query.length < 2) {
        bookSuggestions.style.display = 'none';
        authorInput.value = '';
        return;
    }
    
    const filtered = books.filter(b => b.title.toLowerCase().includes(query));
    
    if (filtered.length > 0) {
        bookSuggestions.innerHTML = filtered.map(b => 
            `<a href="#" class="list-group-item list-group-item-action" data-title="${b.title}" data-author="${b.author || ''}">
                ${b.title} <small class="text-muted">by ${b.author || 'Unknown'}</small>
            </a>`
        ).join('');
        bookSuggestions.style.display = 'block';
    } else {
        bookSuggestions.innerHTML = '<div class="list-group-item">No books found - You can type a new book title</div>';
        bookSuggestions.style.display = 'block';
    }
});

bookSuggestions.addEventListener('click', function(e) {
    e.preventDefault();
    const item = e.target.closest('a');
    if (item) {
        bookSearch.value = item.dataset.title;
        authorInput.value = item.dataset.author;
        bookSuggestions.style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>