document.getElementById("registerForm").addEventListener("submit", function(event) {
    const username = document.getElementById("registerUsername").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    // Name field validation
    if (username === "") {
        event.preventDefault(); 
        alert("Name is required.");
        return;
    }

    // Email field validation
    if (email === "") {
        event.preventDefault(); 
        alert("Email is empty.");
        return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        event.preventDefault(); 
        alert("Email format is invalid.");
        return;
    }

    // Password field validation
    if (password === "") {
        event.preventDefault(); 
        alert("Password is empty.");
        return;
    }

    if (password.length < 6) {
        event.preventDefault(); 
        alert("Password must be at least 6 characters long.");
        return;
    }


});