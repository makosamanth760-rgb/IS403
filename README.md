WPU Student Management System (Demo)

How to run (frontend only):
- Open `index.html` in a browser (double-click on Windows). It redirects to `login.html`.
  - Set API base (optional) in browser console: `localStorage.setItem('wpu_sms_api_base','http://localhost:3001')`

Pages:
- `login.html`: login and role routing
- `registrar.html`: add student, view HECAS eligibility
- `student.html`: profile, transcript, enroll/unenroll
- `dean.html`: allocate dormitory, view allocations

Data:
- Backend API (Node/Express + MySQL) in `server/`. Use real users from `WPUniversitydb` and email/password login.

Docs:
- See `docs/use-case.md` for UML use-case diagram (PlantUML).

Backend setup:
- Install Node.js 18+.
- In `server/`, run: `npm install`
- Create `.env` (copy `.env.example` values):
  - `PORT=3001`
  - `DB_HOST=localhost`
  - `DB_USER=yourUser`
  - `DB_PASSWORD=yourPass`
  - `DB_NAME=WPUniversitydb`
  - `JWT_SECRET=yourSecret`
- Ensure your MySQL has tables: `users(id,email,password_hash,role,name)`, `students`, `units`, `enrollments`, `transcript`, `dormitories`, `allocations` aligned with queries in `server/src/index.js`.
- Start API: `npm run dev` (in `server/`).
- In the browser console (once), set API base: `localStorage.setItem('wpu_sms_api_base','http://localhost:3001')`.


