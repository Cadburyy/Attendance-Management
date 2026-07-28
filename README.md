# Attendance Management

A **Laravel-based project** built for attendance management.

## 🚀 Setup Instructions

To get the **AM** project running on your local machine, follow the steps below. The process includes backend setup, database configuration, and frontend build compilation.

### 1\. Initial Setup and Prerequisites

First, ensure you have all the necessary software installed:

  * **XAMPP**: Start the Apache and MySQL services. These will act as your local web server and database server.
  * **Composer**: This is a PHP dependency manager.
  * **Node.js & npm**: These manage frontend dependencies and scripts.

-----

### 2\. Project Download and Backend Configuration

1.  Download the project files from the GitHub repository as a ZIP file.
2.  Extract the ZIP file and place the `am` folder in your preferred directory.
3.  Open a terminal and navigate to the project's root directory: `cd path/to/am`.
4.  Install the backend dependencies by running `composer install`.
5.  Create a copy of the environment file with `cp .env.example .env`.
6.  Generate a unique application key for security with `php artisan key:generate`.
7.  Create a new database in **phpMyAdmin**, for example, `am_db`.
8.  Open the `.env` file and update the database settings to match your new database:
      * `DB_DATABASE=am_db`
      * `DB_USERNAME=root`
      * `DB_PASSWORD=`

-----

### 3\. Database Migration and Seeding

With the database configured, you can set it up with the required tables and initial data.

1.  Run the migration and seeding commands:   
    * `php artisan migrate:fresh`
    * `php artisan db:seed --class=PermissionTableSeeder`
    * `php artisan db:seed --class=CreateAdminUserSeeder`
2.  This command will create all the necessary tables and populate them with default data for roles, permissions, and a default admin user.

-----

### 4\. Frontend Setup and Launch

1.  In the same terminal, install the frontend dependencies by running `npm install`.
2.  Build the frontend assets with `npm run build`.
3.  Start the Laravel development server with `php artisan serve`.
4.  Open a **new** terminal and, while in the same `am` directory, start the frontend development server with `npm run dev`.

### 5. AI Server Setup (Face & Uniform Recognition)
This system uses a separate Python server for AI processing.

1. Open a new terminal and navigate to the AI folder: `cd ai_services`
2. Create a Virtual Environment (Optional but recommended):
   * `python -m venv venv`
   * `venv\Scripts\activate` (Windows)
3. Install AI dependencies:
   * `pip install -r requirements.txt`
4. Run the AI Server: `python app.py`
   * The server will run at `http://127.0.0.1:5000`. Make sure this is always running when using the attendance feature.

The application will now be running and accessible at [http://localhost:8000](https://www.google.com/search?q=http://localhost:8000). You can log in with the admin user created during the seeding process to begin managing the application.
