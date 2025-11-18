<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'librarian'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

if ($_POST && isset($_POST['add_book'])) {
    $stmt = $db->prepare("INSERT INTO books (title, author, isbn, category, publisher, publication_year) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_POST['title'], $_POST['author'], $_POST['isbn'], $_POST['category'], $_POST['publisher'], $_POST['publication_year']])) {
        $success = "Book added successfully!";
    }
}

$books = $db->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll();
$categories = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-book me-3"></i>Library Catalog</h1>
                    <button class="btn btn-primary morph-btn neon-green" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus me-2"></i>Add Book
                    </button>
                </div>
            </div>
            
            <!-- Library Stats -->
            <div class="stats-grid mb-4">
                <div class="advanced-card stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Total Books</h6>
                            <h2 class="mb-0"><?= count($books) ?></h2>
                        </div>
                        <i class="fas fa-books fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Categories</h6>
                            <h2 class="mb-0"><?= count($categories) ?></h2>
                        </div>
                        <i class="fas fa-tags fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">New This Month</h6>
                            <h2 class="mb-0"><?= count(array_filter($books, fn($b) => date('Y-m', strtotime($b['created_at'])) == date('Y-m'))) ?></h2>
                        </div>
                        <i class="fas fa-plus-circle fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Recent Year</h6>
                            <h2 class="mb-0"><?= count(array_filter($books, fn($b) => $b['publication_year'] >= date('Y') - 5)) ?></h2>
                        </div>
                        <i class="fas fa-calendar fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="card advanced-card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="search-container position-relative">
                                <input type="text" class="form-control search-input" placeholder="Search books..." id="bookSearch">
                                <i class="fas fa-search position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category'] ?>"><?= $cat['category'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100 morph-btn" onclick="scanBarcode()">
                                <i class="fas fa-barcode me-2"></i>Scan ISBN
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Books Grid -->
            <div class="row" id="booksGrid">
                <?php foreach ($books as $book): ?>
                <div class="col-md-6 col-lg-4 mb-4 book-item" data-category="<?= $book['category'] ?>">
                    <div class="card advanced-card book-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="book-icon">
                                    <i class="fas fa-book fa-3x text-primary"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View Details</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <h5 class="card-title mb-2"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="text-muted mb-2">by <?= htmlspecialchars($book['author']) ?></p>
                            
                            <div class="book-details mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Category</small>
                                    <span class="badge bg-primary"><?= $book['category'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Year</small>
                                    <span class="badge bg-secondary"><?= $book['publication_year'] ?></span>
                                </div>
                                <?php if ($book['isbn']): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">ISBN</small>
                                    <small class="font-monospace"><?= $book['isbn'] ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary morph-btn flex-fill" onclick="viewBook(<?= $book['id'] ?>)">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                <button class="btn btn-sm btn-outline-success morph-btn flex-fill" onclick="issueBook(<?= $book['id'] ?>)">
                                    <i class="fas fa-hand-holding me-1"></i>Issue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($books)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                <h5>No Books in Catalog</h5>
                <p class="text-muted">Start building your library by adding books.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade advanced-modal" id="addBookModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-book-medical me-2"></i>Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="floating-label">
                                <input type="text" name="title" placeholder=" " required>
                                <label>Book Title</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="floating-label">
                                <input type="text" name="isbn" placeholder=" ">
                                <label>ISBN</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="floating-label">
                                <input type="text" name="author" placeholder=" " required>
                                <label>Author</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="floating-label">
                                <input type="text" name="publisher" placeholder=" ">
                                <label>Publisher</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="floating-label">
                                <input type="text" name="category" placeholder=" " list="categories" required>
                                <label>Category</label>
                                <datalist id="categories">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category'] ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="floating-label">
                                <input type="number" name="publication_year" placeholder=" " min="1800" max="<?= date('Y') ?>">
                                <label>Publication Year</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_book" class="btn btn-primary morph-btn">
                        <i class="fas fa-save me-2"></i>Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('bookSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    
    function filterBooks() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryValue = categoryFilter.value;
        const books = document.querySelectorAll('.book-item');
        
        books.forEach(book => {
            const text = book.textContent.toLowerCase();
            const category = book.dataset.category;
            const searchMatch = !searchTerm || text.includes(searchTerm);
            const categoryMatch = !categoryValue || category === categoryValue;
            
            book.style.display = (searchMatch && categoryMatch) ? 'block' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterBooks);
    categoryFilter.addEventListener('change', filterBooks);
});

function viewBook(id) {
    window.advancedSMS.showAdvancedNotification('Book Details', 'Loading book information...', 'info');
}

function issueBook(id) {
    window.advancedSMS.showAdvancedNotification('Issue Book', 'Opening issue form...', 'info');
}

function scanBarcode() {
    window.advancedSMS.showAdvancedNotification('Barcode Scanner', 'Barcode scanning feature coming soon!', 'info');
}

// Add hover effects
document.querySelectorAll('.book-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px) rotateY(5deg)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) rotateY(0)';
    });
});
</script>

<style>
.book-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.9));
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    perspective: 1000px;
}

.book-card:hover {
    box-shadow: 0 25px 50px rgba(102, 126, 234, 0.3);
}

.book-icon {
    animation: bookFloat 3s ease-in-out infinite;
}

@keyframes bookFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.book-details {
    background: rgba(102, 126, 234, 0.05);
    border-radius: 10px;
    padding: 15px;
    border-left: 4px solid #667eea;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 4px;
}
</style>

<?php include '../includes/footer.php'; ?>