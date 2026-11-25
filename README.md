# University Student Information and Academic Management System (UniSIMS)

A BCA final-year project: a simple, secure web application for managing university student records.
It is built only with **HTML, CSS, JavaScript, PHP, MySQL and XML**, and runs locally on **XAMPP**.

| Feature | Details |
|---|---|
| Login & Registration | `password_hash()` / `password_verify()`, PHP sessions, CSRF tokens |
| Dashboard | Total students, total courses, current term, users, recent students, semester and course charts, quick links |
| Student CRUD | Add, view, edit and delete students, plus search by register number or name and filter by course |
| Validation | JavaScript (instant feedback) **and** PHP (server side); duplicate register numbers are blocked |
| Security | Prepared statements (SQL injection), `htmlspecialchars()` (XSS), CSRF tokens, session-ID regeneration |
| XML | `students.xml` is rebuilt automatically after every add, edit or delete. `xml_view.php` displays it |
| UI | Navy, white and light-blue theme, sidebar and top bar, responsive on desktop, tablet and mobile |

---

## 1. Project folder structure

```
university_management/
├── index.php               ← Login page (first page)
├── register.php            ← New user registration
├── dashboard.php           ← Main dashboard
├── add_student.php         ← Add student      (CREATE)
├── view_students.php       ← List + search    (READ)
├── edit_student.php        ← Edit student     (UPDATE)
├── delete_student.php      ← Delete student   (DELETE, with confirmation)
├── xml_view.php            ← "Student Records – XML View"
├── logout.php              ← Logout
├── db.php                  ← Database connection (MySQLi)
├── students.xml            ← XML student data (auto-synchronised)
├── includes/               ← Reusable PHP parts (direct access blocked by .htaccess)
│   ├── functions.php       ← Sessions, CSRF, validation, XML sync, icons
│   ├── header.php          ← Sidebar + top navigation bar
│   ├── footer.php          ← Page footer + script include
│   ├── student_form.php    ← Shared Add/Edit student form
│   └── .htaccess
├── assets/
│   ├── css/style.css       ← All styling and responsive design
│   └── js/script.js        ← Client-side validation and interactivity
└── database/
    ├── university_management.sql   ← Creates the database, tables and sample data
    └── .htaccess
```

## 2. Database design (`university_management`)

**Table `users`** stores login and registration details.

| Column | Type | Constraints |
|---|---|---|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT |
| full_name | VARCHAR(100) | NOT NULL |
| username | VARCHAR(30) | NOT NULL, **UNIQUE** |
| email | VARCHAR(100) | NOT NULL, **UNIQUE** |
| password | VARCHAR(255) | NOT NULL (bcrypt hash) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Table `students`** stores student academic information.

| Column | Type | Constraints |
|---|---|---|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT |
| register_number | VARCHAR(20) | NOT NULL, **UNIQUE** |
| student_name | VARCHAR(100) | NOT NULL, indexed |
| course | VARCHAR(50) | NOT NULL, indexed |
| semester | TINYINT UNSIGNED | NOT NULL (1 – 8) |
| email | VARCHAR(100) | NOT NULL |
| contact_number | VARCHAR(10) | NOT NULL |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

**XML structure (`students.xml`):**

```xml
<students generated="2026-11-25 10:24:25" total="8">
  <student id="1">
    <register_number>BCA23001</register_number>
    <student_name>Aarav Sharma</student_name>
    <course>BCA</course>
    <semester>5</semester>
    <email>aarav.sharma@student.edu</email>
  </student>
  ...
</students>
```

## 3. Installation with XAMPP

1. **Install XAMPP** (PHP 8.x is recommended; PHP 7.4 or newer works) from <https://www.apachefriends.org>.
2. **Copy the project.** Copy the `university_management` folder into XAMPP's `htdocs` folder:
   - Windows: `C:\xampp\htdocs\university_management`
   - Linux: `/opt/lampp/htdocs/university_management`
   - macOS: `/Applications/XAMPP/htdocs/university_management`
3. **Start the servers.** Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4. **Create the database.**
   - Open <http://localhost/phpmyadmin>.
   - Click **Import**, choose `university_management/database/university_management.sql`, then click **Go**.
   - This creates the `university_management` database, both tables and the sample data.
     The script drops any old copy of this database first.
5. **Open the application** at <http://localhost/university_management/>.

**Linux/macOS only:** Apache must be allowed to write the XML file:
```bash
sudo chmod 666 /opt/lampp/htdocs/university_management/students.xml
```

**Other MySQL settings:** if your MySQL uses a password or a port other than 3306, edit the constants at the top of `db.php`.

## 4. Sample login credentials

| Username | Password |
|---|---|
| `admin` | `Admin@123` |

You can also sign in with the email `admin@university.edu`, or create a new account on the Register page.

## 5. Module explanation

1. **Login and Registration** (`index.php`, `register.php`, `logout.php`)
   - The login page looks up the username or email with a prepared statement and checks the password with `password_verify()`.
   - After a successful login, `session_regenerate_id(true)` runs to prevent session fixation. The user's id, username and name are then stored in `$_SESSION`.
   - Registration validates every field and rejects usernames or emails that are already taken. It stores `password_hash($password, PASSWORD_DEFAULT)`.
   - Logout clears the session data, deletes the session cookie and destroys the session. The logout link carries the CSRF token.
2. **Dashboard** (`dashboard.php`)
   - Shows four statistic cards: Total Students, Total Courses, Current Term and System Users.
   - Also shows the five most recently added students, semester information with a students-per-semester chart, a students-by-course chart and quick links.
   - The academic session and term are calculated from today's date: June–November is the odd semester and December–May is the even semester.
3. **Student Management (CRUD)**
   - **Add** (`add_student.php`): validates the form, rejects duplicate register numbers, `INSERT`s the record, then rebuilds the XML file.
   - **View** (`view_students.php`): professional table with search by register number or name (`LIKE`) and a course filter.
   - **Edit** (`edit_student.php?id=…`): the form is pre-filled. The student keeps their own register number, but it cannot clash with another student's. Saving runs an `UPDATE` and rebuilds the XML.
   - **Delete** (`delete_student.php?id=…`): a confirmation page shows the record first. Deletion only happens by POST with a valid CSRF token, then the XML is rebuilt.
4. **MySQL Database** (`db.php`, `database/university_management.sql`)
   - Uses a MySQLi connection with `utf8mb4`.
   - Errors become exceptions and are shown as a friendly message.
   - Every query that contains user input uses a prepared statement.
5. **XML** (`students.xml`, `xml_view.php`, `sync_students_xml()` in `includes/functions.php`)
   - `sync_students_xml()` reads all students from MySQL and builds the document with `DOMDocument`, which escapes special characters automatically. It then saves `students.xml`.
   - The function runs after every add, edit and delete.
   - `xml_view.php` reads the file with **SimpleXML** and shows it in an HTML table with a quick filter.
   - The page also shows whether the XML matches MySQL, offers a **Sync Now** button and displays the raw XML source.

## 6. Testing instructions

| # | Test | Steps | Expected result |
|---|---|---|---|
| 1 | Protected pages | Open `dashboard.php` without logging in | Redirected to login with "Please log in…" |
| 2 | Login – invalid | Enter `admin` with a wrong password | "Invalid username or password." |
| 3 | Login – valid | Enter `admin` / `Admin@123` | Dashboard opens with a welcome message |
| 4 | Registration – validation | Submit the register form empty or with a short password | Red error under each invalid field |
| 5 | Registration – duplicate | Register with username `admin` | "This username is already taken." |
| 6 | Registration – success | Register a new user, then log in with it | "Registration successful!", then login works |
| 7 | Add – validation | Add a student with a bad email or a 5-digit phone | Field errors; nothing is saved |
| 8 | Add – duplicate | Add a student with register number `BCA23001` | "A student with this register number already exists." |
| 9 | Add – success | Add a valid student | Success message; student appears in the list |
| 10 | Display / search | Search for `BCA23` or a name, and filter by course | Only matching rows are shown |
| 11 | Edit | Click ✏️ on a student, change the semester, then save | Success message; new value shown |
| 12 | Edit – duplicate | Change a register number to one that already exists | "Another student already uses this register number." |
| 13 | Delete | Click 🗑️, check the confirmation page, then click **Yes, Delete** | Record removed; success message |
| 14 | XML sync | After tests 9, 11 and 13, open **XML Records** | The XML table shows the same data; status is **In sync** |
| 15 | Raw XML | Click **Open XML File** | The browser shows the XML tree from `students.xml` |
| 16 | Logout | Click **Logout** and confirm | Back at login with "logged out successfully"; dashboard is protected again |
| 17 | Security – XSS | Try the name `<script>alert(1)</script>` | Rejected by validation; all output is escaped anyway |
| 18 | Security – SQL injection | Search for `' OR 1=1 --` | "No students match your search." |
| 19 | Responsive | Resize the browser or use mobile view (F12) | The sidebar becomes a ☰ slide-in menu; forms stack in one column |

## 7. Project report structure (BCA)

See [`PROJECT_REPORT.md`](PROJECT_REPORT.md) for a chapter-by-chapter report outline.

## 8. Troubleshooting

| Problem | Solution |
|---|---|
| "Database connection failed" | Start MySQL in XAMPP, import the SQL file, and check `db.php` |
| Apache or MySQL will not start | Another program is using port 80 or 3306. Change the port in XAMPP and in `DB_PORT` |
| "students.xml could not be updated" | Give write permission to `students.xml` (see Linux/macOS note above) |
| Login does not work after re-importing | Clear browser cookies, or use a private window |
