CREATE DATABASE movie_booking;
USE movie_booking;

CREATE TABLE users(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
);

CREATE TABLE cinemas(
    cinema_id INT AUTO_INCREMENT PRIMARY KEY,
    cinema_name VARCHAR(100),
    location VARCHAR(100)
);

CREATE TABLE movies(
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    genre VARCHAR(50),
    duration INT,
    description TEXT
);

CREATE TABLE halls(
    hall_id INT AUTO_INCREMENT PRIMARY KEY,
    cinema_id INT,
    hall_name VARCHAR(50),
    total_seats INT,
    FOREIGN KEY(cinema_id) REFERENCES cinemas(cinema_id)
);

CREATE TABLE shows(
    show_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    hall_id INT,
    show_date DATE,
    show_time TIME,
    price DECIMAL(10,2),
    FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
    FOREIGN KEY(hall_id) REFERENCES halls(hall_id)
);

CREATE TABLE bookings(
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    show_id INT,
    seats VARCHAR(50),
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(user_id),
    FOREIGN KEY(show_id) REFERENCES shows(show_id)
);



SAMPLE DATA
INSERT INTO cinemas(cinema_name,location)
VALUES
('QFX Civil Mall','Kathmandu'),
('FCube Cinema','Kathmandu');

INSERT INTO movies(title,genre,duration,description)
VALUES
('Avengers','Action',180,'Marvel Movie'),
('Dune','Sci-Fi',165,'Sci-Fi Epic');

INSERT INTO halls(cinema_id,hall_name,total_seats)
VALUES
(1,'Hall A',100),
(2,'Hall B',120);

INSERT INTO shows(movie_id,hall_id,show_date,show_time,price)
VALUES
(1,1,'2026-06-20','10:00:00',500),
(1,2,'2026-06-20','13:00:00',600),
(2,1,'2026-06-20','16:00:00',550);