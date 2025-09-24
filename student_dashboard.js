
let currentTab = 'search';
let allBooks = [];
let myBorrowings = [];

document.addEventListener('DOMContentLoaded', function() {
    showTab('search'); 
    loadBooks();
    loadMyBorrowings();
    loadUserProfile();
    setupEventListeners();
});


function showTab(tabName, event = null) {
    currentTab = tabName;

    
    document.querySelectorAll('.tab-content').forEach(section => {
        section.classList.remove('active');
    });

   
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    
    if (event && event.target) {
        event.target.classList.add('active');
    } else {
        
        document.querySelectorAll('.tab-button').forEach(button => {
            if (button.textContent.toLowerCase().includes(tabName)) {
                button.classList.add('active');
            }
        });
    }

    
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    
    switch(tabName) {
        case 'search':
            loadBooks();
            break;
        case 'myborrowing':
            loadMyBorrowings();
            break;
        case 'profile':
            loadUserProfile();
            break;
    }
}


function searchBooks() {
    const searchTerm = document.getElementById('book-search').value;
    const category = document.getElementById('category-filter').value;
    const availability = document.getElementById('availability-filter').value;

    const params = new URLSearchParams({
        action: 'search',
        search: searchTerm,
        category: category,
        availability: availability
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

function filterBooks() {
    searchBooks();
}

function displayBooks(books) {
    const container = document.getElementById('books-list');

    if (books.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No books found</h3>
                <p>Try adjusting your search criteria</p>
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
                <button class="btn btn-secondary btn-sm" onclick="showBookDetails(${book.id})">View Details</button>
                ${book.available_copies > 0 ? 
                    `<button class="btn btn-primary btn-sm" onclick="borrowBook(${book.id})">Borrow Book</button>` :
                    '<span class="meta-badge unavailable">Not Available</span>'
                }
            </div>
        </div>
    `).join('');

    container.innerHTML = booksHTML;
}

function showBookDetails(bookId) {
    const book = allBooks.find(b => b.id == bookId);
    if (!book) return;

    const content = `
        <h2>${escapeHtml(book.title)}</h2>
        <div class="book-details">
            <p><strong>Author:</strong> ${escapeHtml(book.author)}</p>
            <p><strong>Category:</strong> ${escapeHtml(book.category || 'N/A')}</p>
            <p><strong>Publisher:</strong> ${escapeHtml(book.publisher || 'N/A')}</p>
            <p><strong>Publication Year:</strong> ${book.publication_year || 'N/A'}</p>
            <p><strong>ISBN:</strong> ${escapeHtml(book.isbn || 'N/A')}</p>
            <p><strong>Available:</strong> ${book.available_copies}/${book.total_copies} copies</p>
            ${book.description ? `<p><strong>Description:</strong> ${escapeHtml(book.description)}</p>` : ''}
        </div>
        ${book.available_copies > 0 ? 
            `<button class="btn btn-primary" onclick="borrowBook(${book.id}); closeModal('bookDetailsModal');">Borrow This Book</button>` :
            '<p class="meta-badge unavailable">This book is currently not available</p>'
        }
    `;

    document.getElementById('book-details-content').innerHTML = content;
    document.getElementById('bookDetailsModal').style.display = 'block';
}

function borrowBook(bookId) {
    if (!confirm('Are you sure you want to borrow this book?')) return;

    const formData = new FormData();
    formData.append('action', 'borrow');
    formData.append('book_id', bookId);

    fetch('get_borrowings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadBooks();
            loadMyBorrowings();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while borrowing the book');
    });
}

// My Borrowings
function loadMyBorrowings() {
    const status = document.getElementById('borrowing-filter') ? document.getElementById('borrowing-filter').value : '';

    const params = new URLSearchParams({
        action: 'my_borrowings',
        status: status
    });

    showLoading('my-borrowings-list');

    fetch(`get_borrowings.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                myBorrowings = data.borrowings;
                displayMyBorrowings(data.borrowings);
            } else {
                showError('my-borrowings-list', data.message || 'Failed to load borrowings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('my-borrowings-list', 'An error occurred while loading borrowings');
        });
}

function filterMyBorrowings() {
    loadMyBorrowings();
}

function displayMyBorrowings(borrowings) {
    const container = document.getElementById('my-borrowings-list');

    if (borrowings.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No borrowing records found</h3>
                <p>You haven't borrowed any books yet</p>
            </div>
        `;
        return;
    }

    const borrowingsHTML = borrowings.map(borrowing => {
        const statusClass = borrowing.status;

        return `
            <div class="borrowing-item">
                <div class="item-info">
                    <h3>${escapeHtml(borrowing.title)}</h3>
                    <p><strong>Author:</strong> ${escapeHtml(borrowing.author)}</p>
                    <p><strong>Borrowed:</strong> ${formatDate(borrowing.borrow_date)}</p>
                    <p><strong>Due Date:</strong> ${formatDate(borrowing.due_date)}</p>
                    ${borrowing.return_date ? `<p><strong>Returned:</strong> ${formatDate(borrowing.return_date)}</p>` : ''}
                    <div class="item-meta">
                        <span class="meta-badge ${statusClass}">${borrowing.status.charAt(0).toUpperCase() + borrowing.status.slice(1)}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = borrowingsHTML;
}

// Profile management
function loadUserProfile() {
    fetch('update_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateProfileForm(data.user);
            } else {
                console.error('Failed to load profile:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading profile:', error);
        });
}

function populateProfileForm(user) {
    document.getElementById('fullName').value = user.full_name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('address').value = user.address || '';
}

function setupEventListeners() {
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateProfile();
    });

    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        changePassword();
    });
}

function updateProfile() {
    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('full_name', document.getElementById('fullName').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('address', document.getElementById('address').value);

    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating profile');
    });
}

function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert('New password and confirmation do not match');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully!');
            document.getElementById('passwordForm').reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while changing password');
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
    return unsafe
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