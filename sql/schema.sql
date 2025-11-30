-- Create DB
CREATE DATABASE IF NOT EXISTS tutoring_system
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE tutoring_system;

-- Drop in FK-safe order for development
DROP TABLE IF EXISTS Notes;
DROP TABLE IF EXISTS Waitlist;
DROP TABLE IF EXISTS Reviews;
DROP TABLE IF EXISTS AppointmentStudent;
DROP TABLE IF EXISTS Availability;
DROP TABLE IF EXISTS TutorQualification;
DROP TABLE IF EXISTS Tutors;
DROP TABLE IF EXISTS Students;
DROP TABLE IF EXISTS Booked;
DROP TABLE IF EXISTS Subject;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Accounts;

-- =========================
-- Accounts
-- =========================
CREATE TABLE Accounts (
  account_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(100) NOT NULL UNIQUE,
  role         ENUM('student','tutor','admin') NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login   DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Users
-- =========================
CREATE TABLE Users (
  user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id    INT UNSIGNED NULL,
  first_name    VARCHAR(50) NOT NULL,
  last_name     VARCHAR(50) NOT NULL,
  phone         VARCHAR(20) NULL,
  university_id VARCHAR(20) NULL,
  CONSTRAINT fk_users_account
    FOREIGN KEY (account_id) REFERENCES Accounts(account_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Students
-- =========================
CREATE TABLE Students (
  user_id    INT UNSIGNED PRIMARY KEY,
  class_year INT NULL,
  major      VARCHAR(100) NULL,
  CONSTRAINT fk_students_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Tutors
-- =========================
CREATE TABLE Tutors (
  user_id                INT UNSIGNED PRIMARY KEY,
  bio                    TEXT NULL,
  max_concurrent_sessions INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_tutors_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Subject
-- =========================
CREATE TABLE Subject (
  subject_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(100) NOT NULL,
  department   VARCHAR(100) NULL,
  level        INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- TutorQualification
-- =========================
CREATE TABLE TutorQualification (
  user_id           INT UNSIGNED NOT NULL,
  subject_id        INT UNSIGNED NOT NULL,
  proficiency_level ENUM('beginner','intermediate','advanced','expert') NOT NULL,
  PRIMARY KEY (user_id, subject_id),
  CONSTRAINT fk_tutorqual_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_tutorqual_subject
    FOREIGN KEY (subject_id) REFERENCES Subject(subject_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Availability
-- =========================
CREATE TABLE Availability (
  availability_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  day_of_week     INT NOT NULL,       -- 0–6 or 1–7, your choice in code
  start_time      TIME NOT NULL,
  end_time        TIME NOT NULL,
  CONSTRAINT fk_availability_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Booked
-- =========================
CREATE TABLE Booked (
  booked_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id               INT UNSIGNED NOT NULL,   -- tutor or owner of the session
  subject_id            INT UNSIGNED NOT NULL,
  created_by_account_id INT UNSIGNED NOT NULL,
  session_start         DATETIME NOT NULL,
  session_end           DATETIME NOT NULL,
  max_students          INT NOT NULL DEFAULT 1,
  platform              ENUM('in_person','zoom','teams','google_meet','other') NULL,
  meeting_url           VARCHAR(255) NULL,
  status                ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at          DATETIME NULL,
  cancellation_reason   TEXT NULL,
  CONSTRAINT fk_booked_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_booked_subject
    FOREIGN KEY (subject_id) REFERENCES Subject(subject_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_booked_created_by
    FOREIGN KEY (created_by_account_id) REFERENCES Accounts(account_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- AppointmentStudent
-- (bridge: which students joined a booked session)
-- =========================
CREATE TABLE AppointmentStudent (
  booked_id     INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  booked_at     DATETIME NOT NULL,
  checked_in_at DATETIME NULL,
  PRIMARY KEY (booked_id, user_id),
  CONSTRAINT fk_apptstudent_booked
    FOREIGN KEY (booked_id) REFERENCES Booked(booked_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_apptstudent_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Reviews
-- =========================
CREATE TABLE Reviews (
  review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id   INT UNSIGNED NOT NULL,
  booked_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_reviews_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_reviews_booked
    FOREIGN KEY (booked_id) REFERENCES Booked(booked_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Waitlist
-- =========================
CREATE TABLE Waitlist (
  waitlist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booked_id   INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  position    INT NOT NULL,
  added_at    DATETIME NOT NULL,
  notified_at DATETIME NULL,
  expires_at  DATETIME NULL,
  CONSTRAINT fk_waitlist_booked
    FOREIGN KEY (booked_id) REFERENCES Booked(booked_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_waitlist_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Notes
-- =========================
CREATE TABLE Notes (
  note_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booked_id INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NOT NULL,
  note_text TEXT NOT NULL,
  CONSTRAINT fk_notes_booked
    FOREIGN KEY (booked_id) REFERENCES Booked(booked_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_notes_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
