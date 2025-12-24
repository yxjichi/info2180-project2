USE dolphin_crm;

DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(10),
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    company VARCHAR(100),
    type VARCHAR(20) NOT NULL,
    assigned_to INT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL
);

INSERT INTO users (firstname, lastname, email, password, role, created_at)
VALUES ('Admin', 'User', 'admin@project2.com', '$2y$12$gozfhKLAsAOBFI2GukFr5O0Kahbo2kx0t9qloUM1GOfGF/hOT6gU6', 'Admin', NOW());

INSERT INTO contacts (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at)
VALUES
('Mr', 'John', 'Brown', 'john@example.com', '876-555-1111', 'ABC Ltd', 'Sales Lead', 1, 1, NOW(), NOW()),
('Ms', 'Sara', 'King', 'sara@example.com', '876-555-2222', 'XYZ Inc', 'Support', 1, 1, NOW(), NOW());