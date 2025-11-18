# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SkanPolis is a Polish insurance comparison web application that allows users to search for and compare car insurance policies (OC/AC). The application includes user authentication, admin panel, and insurance search functionality.

## Technology Stack

- **Backend**: PHP with PDO for database interactions
- **Frontend**: HTML5, CSS3
- **Database**: MySQL (database name: `insurance_db`)
- **Language**: Polish (UI and content)

## Project Structure

```
Insurance_project/
├── index.html              # Landing page with login/register options
├── html/                   # HTML templates
│   ├── main.html          # Main insurance search form (logged-in users)
│   ├── admin.html         # Admin panel static template
│   ├── login.html         # Login page static template
│   ├── account.html       # User account page
│   ├── detail.html        # Insurance policy details
│   └── list.html          # Insurance list view
├── scripts/               # PHP backend scripts
│   ├── db_connect.php     # Database connection configuration
│   ├── login.php          # Login logic and form
│   ├── register.php       # Registration logic and form
│   ├── admin.php          # Admin panel with CRUD operations
│   ├── manage_insurance.php # Insurance search and results
│   └── validate_form.php  # Form validation utilities
└── css/                   # Stylesheets for each page
    ├── start.css
    ├── main.css
    ├── admin.css
    ├── login.css
    ├── register.css
    └── ...
```

## Database Configuration

Database connection is centralized in `scripts/db_connect.php`:
- Host: `localhost`
- Database: `insurance_db`
- User: `root`
- Password: Stored in db_connect.php (not in version control for production)

### Database Tables

**User table:**
- `email` (varchar, primary key)
- `haslo` (varchar) - password hash (bcrypt)
- `SUser` (int) - admin flag (1 = admin, 0 = regular user)
- `Wiek` (int) - age

**Insurance table:**
- `Insurance_ID` (int, auto-increment)
- `Insurance_name` (varchar) - insurance company name
- `Insurance_type` (varchar) - type: OC, AC, or OC/AC
- `Typ_nadwozia` (varchar) - body type (Sedan, SUV, Kombi, etc.)
- `Use_type` (varchar) - LEASING or PRYWATNIE
- `License_release_date` (date) - policy expiration date
- `Planned_mileage` (int) - annual mileage

## Development Workflow

### Running the Application

1. Start a local PHP development server:
   ```bash
   cd Insurance_project
   php -S localhost:8000
   ```

2. Access the application at `http://localhost:8000/index.html`

### Database Setup

Ensure MySQL is running and the `insurance_db` database exists with the proper schema. Connection details are in `scripts/db_connect.php:4-7`.

## Key Application Flows

### User Authentication

1. **Registration** (`scripts/register.php`):
   - Email validation (must be unique)
   - Password requirements: min 8 chars, 1 uppercase, 1 number
   - Passwords are hashed using bcrypt
   - Default SUser = 0 (regular user)

2. **Login** (`scripts/login.php`):
   - Email and password validation
   - Redirects admins (SUser = 1) to `admin.php`
   - Redirects regular users to `html/main.html`

### Insurance Search

1. Users fill out the form in `html/main.html` with vehicle details
2. Form submits to `scripts/manage_insurance.php`
3. Results are filtered based on:
   - `typ_nadwozia` (body type)
   - `Use_type` (leasing/private)
   - `typ_ubezpieczenia` (insurance type)
4. Matching policies are displayed with randomly generated prices (placeholder)

### Admin Panel

Accessible only to users with SUser = 1 (`scripts/admin.php`):
- View all insurance policies in a table
- Add new insurance policies
- Delete existing insurance policies
- Form validation for all required fields

## Code Architecture Notes

### PHP File Organization

- **Mixed PHP/HTML files**: `login.php`, `register.php`, `admin.php`
  - These files contain both processing logic (top) and HTML templates (bottom)
  - PHP processes POST requests, then displays the form with error messages

- **Database connection**:
  - Always include `db_connect.php` at the top of PHP files
  - Uses global `$pdo` object for all database operations
  - PDO configured with exception mode for error handling

### Security Considerations

- Passwords are hashed with `password_hash()` and verified with `password_verify()`
- Prepared statements are used for all database queries to prevent SQL injection
- Input sanitization with `trim()` and `htmlspecialchars()` for output
- Email validation with `filter_var(FILTER_VALIDATE_EMAIL)`

### Naming Conventions

- Database columns use snake_case or PascalCase inconsistently
- Polish variable names and comments in some areas
- CSS classes use kebab-case
- HTML IDs use kebab-case

## Common Development Tasks

### Adding a New Insurance Policy Field

1. Update the `Insurance` table schema in MySQL
2. Modify the form in `html/admin.html` or create new form fields
3. Update the insert query in `scripts/admin.php:32-39`
4. Add the new field to the search filters in `scripts/manage_insurance.php` if needed

### Modifying User Authentication

- User validation logic: `scripts/login.php:27-66`
- Registration logic: `scripts/register.php:5-75`
- Password requirements: `scripts/register.php:23-32`

### Updating Search Filters

- Search form: `html/main.html:27-113`
- Search query logic: `scripts/manage_insurance.php:11-46`
- Add new filter by extending the WHERE clause builder pattern

## Known Issues/Technical Debt

1. Random price generation in `manage_insurance.php:91` - should pull from database
2. Database credentials hardcoded in `db_connect.php` - should use environment variables
3. No session management - users can access pages directly without login
4. Mixed HTML/PHP files make testing difficult
5. Inconsistent database naming conventions
6. Form in `html/main.html:27` has no method/action attributes for POST submission
7. Debug code left in place (`var_dump` in register.php:17,20)

CLAUDE.md - SkanPolis Insurance Comparison Platform
Purpose: Guide Claude Code through migrating legacy PHP application to modern, secure, containerized architecture.
Team: 2 developers (beginners in DevOps/SRE)
Migration Goal: Transform monolithic PHP app → Security-first, containerized, MVC architecture with proper secret management and session handling.

🎯 CURRENT STATE (Legacy)
Insurance_project/
├── index.html (landing page)
├── html/ (mixed HTML templates)
├── scripts/ (PHP files with inline HTML)
│   ├── db_connect.php (HARDCODED credentials ⚠️)
│   ├── login.php (no session management ⚠️)
│   ├── register.php (weak password rules ⚠️)
│   └── admin.php (no authorization check ⚠️)
└── css/ (styles)
Critical Issues:

❌ Database credentials in code (db_connect.php)
❌ No session management (users can access pages directly)
❌ SQL concatenation (injection risk)
❌ Mixed HTML/PHP (no MVC pattern)
❌ No containerization
❌ Weak passwords (8 chars, no special chars)
❌ No CSRF protection
❌ Debug code in production (var_dump)


🎯 TARGET STATE (Modern)
Insurance_project/
├── docker/
│   ├── docker-compose.yml (orchestration)
│   ├── nginx/ (web server config)
│   ├── php/ (PHP-FPM config)
│   └── mysql/ (init scripts)
├── app/ (MVC structure)
│   ├── Controllers/ (request handling)
│   ├── Models/ (database logic)
│   ├── Views/ (presentation)
│   ├── Middleware/ (auth, CSRF)
│   ├── Services/ (business logic)
│   └── Config/ (app configuration)
├── secrets/ (NOT in git)
├── tests/ (unit + integration)
├── .env.example (template)
└── .gitignore (protect secrets)
Achieved Goals:

✅ Docker Secrets for credentials
✅ Redis-based session management
✅ Prepared statements (SQL injection safe)
✅ Clean MVC separation
✅ Container orchestration
✅ Strong passwords (12+ chars, complexity)
✅ CSRF middleware
✅ No debug code

Phase 1: Infrastructure (Priority: CRITICAL)

 Create docker/docker-compose.yml with services: nginx, php-fpm, mysql, redis
 Create docker/nginx/Dockerfile and nginx.conf
 Create docker/php/Dockerfile with PHP 8.2 + extensions
 Create .env.example with all required variables
 Create .gitignore (must include .env, secrets/, vendor/)
 Create docker/mysql/init.sql (database schema from legacy)

Phase 2: Secret Management (Priority: CRITICAL)

 Create app/Config/database.php that reads from Docker secrets or ENV
 Remove ALL hardcoded credentials from code
 Generate strong SESSION_SECRET (64+ random chars)
 Create secrets/ directory structure (NOT in git)
 Update docker-compose.yml to mount secrets

Phase 3: MVC Structure (Priority: HIGH)

 Create directory structure: Controllers/, Models/, Views/, Middleware/, Services/, Config/
 Install Slim Framework via composer (composer.json)
 Create app/public/index.php (application entry point)
 Create app/Config/routes.php (URL routing)
 Create base layout template (Views/layouts/main.php)

Phase 4: Authentication & Sessions (Priority: HIGH)

 Create Services/SessionService.php with Redis backend
 Create Middleware/AuthMiddleware.php (check if logged in)
 Create Middleware/AdminMiddleware.php (check if admin)
 Create Middleware/CsrfMiddleware.php (CSRF token validation)
 Migrate scripts/login.php → Controllers/AuthController.php
 Migrate scripts/register.php → Controllers/AuthController.php
 Update password requirements (12+ chars, uppercase, number, special)

Phase 5: Database Layer (Priority: HIGH)

 Create Models/User.php with prepared statements
 Create Models/Insurance.php with prepared statements
 Remove all SQL concatenation
 Add input validation in Models
 Test all database operations

Phase 6: Controllers & Views (Priority: MEDIUM)

 Migrate scripts/admin.php → Controllers/AdminController.php
 Migrate scripts/manage_insurance.php → Controllers/InsuranceController.php
 Convert HTML files to Views with template engine
 Add htmlspecialchars() to ALL user data output
 Implement CSRF tokens in ALL forms

Phase 7: Business Logic (Priority: MEDIUM)

 Create Services/ValidationService.php (input validation)
 Create Services/PricingService.php (insurance pricing logic)
 Move business logic from Controllers to Services

Phase 8: Testing & Documentation (Priority: LOW)

 Create tests/Unit/ with PHPUnit tests
 Create tests/Integration/ with feature tests
 Create scripts/setup-dev.sh (automated setup)
 Update README.md with setup instructions

 🚨 SECURITY RULES (NEVER VIOLATE)
Rule 1: NO SECRETS IN CODE
php// ❌ NEVER DO THIS
$password = "hardcoded_password";
$pdo = new PDO("mysql:host=localhost", "root", "password123");

// ✅ ALWAYS DO THIS
$password = getenv('DB_PASSWORD') ?: file_get_contents('/run/secrets/db_password');
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']}", 
    $_ENV['DB_USER'], 
    $password
);
Rule 2: ALWAYS USE PREPARED STATEMENTS
php// ❌ NEVER DO THIS - SQL Injection vulnerable
$email = $_POST['email'];
$query = "SELECT * FROM User WHERE email = '$email'";
$result = $pdo->query($query);

// ✅ ALWAYS DO THIS
$email = $_POST['email'];
$stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email");
$stmt->execute(['email' => $email]);
$result = $stmt->fetch();
Rule 3: ALWAYS ESCAPE OUTPUT
php// ❌ NEVER DO THIS - XSS vulnerable
<h1>Welcome <?= $user['name'] ?></h1>

// ✅ ALWAYS DO THIS
<h1>Welcome <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h1>
Rule 4: PASSWORD REQUIREMENTS
php// Minimum requirements:
// - 12+ characters
// - 1+ uppercase letter
// - 1+ lowercase letter  
// - 1+ number
// - 1+ special character (!@#$%^&*)

function validatePassword(string $password): array {
    $errors = [];
    if (strlen($password) < 12) {
        $errors[] = "Hasło musi mieć minimum 12 znaków";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Hasło musi zawierać wielką literę";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Hasło musi zawierać małą literę";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Hasło musi zawierać cyfrę";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Hasło musi zawierać znak specjalny";
    }
    return $errors;
}

// Always hash passwords
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Always verify passwords
if (!password_verify($inputPassword, $storedHash)) {
    // Invalid password
}
Rule 5: SESSION SECURITY
php// Required session settings
session_set_cookie_params([
    'lifetime' => 1800,        // 30 minutes
    'path' => '/',
    'domain' => $_ENV['APP_DOMAIN'],
    'secure' => true,          // HTTPS only
    'httponly' => true,        // No JavaScript access
    'samesite' => 'Strict'     // CSRF protection
]);

// Use Redis for session storage
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis:6379');

// Regenerate session ID on privilege change
session_regenerate_id(true);
Rule 6: CSRF PROTECTION
php// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In forms
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validate token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    throw new Exception('Invalid CSRF token');
}

🎯 INSTRUCTIONS FOR CLAUDE CODE
When user requests migration or refactoring:
1. Analyze Current State

Read all files in scripts/ and html/
Identify security vulnerabilities
Map current functionality

2. Create Infrastructure First

Generate docker/docker-compose.yml
Generate Dockerfile for each service
Generate .env.example
Generate .gitignore

3. Implement Secret Management

Create database config that reads from secrets/env
Remove all hardcoded credentials
Add instructions for generating secrets

4. Build MVC Structure

Create directory structure
Install Slim Framework
Create base templates (Controller, Model, Middleware, Service)
Setup routing

5. Migrate Authentication

Create SessionService with Redis
Create AuthMiddleware
Create AuthController
Migrate login/register logic
Update password requirements

6. Migrate Database Layer

Create User model with prepared statements
Create Insurance model with prepared statements
Remove SQL concatenation
Add validation

7. Migrate Business Logic

Create Controllers for each feature
Create Services for validation, pricing
Create Views with proper escaping
Add CSRF protection

8. Testing

Create test structure
Write unit tests for critical functions
Write integration tests for auth flow

9. Documentation

Update README with setup instructions
Create troubleshooting guide
Document API endpoints



## 🔄 Roadmap Migracji

### ✅ Faza 1: Konteneryzacja (UKOŃCZONA)
- [x] Docker Compose setup
- [x] Nginx + PHP-FPM
- [x] MySQL z nowym schematem
- [x] Redis dla sesji
- [x] Secrets management
- [x] Automatyczny setup



### 📋 Faza 2: Motorcycle Support
- [ ] Formularz wyszukiwania motocykli
- [ ] Controller dla MotorcycleInsurance
- [ ] Widoki wyników dla motocykli
- [ ] Pricing logic dla motocykli

### Faza 3: Zakładka z diagramem dla Administratora w jego panelu. Pokazuje procent udziału danego ubezpieczyciela w ofercie i drugi diagram jakiego ubezpieczyciela użytkownicy wybierają najczęściej.



### 📋 Faza 3: MVC Refactoring
- [ ] Slim Framework
- [ ] Controllers/Models/Views separation
- [ ] Service layer (ValidationService, PricingService)
- [ ] Template engine (Twig/Blade)

### 🔄 Faza 4: Security Hardening 
- [ ] CSRF middleware
- [ ] Authorization middleware (admin check)
- [ ] Input validation layer
- [ ] Password policy enforcement
- [ ] HTTPS w produkcji


### 📋 Faza 5: Testing & CI/CD
- [ ] PHPUnit tests
- [ ] Integration tests
- [ ] GitHub Actions CI/CD
- [ ] Automated deployments

✅ VERIFICATION CHECKLIST
Before considering migration complete:
Security:

 No credentials in code
 All queries use prepared statements
 All output is escaped
 CSRF tokens on all forms
 Session uses Redis with secure settings
 Passwords meet 12+ char requirements
 HTTPS enforced (production)

Architecture:

 Clean MVC separation
 No business logic in Controllers
 No database calls in Views
 Middleware for auth/admin checks

Infrastructure:

 All services in docker-compose
 Secrets managed properly
 .env.example provided
 .gitignore protects secrets

Testing:

 Unit tests pass
 Integration tests pass
 Manual testing checklist completed

Documentation:

 Setup instructions clear
 Architecture documented
 Security rules documented