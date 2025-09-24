document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById("loginForm");

    form.addEventListener("submit", function(event) {
        const email = document.getElementById("loginEmail").value.trim();
        const password = document.getElementById("loginPassword").value;
        const userType = document.getElementById("userType").value;

        let isValid = true;

        // Email validation
        if (email === "") {
            event.preventDefault();
            alert("Email is required.");
            isValid = false;
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                event.preventDefault();
                alert("Please enter a valid email address.");
                isValid = false;
            }
        }

        // Password validation
        if (password === "" && isValid) {
            event.preventDefault();
            alert("Password is required.");
            isValid = false;
        } else if (password.length < 6 && isValid) {
            event.preventDefault();
            alert("Password must be at least 6 characters long.");
            isValid = false;
        }

        // User type validation
        if (!userType && isValid) {
            event.preventDefault();
            alert("Please select a user type.");
            isValid = false;
        }

        // Success message before normal submit
        if (isValid) {
            alert("Login details look good. Submitting...");
        }
    });
});