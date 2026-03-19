-- Create schema
CREATE DATABASE WPUniversityDB;
USE WPUniversityDB;

-- Program
CREATE TABLE Program (
    ProgramID INT PRIMARY KEY AUTO_INCREMENT,
    ProgramName VARCHAR(100) NOT NULL,
    Description TEXT,
    DurationYears INT
);

-- Staff
CREATE TABLE Staff (
    StaffID INT PRIMARY KEY,
    FName VARCHAR(50),
    LName VARCHAR(50),
    Email VARCHAR(100) UNIQUE,
    Password VARCHAR(100),
    Address VARCHAR(255),
    Contact VARCHAR(20)
);

-- Student
CREATE TABLE Student (
    StudentID INT PRIMARY KEY,
    FName VARCHAR(50),
    LName VARCHAR(50),
    Email VARCHAR(100) UNIQUE,
    Password VARCHAR(100),
    ContactNumber VARCHAR(20),
    Gender ENUM('M','F'),
    ResidenceAddress VARCHAR(255),
    ProgramID INT,
    FOREIGN KEY (ProgramID) REFERENCES Program(ProgramID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- Registrar (is a Staff)
CREATE TABLE Registrar (
    StaffID INT PRIMARY KEY,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Student_Service (is a Staff)
CREATE TABLE Student_Service (
    StaffID INT PRIMARY KEY,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Dean (is a Staff)
CREATE TABLE Dean (
    StaffID INT PRIMARY KEY,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Dormitory
CREATE TABLE Dormitory (
    DormID INT PRIMARY KEY,
    Name VARCHAR(100),
    Description TEXT,
    DeanID INT,
    FOREIGN KEY (DeanID) REFERENCES Dean(StaffID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Floor
CREATE TABLE Floor (
    FloorNo INT,
    DormID INT,
    PRIMARY KEY (FloorNo, DormID),
    FOREIGN KEY (DormID) REFERENCES Dormitory(DormID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Room
CREATE TABLE Room (
    RoomNo INT,
    DormID INT,
    FloorNo INT,
    PRIMARY KEY (RoomNo, DormID, FloorNo),
    FOREIGN KEY (DormID, FloorNo) REFERENCES Floor(DormID, FloorNo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Residential & Non-Residential
CREATE TABLE Residential (
    StudentID INT PRIMARY KEY,
    RoomNo INT,
    DormID INT,
    FloorNo INT,
    DateAllocated DATE,
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (RoomNo, DormID, FloorNo) REFERENCES Room(RoomNo, DormID, FloorNo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE Non_Residential (
    StudentID INT PRIMARY KEY,
    ResidenceAddress VARCHAR(255),
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE Non_Residential_Address (
    AddressID INT PRIMARY KEY AUTO_INCREMENT,
    StudentID INT,
    Street VARCHAR(100),
    Town VARCHAR(100),
    Province VARCHAR(100),
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- Transcript
CREATE TABLE Transcript (
    TranscriptID INT PRIMARY KEY AUTO_INCREMENT,
    StudentID INT,
    ProgramID INT,
    DateIssued DATE,
    TotalCredits INT,
    SemesterGPA DECIMAL(3,2),
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (ProgramID) REFERENCES Program(ProgramID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- Unit
CREATE TABLE Unit (
    UnitCode VARCHAR(10) PRIMARY KEY,
    Name VARCHAR(100),
    Description TEXT,
    CreditPoints INT
);

-- Offering
CREATE TABLE Offering (
    OfferingID INT PRIMARY KEY,
    UnitCode VARCHAR(10),
    Semester VARCHAR(10),
    Year INT,
    FOREIGN KEY (UnitCode) REFERENCES Unit(UnitCode)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Enrollment (many-to-many Student - Offering)
CREATE TABLE Enrollment (
    StudentID INT,
    OfferingID INT,
    ProgramID INT,
    Grade VARCHAR(5),
    Mark INT,
    DateEnrolled DATE,
    PRIMARY KEY (StudentID, OfferingID),
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (OfferingID) REFERENCES Offering(OfferingID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (ProgramID) REFERENCES Program(ProgramID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- MealCard
CREATE TABLE MealCard (
    CardID INT PRIMARY KEY AUTO_INCREMENT,
    StudentID INT,
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

INSERT INTO Program (ProgramName, Description, DurationYears) VALUES
('Computer Science', 'Software development', 4),
('Business Administration', 'Management and finance', 4),
('Education', 'Teaching and curriculum design', 4),
('Hospitality Management', 'Tourism and service', 4),
('Environmental Science', 'Sustainability research', 4);

INSERT INTO Staff (StaffID, FName, LName, Email, Password, Address, Contact) VALUES
(1001, 'Alice', 'Brown', 'alice.brown@wpu.ac.pg', 'pass123', 'Madang Campus', '7012345678'),
(1002, 'Bob', 'Smith', 'bob.smith@wpu.ac.pg', 'pass456', 'Lae Campus', '7212345678'),
(1003, 'Cathy', 'Nguyen', 'cathy.nguyen@wpu.ac.pg', 'pass789', 'Port Moresby Campus', '7312345678'),
(1004, 'David', 'Lee', 'david.lee@wpu.ac.pg', 'pass321', 'Goroka Campus', '7412345678'),
(1005, 'Eva', 'Kaupa', 'eva.kaupa@wpu.ac.pg', 'pass654', 'Wewak Campus', '7512345678');

INSERT INTO Student (StudentID, FName, LName, Email, Password, ContactNumber, Gender, ResidenceAddress, ProgramID) VALUES
(2001, 'John', 'Doe', 'john.doe@student.wpu.ac.pg', 'stud123', '760000001', 'M', 'Madang Town', 1),
(2002, 'Jane', 'Amo', 'jane.amo@student.wpu.ac.pg', 'stud456', '760000002', 'F', 'Lae City', 2),
(2003, 'Peter', 'Kai', 'peter.kai@student.wpu.ac.pg', 'stud789', '760000003', 'M', 'Goroka Hills', 3),
(2004, 'Lucy', 'Tapo', 'lucy.tapo@student.wpu.ac.pg', 'stud321', '760000004', 'F', 'Port Moresby', 4),
(2005, 'Tom', 'Wane', 'tom.wane@student.wpu.ac.pg', 'stud654', '760000005', 'M', 'Wewak Bay', 5);

INSERT INTO Registrar (StaffID) VALUES
(1001),
(1002),
(1003);

INSERT INTO Student_Service (StaffID) VALUES
(1004),
(1005);

INSERT INTO Dean (StaffID) VALUES
(1005);

INSERT INTO Dormitory (DormID, Name, Description, DeanID) VALUES
(1, 'Madang Hall', 'Main dormitory near the library', 1002),
(2, 'Lae Lodge', 'Quiet dorm with garden access', 1003),
(3, 'Goroka Heights', 'Scenic dorm on the hilltop', 1004),
(4, 'Port View', 'Modern dorm with ocean view', 1005),
(5, 'Wewak Residence', 'Traditional dorm with cultural design', 1001);

INSERT INTO Floor (FloorNo, DormID) VALUES
(1, 1),
(2, 1),
(1, 2),
(1, 3),
(2, 3);

INSERT INTO Room (RoomNo, DormID, FloorNo) VALUES
(101, 1, 1),
(102, 1, 2),
(201, 2, 1),
(301, 3, 1),
(302, 3, 2);

INSERT INTO Residential (StudentID, RoomNo, DormID, FloorNo, DateAllocated) VALUES
(2001, 101, 1, 1, '2025-01-15'),
(2002, 102, 1, 2, '2025-01-16'),
(2003, 201, 2, 1, '2025-01-17');

INSERT INTO Non_Residential (StudentID, ResidenceAddress) VALUES
(2004, 'Koki Hill, Port Moresby, NCD'),
(2005, 'Boram Compound, Wewak, East Sepik Province');

INSERT INTO Transcript (StudentID, ProgramID, DateIssued, TotalCredits, SemesterGPA) VALUES
(2001, 1, '2025-06-01', 120, 3.45),
(2002, 2, '2025-06-01', 60, 3.20),
(2003, 3, '2025-06-01', 90, 3.80),
(2004, 4, '2025-06-01', 30, 3.10),
(2005, 5, '2025-06-01', 150, 3.95);

INSERT INTO Unit (UnitCode, Name, Description, CreditPoints) VALUES
('CS101', 'Intro to Programming', 'Basics of coding in Python', 3),
('BUS201', 'Marketing Principles', 'Core marketing strategies and tools', 3),
('EDU301', 'Curriculum Design', 'Designing effective learning plans', 4),
('HOS101', 'Food & Beverage Service', 'Hospitality operations and service', 2),
('ENV501', 'Climate Change Studies', 'Advanced environmental science topics', 5);

INSERT INTO Offering (OfferingID, UnitCode, Semester, Year) VALUES
(1, 'CS101', 'Semester 1', 2025),
(2, 'BUS201', 'Semester 1', 2025),
(3, 'EDU301', 'Semester 2', 2025),
(4, 'HOS101', 'Semester 1', 2025),
(5, 'ENV501', 'Semester 2', 2025);

INSERT INTO MealCard (StudentID) VALUES
(2001),
(2002),
(2003),
(2004),
(2005);





