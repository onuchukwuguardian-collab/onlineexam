# Application Guide: Online Examination System

## 1. Introduction

This document provides a comprehensive overview of the Online Examination System. It is a web application built using the Laravel framework, designed to allow administrators to create and manage exams, and for students to take these exams in a secure environment.

The system features role-based access control, detailed exam proctoring capabilities (including tab-switching detection), and a comprehensive admin dashboard for managing all aspects of the application.

## 2. Core Concepts

The application is built around several key data models:

-   **Users:** There are two main roles: `admin` and `user` (student).
    -   **Admins:** Can manage the entire system, including users, classes, subjects, and questions.
    -   **Students:** Can take exams for the subjects assigned to their class.
-   **Classes (`ClassModel`):** Represent the different classes in the school (e.g., "JSS 1", "SS 2"). Each student belongs to a single class.
-   **Subjects:** Represent the subjects available for exams (e.g., "Mathematics", "English Language"). Each subject is assigned to a specific class.
-   **Questions & Options:** Each subject can have multiple questions. Each question has multiple options (e.g., A, B, C, D), with one designated as the correct answer.
-   **Exam Sessions (`ExamSession`):** An `ExamSession` record is created when a student starts an exam. It tracks the start time, remaining time, and the student's answers as they progress.
-   **Security & Bans (`ExamSecurityViolation`, `ExamBan`):** The system tracks security violations (like switching tabs). A violation can lead to an `ExamBan`, which prevents a student from accessing a specific subject's exam. Bans are subject-specific.

## 3. Key Workflows

### 3.1. Student Workflow

1.  **Login:** A student logs in using their email, registration number, or a unique school passcode.
2.  **Dashboard:** After logging in, the student is taken to their dashboard (`/student/dashboard`). The dashboard displays a list of all subjects available for their class.
3.  **Start Exam:** The student clicks on a subject to start an exam. This creates an `ExamSession` and begins the timer.
4.  **Taking the Exam:** The student answers questions one by one. Their progress is saved periodically.
5.  **Security Monitoring:** While taking the exam, a client-side script (`exam-security.js`) monitors for tab-switching. If a tab switch is detected, a violation is immediately reported to the server. This results in the student being banned from that specific subject's exam.
6.  **Submission:** The student submits the exam. The system calculates their score, saves it (`UserScore`), and marks the `ExamSession` as complete. If the timer runs out, the exam is auto-submitted.
7.  **View Score:** The student is redirected to a page displaying their score for the completed exam.

### 3.2. Admin Workflow

1.  **Login:** An admin logs in. They are redirected to the admin dashboard (`/admin/dashboard`).
2.  **User Management:** Admins can create, view, edit, and delete users. They can assign roles (`admin` or `user`) and assign students to classes. There is also a feature for bulk-uploading users from a CSV file.
3.  **Class & Subject Management:** Admins can create and manage classes and subjects. Each subject is assigned to a class and has a specific exam duration.
4.  **Question Management:** For each subject, admins can create, edit, and delete questions and their corresponding options. They can also upload images for questions and bulk-upload questions from a CSV file.
5.  **Security Dashboard:** Admins have access to a detailed security dashboard (`/admin/security`) where they can:
    -   View statistics on security violations.
    -   See a list of currently active bans.
    -   View a complete history of all bans (active and inactive).
    -   Manually unban a student for a specific subject.

## 4. Technical Details

### 4.1. Technology Stack

-   **Backend:** PHP / Laravel Framework
-   **Frontend:** Blade templates, with JavaScript for interactivity. Uses Bootstrap for styling in the admin area.
-   **Database:** (Not specified, but likely MySQL or similar, as is common with Laravel).

### 4.2. Key Directories

-   `app/Http/Controllers`: Contains the application's logic (e.g., `ExamController`, `Admin/UserController`).
-   `app/Models`: Contains the Eloquent models that interact with the database (e.g., `User`, `Subject`, `Question`).
-   `app/Console/Commands`: Contains custom Artisan commands.
-   `database/migrations`: Contains the database schema definitions.
-   `resources/views`: Contains the Blade templates for the UI.
-   `routes`: Contains the application's route definitions (`web.php`, `api.php`).

### 4.3. Scheduled Tasks

The application has a scheduled task configured to run daily:

-   `exam:cleanup-sessions`: This command finds and completes any exam sessions that have expired but were not correctly marked as completed. This is a crucial task for maintaining the stability of the exam system.

### 4.4. Running the Application (Typical Setup)

*This is a general guide based on standard Laravel practices, as the specific environment details were not available.*

1.  **Install Dependencies:**
    ```bash
    composer install
    npm install
    ```
2.  **Environment Configuration:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
3.  **Database Migration:**
    *   Configure your database connection in the `.env` file.
    *   Run the migrations: `php artisan migrate`
4.  **Build Frontend Assets:**
    ```bash
    npm run dev
    ```
5.  **Run the Server:**
    ```bash
    php artisan serve
    ```
6.  **Run the Scheduler (for the cleanup task):**
    *   On a production server, a Cron entry would be needed to run `php artisan schedule:run` every minute.
