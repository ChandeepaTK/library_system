<?php
require_once 'config.php';
requireLibrarian();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        $book_id = (int)($_POST['book_id'] ?? 0);
        
        if (!$book_id) {
            jsonResponse(['success' => false, 'message' => 'Book ID is required'], 400);
        }
        
        
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            jsonResponse(['success' => false, 'message' => 'Book not found'], 404);
        }
        
        
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $isbn = sanitizeInput($_POST['isbn']);
        $publisher = sanitizeInput($_POST['publisher']);
        $publication_year = $_POST['publication_year'] ?? null;
        $category = sanitizeInput($_POST['category']);
        $total_copies = (int)($_POST['total_copies'] ?? $book['total_copies']);
        $description = sanitizeInput($_POST['description']);
        
        
        $errors = [];
        
        if (empty($title)) {
            $errors[] = "Title is required";
        }
        
        if (empty($author)) {
            $errors[] = "Author is required";
        }
        
        if ($total_copies < 1) {
            $errors[] = "Total copies must be at least 1";
        }
        
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = ? AND status = 'borrowed'");
        $stmt->execute([$book_id]);
        $borrowedCount = $stmt->fetchColumn();
        
        if ($total_copies < $borrowedCount) {
            $errors[] = "Cannot reduce total copies below currently borrowed count ($borrowedCount)";
        }
        
        
        if (!empty($isbn) && $isbn !== $book['isbn']) {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
            $stmt->execute([$isbn, $book_id]);
            if ($stmt->fetch()) {
                $errors[] = "Book with this ISBN already exists";
            }
        }
        
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
        }
        
        
        $available_copies = $total_copies - $borrowedCount;
        
        
        $stmt = $pdo->prepare("
            UPDATE books 
            SET isbn = ?, title = ?, author = ?, publisher = ?, publication_year = ?, 
                category = ?, total_copies = ?, available_copies = ?, description = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $isbn ?: null,
            $title,
            $author,
            $publisher ?: null,
            $publication_year ?: null,
            $category ?: null,
            $total_copies,
            $available_copies,
            $description ?: null,
            $book_id
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Book updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update book error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update book'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
}
?>