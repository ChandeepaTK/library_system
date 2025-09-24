// Global variables
let currentTab = 'books';
let allBooks = [];
let allUsers = [];
let allBorrowings = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Show books tab by default
    showTab('books');

    // Load initial data
    loadBooks();
    loadUsers();
    loadBorrowings();

    // Set up form event listeners
    setupEventListeners();
});

// Tab management - FIXED VERSION
function showTab(tabName, clickedButton = null) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    // Show selected tab
    const targetTab = document.getElementById(tabName + '-tab');
    if (targetTab) {
        targetTab.classList.add('active');
    }

    // Add active class to clicked button (if available)
    if (clickedButton) {
        clickedButton.classList.add('active');
    } else {
        const tabButton = document.querySelector(`[onclick*="showTab('${tabName}')"]`);
        if (tabButton) {
            tabButton.classList.add('active');
        }
    }

    currentTab = tabName;

    // Load data based on tab
    switch(tabName) {
        case 'books':
            loadBooks();
            break;
        case 'users':
            loadUsers();
            break;
        case 'borrowings':
            loadBorrowings();
            break;
    }
}

// Book management
function loadBooks() {
    showLoading('books-list');
    
    fetch('get_books.php?action=all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allBooks = data.books;
                displayBooks(data.books);
            } else {
                showError('books-list', data.message || 'Failed to load books');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('books-list', 'An error occurred while loading books');
        });
}

function searchBooks() {
    const searchTerm = document.getElementById('book-search').value;
    const category = document.getElementById('category-filter').value;
    
    const params = new URLSearchParams({
        action: 'search',
        search: searchTerm,
        category: category
    });
    
    showLoading('books-list');
    
    fetch(`get_books.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allBooks = data.books;
                displayBooks(data.books);
            } else {
                showError('books-list', data.message || 'Failed to load books');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('books-list', 'An error occurred while loading books');
        });
}

function filterBooks() {
    searchBooks();
}

function displayBooks(books) {
    const container = document.getElementById('books-list');
    
    if (books.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No books found</h3>
                <p>Try adjusting your search criteria or add new books</p>
            </div>
        `;
        return;
    }
    
    const booksHTML = books.map(book => `
        <div class="book-item">
            <div class="item-info">
                <h3>${escapeHtml(book.title)}</h3>
                <p><strong>Author:</strong> ${escapeHtml(book.author)}</p>
                <p><strong>Category:</strong> ${escapeHtml(book.category || 'N/A')}</p>
                <p><strong>Publisher:</strong> ${escapeHtml(book.publisher || 'N/A')}</p>
                <div class="item-meta">
                    <span class="meta-badge ${book.available_copies > 0 ? 'available' : 'unavailable'}">
                        ${book.available_copies}/${book.total_copies} Available
                    </span>
                    ${book.isbn ? `<span class="meta-badge">ISBN: ${escapeHtml(book.isbn)}</span>` : ''}
                </div>
            </div>
            <div class="item-actions">
                <button class="btn btn-secondary btn-sm" onclick="editBook(${book.id})">
                    Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteBook(${book.id})">
                    Delete
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = booksHTML;
}

function showAddBookModal() {
    document.getElementById('addBookForm').reset();
    document.getElementById('addBookModal').style.display = 'block';
}

function editBook(bookId) {
    const book = allBooks.find(b => b.id == bookId);
    if (!book) return;
    
    
    document.getElementById('bookTitle').value = book.title;
    document.getElementById('bookAuthor').value = book.author;
    document.getElementById('bookISBN').value = book.isbn || '';
    document.getElementById('bookCategory').value = book.category || '';
    document.getElementById('bookCopies').value = book.total_copies;
    document.getElementById('bookYear').value = book.publication_year || '';
    document.getElementById('bookPublisher').value = book.publisher || '';
    document.getElementById('bookDescription').value = book.description || '';
    
    
    document.getElementById('addBookForm').onsubmit = function(e) {
        e.preventDefault();
        updateBook(bookId);
    };
    
    document.querySelector('#addBookModal h2').textContent = 'Edit Book';
    document.querySelector('#addBookModal button[type="submit"]').textContent = 'Update Book';
    
    document.getElementById('addBookModal').style.display = 'block';
}

function deleteBook(bookId) {
    const book = allBooks.find(b => b.id == bookId);
    if (!book) return;
    
    if (!confirm(`Are you sure you want to delete "${book.title}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('book_id', bookId);
    
    fetch('delete_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadBooks();
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the book');
    });
}

function addBook() {
    const formData = new FormData();
    formData.append('title', document.getElementById('bookTitle').value);
    formData.append('author', document.getElementById('bookAuthor').value);
    formData.append('isbn', document.getElementById('bookISBN').value);
    formData.append('category', document.getElementById('bookCategory').value);
    formData.append('total_copies', document.getElementById('bookCopies').value);
    formData.append('publication_year', document.getElementById('bookYear').value);
    formData.append('publisher', document.getElementById('bookPublisher').value);
    formData.append('description', document.getElementById('bookDescription').value);
    
    fetch('insert_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('addBookModal');
            loadBooks();
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the book');
    });
}

function updateBook(bookId) {
    const formData = new FormData();
    formData.append('book_id', bookId);
    formData.append('title', document.getElementById('bookTitle').value);
    formData.append('author', document.getElementById('bookAuthor').value);
    formData.append('isbn', document.getElementById('bookISBN').value);
    formData.append('category', document.getElementById('bookCategory').value);
    formData.append('total_copies', document.getElementById('bookCopies').value);
    formData.append('publication_year', document.getElementById('bookYear').value);
    formData.append('publisher', document.getElementById('bookPublisher').value);
    formData.append('description', document.getElementById('bookDescription').value);
    
    fetch('update_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('addBookModal');
            loadBooks();
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the book');
    });
}

// User management
function loadUsers() {
    showLoading('users-list');
    
    fetch('get_users.php?action=all')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }
            });
        })
        .then(data => {
            if (data.success) {
                allUsers = data.users;
                displayUsers(data.users);
            } else {
                showError('users-list', data.message || 'Failed to load users');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('users-list', 'An error occurred while loading users: ' + error.message);
        });
}

function searchUsers() {
    const searchTerm = document.getElementById('user-search').value;
    
    const params = new URLSearchParams({
        action: 'search',
        search: searchTerm
    });
    
    showLoading('users-list');
    
    fetch(`get_users.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }
            });
        })
        .then(data => {
            if (data.success) {
                allUsers = data.users;
                displayUsers(data.users);
            } else {
                showError('users-list', data.message || 'Failed to load users');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('users-list', 'An error occurred while loading users: ' + error.message);
        });
}

function displayUsers(users) {
    const container = document.getElementById('users-list');
    
    if (users.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No users found</h3>
                <p>No student accounts found in the system</p>
            </div>
        `;
        return;
    }
    
    const usersHTML = users.map(user => `
        <div class="user-item">
            <div class="item-info">
                <h3>${escapeHtml(user.full_name)}</h3>
                <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                <p><strong>Username:</strong> ${escapeHtml(user.username)}</p>
                <p><strong>Joined:</strong> ${formatDate(user.created_at)}</p>
                <div class="item-meta">
                    <span class="meta-badge ${user.status === 'active' ? 'available' : 'unavailable'}">
                        ${user.status}
                    </span>
                    <span class="meta-badge">
                        ${user.stats.borrowed} Borrowed
                    </span>
                    <span class="meta-badge">
                        ${user.stats.returned} Returned
                    </span>
                    ${user.stats.overdue > 0 ? 
                        `<span class="meta-badge overdue">${user.stats.overdue} Overdue</span>` : ''
                    }
                    ${user.stats.total_fine > 0 ? 
                        `<span class="meta-badge overdue">${parseFloat(user.stats.total_fine).toFixed(2)} Fine</span>` : ''
                    }
                </div>
            </div>
            <div class="item-actions">
                <button class="btn btn-secondary btn-sm" onclick="viewUserDetails(${user.id})">
                    View Details
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                    Delete
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = usersHTML;
}

function viewUserDetails(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;
    
    alert(`User Details:\n\nName: ${user.full_name}\nEmail: ${user.email}\nUsername: ${user.username}\nStatus: ${user.status}\n\nBorrowing Statistics:\nCurrently Borrowed: ${user.stats.borrowed}\nReturned Books: ${user.stats.returned}\nOverdue Books: ${user.stats.overdue}\nTotal Fines: ${parseFloat(user.stats.total_fine).toFixed(2)}`);
}

function deleteUser(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;
    
    if (!confirm(`Are you sure you want to delete user "${user.full_name}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    
    fetch('delete_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadUsers();
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the user');
    });
}

// Borrowing management
function loadBorrowings() {
    const status = document.getElementById('borrowing-filter') ? document.getElementById('borrowing-filter').value : '';
    
    const params = new URLSearchParams({
        action: 'all',
        status: status
    });
    
    showLoading('borrowings-list');
    
    fetch(`get_borrowings.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allBorrowings = data.borrowings;
                displayBorrowings(data.borrowings);
            } else {
                showError('borrowings-list', data.message || 'Failed to load borrowings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('borrowings-list', 'An error occurred while loading borrowings');
        });
}

function filterBorrowings() {
    loadBorrowings();
}

function displayBorrowings(borrowings) {
    const container = document.getElementById('borrowings-list');
    
    if (borrowings.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No borrowing records found</h3>
                <p>No borrowing activities found</p>
            </div>
        `;
        return;
    }
    
    const borrowingsHTML = borrowings.map(borrowing => {
        const statusClass = borrowing.status === 'overdue' ? 'overdue' : borrowing.status;
        const dueDate = new Date(borrowing.due_date);
        const isOverdue = borrowing.status === 'borrowed' && dueDate < new Date();
        
        return `
            <div class="borrowing-item">
                <div class="item-info">
                    <h3>${escapeHtml(borrowing.title)}</h3>
                    <p><strong>Student:</strong> ${escapeHtml(borrowing.full_name)} (${escapeHtml(borrowing.email)})</p>
                    <p><strong>Author:</strong> ${escapeHtml(borrowing.author)}</p>
                    <p><strong>Borrowed:</strong> ${formatDate(borrowing.borrow_date)}</p>
                    <p><strong>Due Date:</strong> ${formatDate(borrowing.due_date)}</p>
                    ${borrowing.return_date ? `<p><strong>Returned:</strong> ${formatDate(borrowing.return_date)}</p>` : ''}
                    <div class="item-meta">
                        <span class="meta-badge ${statusClass}">
                            ${borrowing.status.charAt(0).toUpperCase() + borrowing.status.slice(1)}
                        </span>
                        ${borrowing.fine_amount > 0 ? 
                            `<span class="meta-badge overdue">Fine: ${parseFloat(borrowing.fine_amount).toFixed(2)}</span>` : 
                            ''
                        }
                        ${isOverdue && borrowing.calculated_fine > 0 ? 
                            `<span class="meta-badge overdue">Current Fine: ${parseFloat(borrowing.calculated_fine).toFixed(2)}</span>` : 
                            ''
                        }
                    </div>
                </div>
                <div class="item-actions">
                    ${borrowing.status === 'borrowed' || borrowing.status === 'overdue' ? 
                        `<button class="btn btn-success btn-sm" onclick="returnBook(${borrowing.id})">
                            Process Return
                        </button>` : 
                        '<span class="meta-badge">Returned</span>'
                    }
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = borrowingsHTML;
}

function returnBook(borrowingId) {
    const borrowing = allBorrowings.find(b => b.id == borrowingId);
    if (!borrowing) return;
    
    const fine = borrowing.calculated_fine || 0;
    let confirmMessage = `Process return for "${borrowing.title}" by ${borrowing.full_name}?`;
    
    if (fine > 0) {
        confirmMessage += `\n\nFine amount: ${parseFloat(fine).toFixed(2)}`;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'return');
    formData.append('borrowing_id', borrowingId);
    
    fetch('get_borrowings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadBorrowings();
            loadBooks(); 
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the return');
    });
}


function setupEventListeners() {
   
    document.getElementById('addBookForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addBook();
    });
}

function showLoading(elementId) {
    document.getElementById(elementId).innerHTML = '<div class="loading">Loading...</div>';
}

function showError(elementId, message) {
    document.getElementById(elementId).innerHTML = `
        <div class="empty-state">
            <h3>Error</h3>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    
   
    if (modalId === 'addBookModal') {
        document.getElementById('addBookForm').reset();
        document.getElementById('addBookForm').onsubmit = function(e) {
            e.preventDefault();
            addBook();
        };
        document.querySelector('#addBookModal h2').textContent = 'Add New Book';
        document.querySelector('#addBookModal button[type="submit"]').textContent = 'Add Book';
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}


window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}