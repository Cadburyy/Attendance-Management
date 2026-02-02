# Mutation Management

A **Laravel-based project** built for task management and internal operations.

## 🚀 Setup Instructions

To get the **MM** project running on your local machine, follow the steps below. The process includes backend setup, database configuration, and frontend build compilation.

### 1\. Initial Setup and Prerequisites

First, ensure you have all the necessary software installed:

  * **XAMPP**: Start the Apache and MySQL services. These will act as your local web server and database server.
  * **Composer**: This is a PHP dependency manager.
  * **Node.js & npm**: These manage frontend dependencies and scripts.

-----

### 2\. Project Download and Backend Configuration

1.  Download the project files from the GitHub repository as a ZIP file.
2.  Extract the ZIP file and place the `mm` folder in your preferred directory.
3.  Open a terminal and navigate to the project's root directory: `cd path/to/mm`.
4.  Install the backend dependencies by running `composer install`.
5.  Create a copy of the environment file with `cp .env.example .env`.
6.  Generate a unique application key for security with `php artisan key:generate`.
7.  Create a new database in **phpMyAdmin**, for example, `mm_db`.
8.  Open the `.env` file and update the database settings to match your new database:
      * `DB_DATABASE=mm_db`
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
4.  Open a **new** terminal and, while in the same `mm` directory, start the frontend development server with `npm run dev`.

The application will now be running and accessible at [http://localhost:8000](https://www.google.com/search?q=http://localhost:8000). You can log in with the admin user created during the seeding process to begin managing the application.
