# Western Pacific University - Student Management System

An enterprise information system for managing student enrollment, admissions, and dormitory allocations at Western Pacific University.

## Features

### Student Features
- Login/Logout
- Enroll in up to 4 unit offerings per semester
- Unenroll from units
- View profile (student information and current semester enrollments)
- View academic transcript with overall GPA

### Registrar's Office Features
- Login/Logout
- Add new student information
- View students eligible for HECAS scholarship (GPA >= 2.5)

### Student Services Office Features
- Login/Logout
- Allocate students to dormitories
- View students living in a particular dormitory

## Requirements

- XAMPP (PHP 7.4+ and MySQL)
- Web browser

## Installation

1. **Copy files to XAMPP htdocs**
   - Ensure all files are in `C:\xampp\htdocs\WPU\`

2. **Create the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `WPU`
   - Import the SQL schema file: `database/schema.sql`
   - Or run the SQL commands from `database/schema.sql` in phpMyAdmin

3. **Configure database connection**
   - The database configuration is in `config/database.php`
   - Default settings:
     - Host: `localhost`
     - User: `root`
     - Password: `` (empty)
     - Database: `WPU`
   - Modify if your MySQL settings are different

4. **Start XAMPP services**
   - Start Apache and MySQL from XAMPP Control Panel

5. **Access the application**
   - Open your browser and navigate to: `http://localhost/WPU/`

## Default Login Credentials

### Staff Accounts
- **Registrar's Office:**
  - Email: `registrar@wpu.edu`
  - Password: `password`

- **Student Services Office:**
  - Email: `services@wpu.edu`
  - Password: `password`

### Student Accounts
- Students are created by the Registrar's Office
- Each student receives a unique Student ID, email, and password upon account creation

## Database Structure

The system uses the following main tables:
- `staff` - Staff members (registrar and student services)
- `students` - Student information
- `units` - Available units/courses
- `unit_offerings` - When units are offered (semester and year)
- `enrollments` - Student enrollments with marks and grades
- `dormitories` - Dormitory information
- `floors` - Floor information for dormitories
- `rooms` - Room information
- `dormitory_allocations` - Student dormitory assignments

## Usage

1. **Login**
   - Select your user type (Student, Registrar's Office, or Student Services Office)
   - Enter your email and password
   - Click Login

2. **For Students:**
   - View dashboard with current semester enrollments
   - Enroll in units (maximum 4 per semester)
   - View profile and transcript
   - Unenroll from units if needed

3. **For Registrar's Office:**
   - Add new students to the system
   - View students eligible for HECAS scholarship (GPA >= 2.5)

4. **For Student Services Office:**
   - Allocate students to dormitory rooms
   - View students by dormitory

## Notes

- The system enforces a maximum of 4 unit enrollments per semester per student
- GPA is calculated automatically based on marks in enrolled units
- Students can only be allocated to one dormitory room at a time
- All passwords are hashed using PHP's `password_hash()` function

## Troubleshooting

- **Connection Error:** Check that MySQL is running in XAMPP and the database name is correct
- **Login Issues:** Verify you're using the correct email and password for your user type
- **Page Not Found:** Ensure Apache is running and files are in the correct directory

## Development

This system was built using:
- PHP (Server-side scripting)
- MySQL (Database)
- HTML/CSS (Frontend)
- Vanilla JavaScript (Client-side interactions)

## License

This project is for educational purposes as part of the Western Pacific University Student Management System assignment.

