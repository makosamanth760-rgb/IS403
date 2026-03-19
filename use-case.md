## WPU Student Management System - Use Case Overview

Actors:
- Registrar Staff
- Student
- Student Service Staff (Dean)

Key Use Cases:
- Registrar: Login/Logout, Add New Student, View HECAS Eligibility
- Student: Login/Logout, View Profile, View Transcript, Enroll in Units, Unenroll from Units
- Dean: Allocate Dormitory, View Dormitory List

UML Use-Case Diagram (PlantUML):

```plantuml
@startuml
left to right direction
actor Registrar
actor Student
actor Dean

rectangle "WPU Student Management System" {
  usecase UC1 as "Login/Logout"
  usecase UC2 as "Add New Student"
  usecase UC3 as "View HECAS Eligibility"
  usecase UC4 as "View Profile"
  usecase UC5 as "View Transcript"
  usecase UC6 as "Enroll in Units"
  usecase UC7 as "Unenroll from Units"
  usecase UC8 as "Allocate Dormitory"
  usecase UC9 as "View Dormitory List"
}

Registrar --> UC1
Registrar --> UC2
Registrar --> UC3

Student --> UC1
Student --> UC4
Student --> UC5
Student --> UC6
Student --> UC7

Dean --> UC1
Dean --> UC8
Dean --> UC9
@enduml
```

Notes:
- This demo uses localStorage for data persistence. Replace with a backend service and the provided `WPUniversitydb.sql` for production.


