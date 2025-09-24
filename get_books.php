<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();


$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search':
        searchBooks();
        break;
    case 'get':
        getBookById();
        break;
    case 'all':
        getAllBooks();
        break;
    default:
        getAllBooks();
        break;
}

function searchBooks() {
    global $pdo;
    
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $availability = $_GET['availability'] ?? '';
    
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($availability === 'available') {
        $sql .= " AND available_copies > 0";
    }
    
    $sql .= " ORDER BY title ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'books' => $books]);
}

function getBookById() {
    global $pdo;
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Book ID required'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        jsonResponse(['success' => false, 'message' => 'Book not found'], 404);
    }
    
    jsonResponse(['success' => true, 'book' => $book]);
}

function getAllBooks() {
    global $pdo;
    
    $page = $_GET['page'] ?? 1;
    $limit = BOOKS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    
    $countStmt = $pdo->query("SELECT COUNT(*) FROM books");
    $totalBooks = $countStmt->fetchColumn();
    
    
    $stmt = $pdo->prepare("
        SELECT * FROM books 
        ORDER BY title ASC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $books = $stmt->fetchAll();
    
    $totalPages = ceil($totalBooks / $limit);
    
    jsonResponse([
        'success' => true,
        'books' => $books,
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => $totalPages,
            'total_books' => $totalBooks,
            'books_per_page' => $limit
        ]
    ]);
}
?>