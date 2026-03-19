/* Data Layer using localStorage for demo purposed SMS */
(function(){
    const STORAGE_KEY = 'wpu_sms_state_v1';
    const initialState = {
        users: [
            { id: 'u1', username: 'registrar', password: 'registrar123', role: 'registrar', name: 'Registrar Staff' },
            { id: 'u2', username: 'student1', password: 'student123', role: 'student', name: 'Jane Student' },
            { id: 'u3', username: 'dean', password: 'dean123', role: 'dean', name: 'Dean Service' }
        ],
        students: [
            { id: 's1001', givenName: 'Jane', familyName: 'Student', program: 'BSc Computer Science', hecasEligible: true },
            { id: 's1002', givenName: 'Mark', familyName: 'Tau', program: 'BA Economics', hecasEligible: false }
        ],
        units: [
            { id: 'CS101', title: 'Intro to Programming', creditPoints: 10 },
            { id: 'CS102', title: 'Data Structures', creditPoints: 10 },
            { id: 'CS103', title: 'Web Development', creditPoints: 10 }
        ],
        enrollments: [
            { studentId: 's1001', unitId: 'CS101', term: '2025-T1' },
            { studentId: 's1001', unitId: 'CS103', term: '2025-T1' }
        ],
        transcript: [
            { studentId: 's1001', unitId: 'CS101', grade: 'A' },
            { studentId: 's1001', unitId: 'CS103', grade: 'B+' }
        ],
        dormitories: [
            { id: 'D1', name: 'Kokopo Hall', capacity: 2 },
            { id: 'D2', name: 'Rabaul Hall', capacity: 2 }
        ],
        allocations: [
            { dormId: 'D1', studentId: 's1001' }
        ]
    };

    function load(){
        const raw = localStorage.getItem(STORAGE_KEY);
        if(!raw){
            localStorage.setItem(STORAGE_KEY, JSON.stringify(initialState));
            return JSON.parse(JSON.stringify(initialState));
        }
        try{ return JSON.parse(raw); }catch(e){
            localStorage.setItem(STORAGE_KEY, JSON.stringify(initialState));
            return JSON.parse(JSON.stringify(initialState));
        }
    }

    function save(state){
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    // Public API
    window.SMSData = {
        getState: () => load(),
        setState: save,
        findUser: (username, password) => {
            const s = load();
            return s.users.find(u => u.username === username && u.password === password) || null;
        },
        addStudent: (student) => {
            const s = load();
            s.students.push(student);
            save(s);
            return student;
        },
        listStudents: () => load().students,
        listUnits: () => load().units,
        listEnrollmentsByStudent: (studentId) => load().enrollments.filter(e => e.studentId === studentId),
        enrollUnit: (studentId, unitId, term) => {
            const s = load();
            if(!s.enrollments.find(e => e.studentId===studentId && e.unitId===unitId && e.term===term)){
                s.enrollments.push({ studentId, unitId, term });
                save(s);
            }
        },
        unenrollUnit: (studentId, unitId, term) => {
            const s = load();
            s.enrollments = s.enrollments.filter(e => !(e.studentId===studentId && e.unitId===unitId && e.term===term));
            save(s);
        },
        listTranscript: (studentId) => load().transcript.filter(t => t.studentId === studentId),
        isHECASEligible: (studentId) => {
            const st = load().students.find(s=>s.id===studentId);
            return !!(st && st.hecasEligible);
        },
        listDormitories: () => load().dormitories,
        listAllocations: () => load().allocations,
        allocateDorm: (studentId, dormId) => {
            const s = load();
            const current = s.allocations.filter(a => a.dormId===dormId);
            const dorm = s.dormitories.find(d => d.id===dormId);
            if(!dorm) return false;
            if(current.length >= dorm.capacity) return false;
            if(s.allocations.find(a=>a.studentId===studentId)){
                s.allocations = s.allocations.filter(a=>a.studentId!==studentId);
            }
            s.allocations.push({ dormId, studentId });
            save(s);
            return true;
        }
    };
})();


