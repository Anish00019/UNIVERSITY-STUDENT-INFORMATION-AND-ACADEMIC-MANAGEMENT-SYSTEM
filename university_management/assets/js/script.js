/* =====================================================================
   UniSIMS - Client-side script
   ---------------------------------------------------------------------
   1. Sidebar toggle (mobile)
   2. Alert messages (close button + auto hide)
   3. Confirmation dialogs (links with data-confirm)
   4. Show / hide password
   5. Input helpers (uppercase register number, digits-only phone)
   6. Quick table filter (XML view)
   7. Form validation (login, register and student forms)
   Note: PHP validates everything again on the server - JavaScript
   validation only gives the user faster feedback.
   ===================================================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* ---------- 1. Sidebar toggle ---------- */
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var menuToggle = document.getElementById('menuToggle');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    /* ---------- 2. Alerts ---------- */
    function hideAlert(alertBox) {
        alertBox.classList.add('fade-out');
        setTimeout(function () { alertBox.remove(); }, 300);
    }

    document.querySelectorAll('.alert').forEach(function (alertBox) {
        var closeBtn = alertBox.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () { hideAlert(alertBox); });
        }
        // Success messages disappear automatically after 5 seconds.
        if (alertBox.classList.contains('alert-success')) {
            setTimeout(function () { hideAlert(alertBox); }, 5000);
        }
    });

    /* ---------- 3. Confirmation dialogs ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (!confirm(element.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    /* ---------- 4. Show / hide password ---------- */
    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = button.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.style.color = show ? '#1e5bb8' : '';
        });
    });

    /* ---------- 5. Input helpers ---------- */
    var regInput = document.getElementById('register_number');
    if (regInput) {
        regInput.addEventListener('input', function () {
            regInput.value = regInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }

    var phoneInput = document.getElementById('contact_number');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    /* ---------- 6. Quick table filter ---------- */
    document.querySelectorAll('[data-table-filter]').forEach(function (input) {
        var table = document.getElementById(input.getAttribute('data-table-filter'));
        if (!table) return;

        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            var visible = 0;
            table.querySelectorAll('tbody tr:not(.no-results)').forEach(function (row) {
                var match = row.textContent.toLowerCase().indexOf(term) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            var noResults = table.querySelector('.no-results');
            if (noResults) noResults.hidden = visible > 0;
        });
    });

    /* ---------- 7. Form validation ---------- */

    // Each rule returns an error message, or '' when the value is valid.
    var rules = {
        login: {
            username: function (v) { return v === '' ? 'Please enter your username or email.' : ''; },
            password: function (v) { return v === '' ? 'Please enter your password.' : ''; }
        },
        register: {
            full_name: function (v) {
                if (v === '') return 'Full name is required.';
                return /^[A-Za-z][A-Za-z .']{2,99}$/.test(v) ? '' : 'Use 3-100 letters and spaces only.';
            },
            username: function (v) {
                if (v === '') return 'Username is required.';
                return /^[A-Za-z0-9_]{4,30}$/.test(v) ? '' : '4-30 characters: letters, digits or underscore.';
            },
            email: validateEmail,
            password: function (v) {
                if (v === '') return 'Password is required.';
                if (v.length < 8) return 'Password must be at least 8 characters.';
                if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) return 'Password must contain letters and numbers.';
                return '';
            },
            confirm_password: function (v, form) {
                if (v === '') return 'Please confirm your password.';
                return v === form.elements.password.value ? '' : 'Passwords do not match.';
            }
        },
        student: {
            register_number: function (v) {
                if (v === '') return 'Register number is required.';
                return /^[A-Za-z0-9]{5,20}$/.test(v) ? '' : 'Use 5-20 letters and digits only (e.g. BCA23001).';
            },
            student_name: function (v) {
                if (v === '') return 'Student name is required.';
                return /^[A-Za-z][A-Za-z .']{2,99}$/.test(v) ? '' : 'Name must be 3-100 characters (letters, spaces, dots only).';
            },
            course: function (v) { return v === '' ? 'Please select a course.' : ''; },
            semester: function (v) { return v === '' ? 'Please select a semester.' : ''; },
            email: validateEmail,
            contact_number: function (v) {
                if (v === '') return 'Contact number is required.';
                return /^[6-9][0-9]{9}$/.test(v) ? '' : 'Enter a valid 10-digit mobile number starting with 6-9.';
            }
        }
    };

    function validateEmail(v) {
        if (v === '') return 'Email ID is required.';
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? '' : 'Please enter a valid email address.';
    }

    // Show or clear the error message under a field.
    function showError(field, message) {
        var group = field.closest('.form-group');
        if (!group) return;
        var errorBox = group.querySelector('.field-error');
        group.classList.toggle('has-error', message !== '');
        if (errorBox) errorBox.textContent = message;
    }

    function validateField(form, formRules, name) {
        var field = form.elements[name];
        if (!field) return true;
        var message = formRules[name](field.value.trim(), form);
        showError(field, message);
        return message === '';
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var formRules = rules[form.getAttribute('data-validate')];
        if (!formRules) return;

        // Validate a field when the user leaves it, and re-check while typing once it has an error.
        Object.keys(formRules).forEach(function (name) {
            var field = form.elements[name];
            if (!field) return;
            field.addEventListener('blur', function () { validateField(form, formRules, name); });
            field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', function () {
                if (field.closest('.form-group').classList.contains('has-error')) {
                    validateField(form, formRules, name);
                }
            });
        });

        // Validate every field before the form is submitted.
        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            Object.keys(formRules).forEach(function (name) {
                if (!validateField(form, formRules, name) && !firstInvalid) {
                    firstInvalid = form.elements[name];
                }
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });
});
