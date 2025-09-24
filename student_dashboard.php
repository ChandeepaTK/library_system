<?php
require_once 'config.php';
requireStudent();

$pdo = getDBConnection();


$user_id = $_SESSION['user_id'];
$stats = [];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$stmt->execute([$user_id]);
$stats['borrowed_books'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'returned'");
$stmt->execute([$user_id]);
$stats['returned_books'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - School Library</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo"><h2>📚 School Library - Student</h2></div>
            <div class="nav-menu">
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="#" onclick="logout()" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
            <p>Explore and manage your library activities</p>
        </div>

        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <h3><?php echo $stats['borrowed_books']; ?></h3>
                    <p>Currently Borrowed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $stats['returned_books']; ?></h3>
                    <p>Books Returned</p>
                </div>
            </div>
        </div>

        
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('search')">Search Books</button>
            <button class="tab-button" onclick="showTab('myborrowing')">My Borrowings</button>
            <button class="tab-button" onclick="showTab('profile')">My Profile</button>
        </div>

        
        <div id="search-tab" class="tab-content active">
            <div class="tab-header">
                <h2>Search Books</h2>
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
                <select id="availability-filter" onchange="filterBooks()">
                    <option value="">All Books</option>
                    <option value="available">Available Only</option>
                </select>
            </div>

            <div id="books-list" class="content-list">
                
            </div>
        </div>

        
        <div id="myborrowing-tab" class="tab-content">
            <div class="tab-header">
                <h2>My Borrowings</h2>
            </div>
            
            <div class="search-container">
                <select id="borrowing-filter" onchange="filterMyBorrowings()">
                    <option value="">All Borrowings</option>
                    <option value="borrowed">Currently Borrowed</option>
                    <option value="returned">Returned</option>
                </select>
            </div>

            <div id="my-borrowings-list" class="content-list">
                
            </div>
        </div>

        
        <div id="profile-tab" class="tab-content">
            <div class="tab-header">
                <h2>My Profile</h2>
            </div>
            
            <div class="profile-container">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <span class="avatar-text"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></span>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                            <span class="user-type-badge">Student</span>
                        </div>
                    </div>
                    
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" rows="3" placeholder="Enter your address"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <div class="profile-card">
                    <h3>Change Password</h3>
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div id="bookDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('bookDetailsModal')">&times;</span>
            <div id="book-details-content">
                
            </div>
        </div>
    </div>

    <script src="student_dashboard.js"></script>
</body>
</html>