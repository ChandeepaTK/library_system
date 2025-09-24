<?php
require_once 'config.php';
requireLibrarian();

$pdo = getDBConnection();


$stats = [];
$stats['total_books'] = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student'")->fetchColumn();
$stats['borrowed_books'] = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'borrowed'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - School Library</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo"><h2>📚 School Library - Librarian</h2></div>
            <div class="nav-menu">
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="#" onclick="logout()" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Librarian Dashboard</h1>
            <p>Manage your library system efficiently</p>
        </div>

        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_books']; ?></h3>
                    <p>Total Books</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Registered Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <h3><?php echo $stats['borrowed_books']; ?></h3>
                    <p>Currently Borrowed</p>
                </div>
            </div>
        </div>

        
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('books')">Manage Books</button>
            <button class="tab-button" onclick="showTab('users')">Manage Users</button>
            <button class="tab-button" onclick="showTab('borrowings')">Manage Borrowings</button>
        </div>

        
        <div id="books-tab" class="tab-content active">
            <div class="tab-header">
                <h2>Manage Books</h2>
                <button class="btn btn-primary" onclick="showAddBookModal()">Add New Book</button>
            </div>
            
            <div class="search-container">
                <input type="text" id="book-search" placeholder="Search books by title, author, or ISBN..." onkeyup="searchBooks()">
                <select id="category-filter" onchange="filterBooks()">
                    <option value="">All Categories</option>
                    <option value="Fiction">Fiction</option>
                    <option value="Science Fiction">Science Fiction</option>
                    <option value="Romance">Romance</option>
                    <option value="Mystery">Mystery</option>
                    <option value="Biography">Biography</option>
                    <option value="History">History</option>
                    <option value="Science">Science</option>
                </select>
            </div>

            <div id="books-list" class="content-list">
                
            </div>
        </div>

        
        <div id="users-tab" class="tab-content">
            <div class="tab-header">
                <h2>Manage Users</h2>
            </div>
            
            <div class="search-container">
                <input type="text" id="user-search" placeholder="Search users by name or email..." onkeyup="searchUsers()">
            </div>

            <div id="users-list" class="content-list">
                
            </div>
        </div>

        
        <div id="borrowings-tab" class="tab-content">
            <div class="tab-header">
                <h2>Manage Borrowings</h2>
            </div>
            
            <div class="search-container">
                <select id="borrowing-filter" onchange="filterBorrowings()">
                    <option value="">All Borrowings</option>
                    <option value="borrowed">Currently Borrowed</option>
                    <option value="returned">Returned</option>
                </select>
            </div>

            <div id="borrowings-list" class="content-list">
            
            </div>
        </div>
    </div>

    
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addBookModal')">&times;</span>
            <h2>Add New Book</h2>
            <form id="addBookForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="bookTitle">Title *</label>
                        <input type="text" id="bookTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="bookAuthor">Author *</label>
                        <input type="text" id="bookAuthor" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bookISBN">ISBN</label>
                        <input type="text" id="bookISBN">
                    </div>
                    <div class="form-group">
                        <label for="bookCategory">Category</label>
                        <select id="bookCategory">
                            <option value="Fiction">Fiction</option>
                            <option value="Science Fiction">Science Fiction</option>
                            <option value="Romance">Romance</option>
                            <option value="Mystery">Mystery</option>
                            <option value="Biography">Biography</option>
                            <option value="History">History</option>
                            <option value="Science">Science</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bookCopies">Total Copies *</label>
                        <input type="number" id="bookCopies" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="bookYear">Publication Year</label>
                        <input type="number" id="bookYear" min="1900" max="2024">
                    </div>
                </div>
                <div class="form-group">
                    <label for="bookPublisher">Publisher</label>
                    <input type="text" id="bookPublisher">
                </div>
                <div class="form-group">
                    <label for="bookDescription">Description</label>
                    <textarea id="bookDescription" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Book</button>
            </form>
        </div>
    </div>

    <script src="librarian_dashboard.js"></script>
</body>
</html>
