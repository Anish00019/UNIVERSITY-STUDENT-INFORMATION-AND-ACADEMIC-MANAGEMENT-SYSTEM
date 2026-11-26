# Project Report Structure – BCA Final Year Project

**Title:** University Student Information and Academic Management System (UniSIMS)
**Technologies:** HTML, CSS, JavaScript, PHP, MySQL, XML (XAMPP)

---

## Preliminary pages
1. Title page (project title, student name, register number, guide, department, university, year)
2. Bonafide certificate
3. Declaration
4. Acknowledgement
5. Abstract (150–250 words)
6. Table of contents
7. List of figures and list of tables

## Chapter 1 – Introduction
1.1 Overview of the project
1.2 Problem statement: manual or spreadsheet records are slow, error-prone and insecure
1.3 Objectives
- Secure login and registration
- Centralised student records with CRUD operations
- Dashboard summary for quick decisions
- XML export that stays in sync with MySQL

1.4 Scope of the project
1.5 Organisation of the report

## Chapter 2 – System Analysis
2.1 Existing system and its limitations
2.2 Proposed system and its advantages
2.3 Feasibility study: technical, economic and operational
2.4 Requirement specification
- Functional requirements: login, registration, add/view/edit/delete/search students, XML view
- Non-functional requirements: security, usability, responsiveness, performance

## Chapter 3 – System Requirements
3.1 Hardware requirements: any PC with 4 GB RAM, 1 GHz CPU and 500 MB free disk space
3.2 Software requirements: Windows/Linux/macOS, XAMPP (Apache, PHP 8.x, MySQL/MariaDB), a modern browser, VS Code
3.3 Technology overview: HTML, CSS, JavaScript, PHP, MySQL, XML, XAMPP

## Chapter 4 – System Design
4.1 System architecture: three tiers (browser → Apache/PHP → MySQL plus the XML file)
4.2 Module design: Login/Registration, Dashboard, Student Management, Database, XML
4.3 Data flow diagrams
- Level 0: context diagram (User ↔ UniSIMS ↔ Database/XML)
- Level 1: Login, Manage Students, Generate XML

4.4 ER diagram: `USERS` and `STUDENTS` entities with their attributes
4.5 Database design: data dictionary for `users` and `students` (see README §2)
4.6 XML design: structure of `students.xml`
4.7 User interface design: screenshots of the login, dashboard, forms and tables

## Chapter 5 – Implementation
5.1 Folder structure (see README §1)
5.2 Database connectivity (`db.php`, MySQLi, prepared statements)
5.3 Authentication: `password_hash()`, `password_verify()`, sessions, session regeneration
5.4 CRUD implementation, with a short code excerpt for each operation
5.5 XML synchronisation: `sync_students_xml()` with DOMDocument, and reading with SimpleXML
5.6 Validation: JavaScript on the client and PHP on the server
5.7 Security measures: SQL injection, XSS, CSRF, unauthorised access

## Chapter 6 – Testing
6.1 Testing methods: unit, integration, system, black-box
6.2 Test cases table with columns: Test ID, Description, Input, Expected Output, Actual Output, Status

| Test ID | Description | Input | Expected | Status |
|---|---|---|---|---|
| TC01 | Valid login | admin / Admin@123 | Dashboard opens | Pass |
| TC02 | Invalid login | admin / wrong | Error message | Pass |
| TC03 | Register duplicate username | admin | "Username already taken" | Pass |
| TC04 | Add valid student | All fields valid | Saved + XML updated | Pass |
| TC05 | Duplicate register number | BCA23001 | Error message | Pass |
| TC06 | Invalid phone | 12345 | Validation error | Pass |
| TC07 | Search student | "BCA23" | Matching rows only | Pass |
| TC08 | Edit student | Change semester | Updated + XML updated | Pass |
| TC09 | Delete student | Confirm delete | Removed + XML updated | Pass |
| TC10 | XML view | Open XML Records | Same data as MySQL, "In sync" | Pass |
| TC11 | Access without login | Open dashboard.php | Redirect to login | Pass |
| TC12 | Logout | Click Logout | Session destroyed | Pass |

(The full list of 19 tests is in README §6.)

## Chapter 7 – Screenshots (Output)
Login page, Registration page, Dashboard, Add Student (with validation errors), View Students (with search), Edit Student, Delete confirmation, XML View, raw `students.xml`, and the mobile view.

## Chapter 8 – Conclusion and Future Enhancements
8.1 Conclusion
8.2 Future enhancements
- Role-based access (admin, faculty, student)
- Attendance and marks modules
- Export to PDF/Excel
- Profile photos
- Email notifications
- Pagination for large data sets

## References / Bibliography
- PHP Manual: <https://www.php.net/manual/>
- MySQL Reference Manual: <https://dev.mysql.com/doc/>
- MDN Web Docs (HTML, CSS, JavaScript): <https://developer.mozilla.org/>
- W3C XML specification: <https://www.w3.org/XML/>
- XAMPP documentation: <https://www.apachefriends.org/>

## Appendix
- A: Complete source code listing
- B: SQL script (`database/university_management.sql`)
- C: Sample `students.xml`
