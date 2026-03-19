/* Auth/session + API client (backend-powered) */
(function(){
    const SESSION_KEY = 'wpu_sms_session_v1';
    const API_BASE = localStorage.getItem('wpu_sms_api_base') || 'http://localhost:3001';

    function saveSession(session){ localStorage.setItem(SESSION_KEY, JSON.stringify(session)); }
    function loadSession(){ const raw = localStorage.getItem(SESSION_KEY); return raw ? JSON.parse(raw) : null; }
    function clearSession(){ localStorage.removeItem(SESSION_KEY); }

    async function apiFetch(path, options={}){
        const s = loadSession();
        const headers = Object.assign({ 'Content-Type':'application/json' }, options.headers||{});
        if(s && s.token){ headers['Authorization'] = 'Bearer ' + s.token; }
        const res = await fetch(API_BASE + path, Object.assign({}, options, { headers }));
        if(res.status===401){ clearSession(); window.location.href='login.html'; return Promise.reject(new Error('Unauthorized')); }
        return res;
    }

    function requireRole(role){
        const s = loadSession();
        if(!s || s.role!==role){ window.location.href = 'login.html'; }
        return s;
    }

    function requireAnyRole(){
        const s = loadSession();
        if(!s){ window.location.href = 'login.html'; return null; }
        return s;
    }

    window.SMSApp = {
        setApiBase: (url)=>{ localStorage.setItem('wpu_sms_api_base', url); },
        apiFetch,
        login: async (email, password) => {
            const res = await fetch(API_BASE + '/api/login', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email, password }) });
            if(!res.ok) return null;
            const data = await res.json();
            const session = { token: data.token, role: data.user.role, name: data.user.name, userId: data.user.id, email: data.user.email };
            saveSession(session);
            return session;
        },
        logout: () => { clearSession(); window.location.href = 'login.html'; },
        getSession: loadSession,
        guardRole: requireRole,
        guardAny: requireAnyRole,
        navigateToRoleHome: () => {
            const s = loadSession();
            if(!s){ window.location.href='login.html'; return; }
            if(s.role==='registrar') window.location.href='registrar.html';
            else if(s.role==='student') window.location.href='student.html';
            else if(s.role==='dean') window.location.href='dean.html';
            else window.location.href='login.html';
        }
    };
})();


