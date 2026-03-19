import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import { createPool } from 'mysql2/promise';
import jwt from 'jsonwebtoken';

const app = express();
app.use(cors());
app.use(express.json());

const pool = createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'appuser',
  password: process.env.DB_PASSWORD || 'student123',
  database: process.env.DB_NAME || 'wpuniversitydb',
  waitForConnections: true,
  connectionLimit: 10
});

const JWT_SECRET = process.env.JWT_SECRET || 'dev-secret';

function signToken(user){
  return jwt.sign({ id: user.id, role: user.role, name: user.name, email: user.email }, JWT_SECRET, { expiresIn: '8h' });
}

function authMiddleware(requiredRoles){
  return (req,res,next)=>{
    const auth = req.headers.authorization || '';
    const token = auth.startsWith('Bearer ') ? auth.slice(7) : null;
    if(!token) return res.status(401).json({error:'Unauthorized'});
    try{
      const payload = jwt.verify(token, JWT_SECRET);
      if(requiredRoles && requiredRoles.length && !requiredRoles.includes(payload.role)){
        return res.status(403).json({error:'Forbidden'});
      }
      req.user = payload;
      next();
    }catch(e){
      return res.status(401).json({error:'Unauthorized'});
    }
  };
}

// Auth: POST /api/login { email, password } against Student/Staff tables (plain text demo)
app.post('/api/login', async (req,res)=>{
  const email = (req.body && req.body.email ? String(req.body.email) : '').trim();
  const password = (req.body && req.body.password ? String(req.body.password) : '').trim();
  if(!email || !password) return res.status(400).json({error:'Missing fields'});
  try{
    // Try student first
    const [sRows] = await pool.query('SELECT student_id AS id, first_name, last_name, email, password FROM Student WHERE LOWER(email) = LOWER(?)', [email]);
    if(sRows.length){
      const u = sRows[0];
      if(String(u.password || '').trim() !== password) return res.status(401).json({error:'Invalid credentials'});
      const name = `${u.first_name} ${u.last_name}`.trim();
      const token = signToken({ id: u.id, role: 'student', name, email: u.email });
      return res.json({ token, user: { id: u.id, role: 'student', name, email: u.email } });
    }
    // Else try staff
    const [tRows] = await pool.query('SELECT staff_id AS id, first_name, last_name, email, password FROM Staff WHERE LOWER(email) = LOWER(?)', [email]);
    if(!tRows.length) return res.status(401).json({error:'Invalid credentials'});
    const st = tRows[0];
    if(String(st.password || '').trim() !== password) return res.status(401).json({error:'Invalid credentials'});
    const name = `${st.first_name} ${st.last_name}`.trim();
    // Determine role
    const [[isReg]] = await pool.query('SELECT COUNT(*) AS c FROM Registrar_Staff WHERE staff_id = ?', [st.id]);
    const [[isDeanSvc]] = await pool.query('SELECT COUNT(*) AS c FROM StudentService_Staff WHERE staff_id = ?', [st.id]);
    const [[isDeanTbl]] = await pool.query('SELECT COUNT(*) AS c FROM Dean WHERE dean_id = ?', [st.id]);
    let role = 'staff';
    if(isReg.c) role = 'registrar';
    else if(isDeanSvc.c || isDeanTbl.c) role = 'dean';
    const token = signToken({ id: st.id, role, name, email: st.email });
    return res.json({ token, user: { id: st.id, role, name, email: st.email } });
  }catch(e){
    console.error(e);
    return res.status(500).json({error:'Server error'});
  }
});

// Registrar endpoints
app.get('/api/registrar/students', authMiddleware(['registrar','dean']), async (req,res)=>{
  try{
    const [rows] = await pool.query('SELECT student_id AS id, first_name AS givenName, last_name AS familyName, NULL AS program, NULL AS hecasEligible FROM Student');
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.post('/api/registrar/students', authMiddleware(['registrar']), async (req,res)=>{
  const { id, givenName, familyName, program, hecasEligible, email, password } = req.body || {};
  if(!id || !givenName || !familyName || !email || !password) return res.status(400).json({error:'Missing fields'});
  try{
    await pool.query('INSERT INTO Student (student_id, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)', [id, givenName, familyName, email, password]);
    res.status(201).json({ ok: true });
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.get('/api/registrar/hecas', authMiddleware(['registrar']), async (req,res)=>{
  try{
    const [rows] = await pool.query('SELECT student_id AS id, first_name AS givenName, last_name AS familyName, NULL AS program, 0 AS hecasEligible FROM Student');
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

// Student endpoints
app.get('/api/student/profile', authMiddleware(['student']), async (req,res)=>{
  try{
    const [rows] = await pool.query('SELECT student_id AS id, first_name AS givenName, last_name AS familyName, NULL AS program FROM Student WHERE student_id = ?', [req.user.id]);
    res.json(rows[0] || null);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.get('/api/student/transcript', authMiddleware(['student']), async (req,res)=>{
  try{
    const [rows] = await pool.query(`
      SELECT t.unit_code AS unitId, u.unit_name AS title, t.grade
      FROM Transcript t
      JOIN Unit u ON u.unit_code = t.unit_code
      WHERE t.student_id = ?
    `,[req.user.id]);
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.get('/api/student/units', authMiddleware(['student']), async (req,res)=>{
  try{
    const [rows] = await pool.query('SELECT unit_code AS id, unit_name AS title, credit_points AS creditPoints FROM Unit');
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.get('/api/student/enrollments', authMiddleware(['student']), async (req,res)=>{
  try{
    const [rows] = await pool.query(`
      SELECT o.unit_code AS unitId, CONCAT(o.semester,' ',o.year) AS term
      FROM Enrolment e
      JOIN Offering o ON o.offering_id = e.offering_id
      WHERE e.student_id = ?
    `, [req.user.id]);
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.post('/api/student/enroll', authMiddleware(['student']), async (req,res)=>{
  const { unitId, term } = req.body || {};
  if(!unitId) return res.status(400).json({error:'Missing fields'});
  try{
    // Pick an offering for the unit (latest year)
    const [offerings] = await pool.query('SELECT offering_id FROM Offering WHERE unit_code = ? ORDER BY year DESC, semester DESC LIMIT 1', [unitId]);
    if(!offerings.length) return res.status(400).json({error:'No offering available'});
    const offeringId = offerings[0].offering_id;
    // Generate enrolment_id
    const [[row]] = await pool.query('SELECT COALESCE(MAX(enrolment_id), 400000) + 1 AS nextId FROM Enrolment');
    await pool.query('INSERT INTO Enrolment (enrolment_id, student_id, offering_id, enrolment_date) VALUES (?, ?, ?, CURDATE())', [row.nextId, req.user.id, offeringId]);
    res.json({ ok: true });
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.post('/api/student/unenroll', authMiddleware(['student']), async (req,res)=>{
  const { unitId } = req.body || {};
  if(!unitId) return res.status(400).json({error:'Missing fields'});
  try{
    const [offerings] = await pool.query('SELECT offering_id FROM Offering WHERE unit_code = ? ORDER BY year DESC, semester DESC LIMIT 1', [unitId]);
    if(!offerings.length) return res.json({ ok: true });
    const offeringId = offerings[0].offering_id;
    await pool.query('DELETE FROM Enrolment WHERE student_id = ? AND offering_id = ?', [req.user.id, offeringId]);
    res.json({ ok: true });
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

// Dean endpoints
app.get('/api/dean/dorms', authMiddleware(['dean']), async (req,res)=>{
  try{
    const [rows] = await pool.query(`
      SELECT d.dormitory_id AS id, d.name, COALESCE(SUM(r.capacity),0) AS capacity
      FROM Dormitory d
      LEFT JOIN FloorLevels f ON f.dormitory_id = d.dormitory_id
      LEFT JOIN Room r ON r.floor_id = f.floor_id
      GROUP BY d.dormitory_id, d.name
    `);
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.get('/api/dean/allocations', authMiddleware(['dean']), async (req,res)=>{
  try{
    const [rows] = await pool.query(`
      SELECT rs.dormitory_id AS dormId, rs.room_id, d.name AS dormName, s.student_id AS studentId, s.first_name AS givenName, s.last_name AS familyName
      FROM Resident_Student rs
      JOIN Dormitory d ON d.dormitory_id = rs.dormitory_id
      JOIN Student s ON s.student_id = rs.student_id
    `);
    res.json(rows);
  }catch(e){ res.status(500).json({error:'Server error'}); }
});

app.post('/api/dean/allocate', authMiddleware(['dean']), async (req,res)=>{
  const { studentId, dormId } = req.body || {};
  if(!studentId || !dormId) return res.status(400).json({error:'Missing fields'});
  const conn = await pool.getConnection();
  try{
    await conn.beginTransaction();
    // Find rooms in dorm and occupancy
    const [rooms] = await conn.query(`
      SELECT r.room_id, r.capacity, COALESCE(x.cnt,0) AS occupants
      FROM Dormitory d
      JOIN FloorLevels f ON f.dormitory_id = d.dormitory_id
      JOIN Room r ON r.floor_id = f.floor_id
      LEFT JOIN (
        SELECT room_id, COUNT(*) AS cnt FROM Resident_Student GROUP BY room_id
      ) x ON x.room_id = r.room_id
      WHERE d.dormitory_id = ?
      ORDER BY r.room_id
    `, [dormId]);
    if(!rooms.length){ await conn.rollback(); return res.status(400).json({error:'No rooms for dorm'}); }
    const free = rooms.find(r => r.occupants < r.capacity);
    if(!free){ await conn.rollback(); return res.status(400).json({error:'Dorm full'}); }
    // Upsert Resident_Student
    await conn.query('DELETE FROM Resident_Student WHERE student_id = ?', [studentId]);
    await conn.query('INSERT INTO Resident_Student (student_id, dormitory_id, room_id, floor_number) VALUES (?, ?, ?, ?)', [studentId, dormId, free.room_id, 1]);
    await conn.commit();
    res.json({ ok: true });
  }catch(e){ await conn.rollback(); res.status(500).json({error:'Server error'}); }
  finally{ conn.release(); }
});

const port = process.env.PORT || 3001;
app.listen(port, ()=>{
  console.log(`WPU SMS API listening on http://localhost:${port}`);
});


