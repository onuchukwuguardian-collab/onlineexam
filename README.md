# Online Exam System

A comprehensive online examination platform built with Laravel, featuring advanced security measures, real-time monitoring, and robust administrative controls.

## Features

### 🔒 Advanced Security System
- **Anti-Tab Switching Detection**: 15-strike policy for tab switching violations
- **Copy Protection**: Prevents unauthorized copying during exams
- **Right-Click Protection**: Disables context menus during examinations
- **Session Management**: Secure user session handling with violation tracking
- **IP Monitoring**: Tracks and monitors user IP addresses

### 👨‍💼 Administrative Features
- **Student Management**: Comprehensive student registration and profile management
- **Question Bank**: Create and manage questions with image support
- **Subject Management**: Organize exams by subjects and categories
- **Ban System**: Advanced user banning and reactivation system
- **Violation Tracking**: Monitor and track student violations
- **Exam Reset**: Reset exam attempts and progress
- **Bulk Operations**: Mass operations for student management

### 📊 Exam Management
- **Timed Exams**: Configurable exam durations with auto-submit
- **Question Randomization**: Randomize question order for each student
- **Image Support**: Questions can include images and multimedia
- **Auto-Advance**: Automatic progression through exam questions
- **Real-time Monitoring**: Live tracking of exam progress
- **Score Calculation**: Automated scoring and result generation

### 🎯 Student Features
- **Secure Exam Interface**: Clean, distraction-free exam environment
- **Progress Tracking**: Real-time progress indicators
- **Reactivation System**: Request reactivation after violations
- **Responsive Design**: Works on desktop and mobile devices

## Technology Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Frontend**: Blade Templates, Bootstrap, JavaScript
- **Database**: MySQL/MariaDB
- **Styling**: Tailwind CSS, Bootstrap
- **Icons**: FontAwesome
- **Package Management**: Composer, NPM

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/onuchukwuguardian-collab/onlineexam.git
   cd onlineexam
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database Setup**
   - Create a MySQL database
   - Update `.env` with your database credentials
   - Run migrations:
     ```bash
     php artisan migrate
     php artisan db:seed
     ```

6. **Build Assets**
   ```bash
   npm run build
   ```

7. **Start the Application**
   ```bash
   php artisan serve
   ```

## Configuration

### Security Settings
The system includes various security configurations in `config/security.php`:
- Tab switching violation limits
- Copy protection settings
- Session timeout configurations
- IP monitoring settings

### Exam Settings
Exam-specific configurations can be found in `config/exams.php`:
- Default exam duration
- Question randomization settings
- Auto-submit configurations
- Scoring algorithms

## Usage

### For Administrators
1. Access the admin dashboard at `/admin`
2. Manage students, questions, and subjects
3. Monitor ongoing exams
4. Review violation reports
5. Handle reactivation requests

### For Students
1. Register or login to the system
2. Select available exams
3. Follow exam instructions carefully
4. Complete exams within the time limit
5. View results after completion

## Security Features

This system implements multiple layers of security:

- **Violation Tracking**: Comprehensive tracking of student violations
- **Automated Banning**: Automatic banning based on violation thresholds
- **Session Security**: Secure session management with timeout controls
- **Anti-Cheating**: Multiple anti-cheating mechanisms
- **Admin Controls**: Granular administrative controls and monitoring

## Documentation

Detailed documentation for specific features can be found in the project's markdown files:
- `COMPLETE_SECURITY_SYSTEM_FINAL.md` - Security system overview
- `ADMIN_DASHBOARD_FINAL_REPORT.md` - Admin dashboard features
- `REACTIVATION_SYSTEM_COMPLETE.md` - Reactivation system details
- `TAB_SWITCHING_3_STRIKE_SYSTEM_READY.md` - Tab switching detection

## Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests for any improvements.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue on GitHub or contact the development team.
