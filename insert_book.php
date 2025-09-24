<?php
require_once 'config.php';
requireLibrarian();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $isbn = sanitizeInput($_POST['isbn']);
        $publisher = sanitizeInput($_POST['publisher']);
        $publication_year = $_POST['publication_year'] ?? null;
        $category = sanitizeInput($_POST['category']);
        $total_copies = (int)($_POST['total_copies'] ?? 1);
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
        
        
        if (!empty($isbn)) {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetch()) {
                $errors[] = "Book with this ISBN already exists";
            }
        }
        
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
        }
        
        
        $stmt = $pdo->prepare("
            INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_copies, available_copies, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $isbn ?: null,
            $title,
            $author,
            $publisher ?: null,
            $publication_year ?: null,
            $category ?: null,
            $total_copies,
            $total_copies, 
            $description ?: null
        ]);
        
        $bookId = $pdo->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'message' => 'Book added successfully',
            'book_id' => $bookId
        ]);
        
    } catch (PDOException $e) {
        error_log("Insert book error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to add book'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
}
?>