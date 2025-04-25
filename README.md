# TaskTrackr

TaskTrackr is a lightweight task management application designed to help users organize and track their daily tasks efficiently.

## Features

- Add, edit, and delete tasks.
- Mark tasks as completed.
- Categorize tasks by priority or tags.
- Responsive and user-friendly interface.

## Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/markalvincadangin/TaskTrackr.git
    ```
2. Navigate to the project directory:
    ```bash
    cd TaskTrackr
    ```

## Setup Instructions

### 1. Install XAMPP
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/).
   - Start the Apache and MySQL services from the XAMPP Control Panel.

### 2. Configure the Database
   1. Open `phpMyAdmin` by navigating to:
      ```
      http://localhost/phpmyadmin
      ```
   2. Create a new database named `tasktrackr`.
   3. Import the database schema:
      - Click on the `tasktrackr` database.
      - Go to the `Import` tab.
      - Select the SQL file located in the `database` folder of this project (e.g., `tasktrackr.sql`).
      - Click `Go` to import the schema.

### 3. Configure the Application
   - Open the `config.php` file in the project directory.
   - Update the database connection settings if necessary:
     ```php
     // filepath: c:\xampp\htdocs\TaskTrackr\config\db.php
     $db_host = 'localhost';
     $db_user = 'root';
     $db_pass = ''; // Add your MySQL password if applicable
     $db_name = 'tasktrackr';
     ```

### 4. Start the Application
   - Open your browser and navigate to:
     ```
     http://localhost/TaskTrackr
     ```

## Usage

1. Open your browser and navigate to:
    ```
    http://localhost/TaskTrackr
    ```
2. Start managing your tasks!

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch:
    ```bash
    git checkout -b feature-name
    ```
3. Commit your changes:
    ```bash
    git commit -m "Add feature-name"
    ```
4. Push to the branch:
    ```bash
    git push origin feature-name
    ```
5. Open a pull request.

## Acknowledgments

- Built with PHP and XAMPP.
- Inspired by the need for simple task management tools.
- Thanks to the open-source community for their support.