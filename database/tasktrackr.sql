-- TaskTrackr Database Schema
-- This SQL script creates the database and tables for the TaskTrackr application.

CREATE DATABASE IF NOT EXISTS tasktrackr;
USE tasktrackr;

-- Users Table
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255), -- Path to the profile picture
    role ENUM('user', 'admin') DEFAULT 'user',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User_Settings Table
CREATE TABLE IF NOT EXISTS User_Settings (
    user_id INT PRIMARY KEY,
    reminder_days_before INT DEFAULT 1, -- How many days before due date to remind
    theme ENUM('light', 'dark') DEFAULT 'light',
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

-- Categories Table
CREATE TABLE IF NOT EXISTS Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT
);

-- Groups Table
CREATE TABLE IF NOT EXISTS `Groups` (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL UNIQUE,
    created_by INT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- Projects Table
CREATE TABLE IF NOT EXISTS Projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    deadline DATE NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    category_id INT,
    created_by INT,
    group_id INT,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `Groups`(group_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- User_Groups Table (Join Table)
CREATE TABLE IF NOT EXISTS User_Groups (
    user_id INT,
    group_id INT,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `Groups`(group_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tasks Table
CREATE TABLE IF NOT EXISTS Tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Pending', 'In Progress', 'Done', 'Overdue') NOT NULL DEFAULT 'Pending',
    project_id INT,
    assigned_to INT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_reminder_sent DATE NULL DEFAULT NULL,
    FOREIGN KEY (project_id) REFERENCES Projects(project_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES Users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- References to related entities
    related_task_id INT,
    related_project_id INT,
    related_group_id INT,
    related_user_id INT, -- For user-to-user notifications
    notification_type ENUM('task_update', 'task_assignment', 'project_update', 
                          'group_update', 'group_invitation', 'reminder', 'general') NOT NULL,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (related_task_id) REFERENCES Tasks(task_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (related_project_id) REFERENCES Projects(project_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (related_group_id) REFERENCES `Groups`(group_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (related_user_id) REFERENCES Users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- Insert default categories
INSERT INTO Categories (name, description) VALUES
    ('Homework', 'Assignments and homework tasks for school or college'),
    ('Presentations', 'Tasks related to preparing and delivering presentations'),
    ('Research', 'Tasks involving research for academic or personal projects'),
    ('Projects', 'Group or individual projects for classes or extracurricular activities'),
    ('Personal', 'Personal tasks and to-dos'),
    ('Work', 'Tasks related to work or professional projects'),
    ('Health', 'Tasks related to health and fitness'),
    ('Finance', 'Financial tasks, budgeting, and expenses'),
    ('Travel', 'Travel planning and itinerary tasks'),
    ('Miscellaneous', 'Other tasks that do not fit into the above categories');