CREATE TABLE Application (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    FIO VARCHAR(100),
    Phone_number CHAR(11),
    Email VARCHAR(255),
    Birth_day DATE,
    Gender VARCHAR(6),
    Biography TEXT
);

CREATE TABLE Favorite_pl (
    ID INT,
    Programming_language INT,
    FOREIGN KEY (ID) REFERENCES Application(ID) ON DELETE CASCADE
);
