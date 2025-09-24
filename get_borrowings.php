<?php
require_once 'config.php';
requireLogin();



header('Content-Type: application/json');

$pdo = getDBConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';


switch ($action) {
    case 'my_borrowings':
        getMyBorrowings();
        break;
    case 'all':
        getAllBorrowings();
        break;
    case 'borrow':
        borrowBook();
        break;
    case 'return':
        returnBook();
        break;
    default:
        if (isStudent()) {
            getMyBorrowings();
        } else {
            getAllBorrowings();
        }
        break;
}

function getMyBorrowings() {
    global $pdo;

    requireStudent();

    $user_id = $_SESSION['user_id'];
    $status = $_GET['status'] ?? '';

    $sql = "
        SELECT b.*, bk.title, bk.author, bk.isbn
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ?
    ";
    $params = [$user_id];

    if (!empty($status)) {
        $sql .= " AND b.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY b.borrow_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['success' => true, 'borrowings' => $borrowings]);
    } catch (PDOException $e) {
        error_log("Get my borrowings error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load borrowings'], 500);
    }
}

function getAllBorrowings() {
    global $pdo;

    requireLibrarian();

    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';

    $sql = "
        SELECT b.*, bk.title, bk.author, bk.isbn, u.full_name, u.email
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        JOIN users u ON b.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($status)) {
        $sql .= " AND b.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (bk.title LIKE ? OR bk.author LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY b.borrow_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['success' => true, 'borrowings' => $borrowings]);
    } catch (PDOException $e) {
        error_log("Get all borrowings error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load borrowings'], 500);
    }
}

function borrowBook() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
    }

    $book_id = (int)($_POST['book_id'] ?? 0);

    if (isLibrarian() && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
    } else {
        requireStudent();
        $user_id = $_SESSION['user_id'];
    }

    if (!$book_id || !$user_id) {
        jsonResponse(['success' => false, 'message' => 'Book ID and User ID are required'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT title, available_copies FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'Book not found'], 404);
        }

        if ($book['available_copies'] <= 0) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'Book is not available for borrowing'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id, $book_id]);
        if ($stmt->fetch()) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'You already have this book borrowed'], 400);
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id]);
        $borrowedCount = $stmt->fetchColumn();

        if ($borrowedCount >= 5) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'You have reached maximum borrowing limit (5 books)'], 400);
        }

        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+' . BORROW_DURATION_DAYS . ' days'));

        $stmt = $pdo->prepare("
            INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status)
            VALUES (?, ?, ?, ?, 'borrowed')
        ");
        $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);

        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
        $stmt->execute([$book_id]);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Book "' . $book['title'] . '" borrowed successfully! Due date: ' . date('M j, Y', strtotime($due_date)),
            'due_date' => $due_date
        ]);

    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Borrow book error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to borrow book. Please try again.'], 500);
    }
}

function returnBook() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
    }

    $borrowing_id = (int)($_POST['borrowing_id'] ?? 0);

    if (!$borrowing_id) {
        jsonResponse(['success' => false, 'message' => 'Borrowing ID is required'], 400);
    }

    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare("
            SELECT b.*, bk.title 
            FROM borrowings b 
            JOIN books bk ON b.book_id = bk.id 
            WHERE b.id = ? AND b.status = 'borrowed'
        ");
        $stmt->execute([$borrowing_id]);
        $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$borrowing) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'Borrowing not found or already returned'], 404);
        }

        
        if (isStudent() && $borrowing['user_id'] != $_SESSION['user_id']) {
            $pdo->rollback();
            jsonResponse(['success' => false, 'message' => 'You can only return your own books'], 403);
        }

        
        $return_date = date('Y-m-d');
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET status = 'returned', return_date = ? 
            WHERE id = ?
        ");
        $stmt->execute([$return_date, $borrowing_id]);

        
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->execute([$borrowing['book_id']]);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Book "' . $borrowing['title'] . '" returned successfully!'
        ]);

    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Return book error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to return book. Please try again.'], 500);
    }
}
?>