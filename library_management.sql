-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 11, 2025 at 07:11 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `total_copies` int NOT NULL DEFAULT '1',
  `available_copies` int NOT NULL DEFAULT '1',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`, `description`, `created_at`, `updated_at`) VALUES
(9, '333333', 'lanka lanka', 'nimal', NULL, NULL, 'History', 1, 0, NULL, '2025-08-07 03:32:42', '2025-08-07 13:31:58'),
(8, '1234567', 'sigiriya', 'namal perera', 'sarsavi', '2019', 'History', 1, 1, NULL, '2025-08-06 19:27:09', '2025-08-11 06:14:15'),
(2, '9780451524935', '1984 new new', 'George Orwell', 'Signet Classics', '1950', 'Science Fiction', 3, 2, 'A dystopian social science fiction novel about totalitarian control.', '2025-08-03 16:43:36', '2025-08-11 06:09:12'),
(3, '9780316769174', 'The Catcher in the Rye', 'J.D. Salinger', 'Little, Brown', '1991', 'Fiction', 4, 4, 'Coming-of-age story of Holden Caulfield in New York City.', '2025-08-03 16:43:36', '2025-08-03 16:43:36'),
(4, '9780140283334', 'Pride and Prejudice', 'Jane Austen', 'Penguin Classics', '1996', 'Romance', 6, 6, 'A romantic novel about Elizabeth Bennet and Mr. Darcy.', '2025-08-03 16:43:36', '2025-08-03 16:43:36'),
(5, '9780345391803', 'The Hitchhikers Guide to the Galaxy', 'Douglas Adams', 'Del Rey', '1995', 'Science Fiction', 2, 2, 'A comedic science fiction series about space travel.', '2025-08-03 16:43:36', '2025-08-03 16:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

DROP TABLE IF EXISTS `borrowings`;
CREATE TABLE IF NOT EXISTS `borrowings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `created_at`) VALUES
(1, 4, 2, '2025-08-07', '2025-08-21', NULL, 'borrowed', '2025-08-07 08:27:04'),
(2, 6, 2, '2025-08-07', '2025-08-21', '2025-08-07', 'returned', '2025-08-07 13:29:53'),
(3, 6, 9, '2025-08-07', '2025-08-21', NULL, 'borrowed', '2025-08-07 13:31:58'),
(4, 7, 2, '2025-08-11', '2025-08-25', '2025-08-11', 'returned', '2025-08-11 06:06:24'),
(5, 7, 8, '2025-08-11', '2025-08-25', '2025-08-11', 'returned', '2025-08-11 06:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Fiction', 'Fictional literature and novels', '2025-08-03 16:43:36'),
(2, 'Science Fiction', 'Science fiction and fantasy books', '2025-08-03 16:43:36'),
(3, 'Romance', 'Romance novels and stories', '2025-08-03 16:43:36'),
(4, 'Mystery', 'Mystery and thriller books', '2025-08-03 16:43:36'),
(5, 'Biography', 'Biographies and autobiographies', '2025-08-03 16:43:36'),
(6, 'History', 'Historical books and documentaries', '2025-08-03 16:43:36'),
(7, 'Science', 'Scientific and technical books', '2025-08-03 16:43:36'),
(8, 'Non-Fiction', 'Educational and informational books', '2025-08-03 16:43:36'),
(9, 'Technology', 'Computer science and technology books', '2025-08-03 16:43:36'),
(10, 'Literature', 'Classic and modern literature', '2025-08-03 16:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  `user_type` enum('librarian','student') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `user_type`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Library Administrator', NULL, NULL, 'librarian', '2025-08-03 16:43:36', '2025-08-11 06:28:31', 'active'),
(4, 'chandeepa', 'chandeepakumarasinghe448@gmail.com', '$2y$10$UzHLbD0RJL9WSmEQamymsOAgqIejU2IwyUmO2TgFkl.1A6i/J/APa', 'chandeepa', '0770216732', 'wijerama', 'student', '2025-08-06 08:56:09', '2025-08-11 07:09:32', 'active'),
(6, 'nihal', 'nihal@gmail.com', '$2y$10$2OKhbgqJFBYnXWOUhnqkRuKcXwoQ1e6tF52ou7cu/uRqNgrplRd1S', 'nihal perera', '0771234567', NULL, 'student', '2025-08-07 13:26:35', '2025-08-07 13:29:08', 'active'),
(7, 'maleesha', 'maleesha@gmail.com', '$2y$10$JsmUBMYiX/OfexntFbKhSOZZwScr5hZ5JD7bhgQoCUzdt/DsMXZva', 'maleesha', '0760943325', 'rathnapura', 'student', '2025-08-11 06:03:39', '2025-08-11 06:05:36', 'active');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
