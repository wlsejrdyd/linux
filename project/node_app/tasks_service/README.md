# Tasks Web Service
* 개요 : 작업자들이 현재 진행중인 내부적인 요청 작업을 서비스화 시켜서 모두가 인지 할 수 있도록 됐으면 좋겠다고 생각해서 기획하게 됨.
* 주요기능
  + 작업 요청 건 등록
  + 작업 요청 리스트 확인
  + 담당자 할당
  + 작업 예정일 설정
  + 작업 요청 생성 시 담당자 브라우저 알림기능
* 추후 추가 할 기능들
  + 시스템팀 관리 웹서비스 URL 목록 페이지 생성
  + 시스템팀 관리 솔루션 설치 파일 다운로드 및 설치 매뉴얼 페이지 생성
  + 일일점검 체크 리스트
  + Dockerfile 작성하여 container 진행, k8s 도입하여 버전 관리

## 환경
* 환경
  + OS : Rocky Linux release 9.4
  + CPU : 2c
  + 메모리 : 4gb
  + applcation : nodejs(16.20.2)

## Source
### index.js
```
const express = require('express');
const session = require('express-session');
const mysql = require('mysql2/promise');
const bcrypt = require('bcrypt');
const path = require('path'); // 추가: 파일 경로 처리
const app = express();
const PORT = 3000;

// DB 연결 설정
const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'qew123!@#',
  database: 'taskdb'
};

// 세션 설정
app.use(session({
  secret: 'secret-key',
  resave: false,
  saveUninitialized: true
}));

// JSON 파싱 및 정적 파일 제공
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static('public'));

app.get('/', (req, res) => {
  // 로그인 상태 확인
  if (req.session.user) {
    // 로그인된 경우 대시보드로 이동
    res.redirect('/dashboard');
  } else {
    // 로그인되지 않은 경우 로그인 페이지로 리다이렉트
    res.redirect('/login');
  }
});

// 로그인 페이지 라우트
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// 사용자 로그인 처리
app.post('/login', async (req, res) => {
  const { username, password } = req.body;
  const connection = await mysql.createConnection(dbConfig);

  const [rows] = await connection.execute('SELECT * FROM users WHERE username = ?', [username]);
  if (rows.length === 0 || !await bcrypt.compare(password, rows[0].password)) {
    return res.status(401).json({ error: 'Invalid username or password' });
  }

  req.session.user = { id: rows[0].id, username: rows[0].username };
  res.redirect('/dashboard');
  await connection.end();
});

// 사용자 목록 API
app.get('/users', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);
  const [rows] = await connection.execute('SELECT username FROM users');
  res.json(rows.map(row => row.username));
  await connection.end();
});

// 대시보드 (작업 요청 게시판 포함)
app.get('/dashboard', (req, res) => {
  if (!req.session.user) return res.redirect('/login');
  res.sendFile(path.join(__dirname, 'public', 'dashboard.html'));
});

// 작업 목록 API
app.get('/tasks', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);
  const [rows] = await connection.execute(`
    SELECT * FROM tasks
    ORDER BY
      CASE WHEN status = 'Incomplete' THEN 1 ELSE 2 END, createdAt DESC
  `);
  res.json(rows);
  await connection.end();
});

//// 작업 생성 API
//app.post('/tasks', async (req, res) => {
//  const { title, content, assignedTo, dueDate } = req.body;
//  if (!req.session.user) return res.status(401).json({ error: 'Unauthorized' });
//
//  const connection = await mysql.createConnection(dbConfig);
//  await connection.execute(`
//    INSERT INTO tasks (title, content, assignedTo, dueDate, createdBy)
//    VALUES (?, ?, ?, ?, ?)
//  `, [title, content, assignedTo, dueDate, req.session.user.username]);
//
//  res.json({ success: true });
//  await connection.end();
//});
app.post('/tasks', async (req, res) => {
  console.log('Request Body:', req.body); // 요청 데이터 출력
  const { title, content, assignedTo, dueDate } = req.body;

  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const connection = await mysql.createConnection(dbConfig);
  try {
    await connection.execute(`
      INSERT INTO tasks (title, content, assignedTo, dueDate, createdBy)
      VALUES (?, ?, ?, ?, ?)
    `, [title, content, assignedTo, dueDate, req.session.user.username]);
    res.json({ success: true });
  } catch (error) {
    console.error('Error creating task:', error); // 에러 로그 출력
    res.status(500).json({ error: 'Failed to create task' });
  } finally {
    await connection.end();
  }
});


// 작업 완료 처리 API
app.put('/tasks/:id', async (req, res) => {
  const { id } = req.params;
  if (!req.session.user) return res.status(401).json({ error: 'Unauthorized' });

  const connection = await mysql.createConnection(dbConfig);
  const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);

  if (taskRows.length === 0) {
    return res.status(404).json({ error: 'Task not found' });
  }

  const task = taskRows[0];
  if (task.assignedTo !== req.session.user.username) {
    return res.status(403).json({ error: 'Not authorized to complete this task' });
  }

  await connection.execute('UPDATE tasks SET status = "complete" WHERE id = ?', [id]);
  res.json({ success: true });
  await connection.end();
});

// 로그아웃
app.get('/logout', (req, res) => {
  req.session.destroy();
  res.redirect('/login');
});

// 서버 실행
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});

// 새 사용자 추가 함수
async function addUser(username, password) {
  const hashedPassword = await bcrypt.hash(password, 10); // 비밀번호 해싱
  const connection = await mysql.createConnection(dbConfig);

  try {
    await connection.execute(
      'INSERT INTO users (username, password) VALUES (?, ?)',
      [username, hashedPassword]
    );
    console.log(`User "${username}" added successfully.`);
  } catch (error) {
    console.error('Error adding user:', error);
  } finally {
    await connection.end();
  }
}

// 사용자 계정 생성
//addUser('admin', 'admin123');
```

### dashboard.html
```
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h1>Dashboard</h1>
    <a href="/logout" class="btn btn-secondary mb-3">Logout</a>

    <h2>Create Task</h2>
    <form id="task-form">
      <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" required>
      </div>
      <div class="mb-3">
        <label for="content" class="form-label">Content</label>
        <textarea class="form-control" id="content" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label for="assignedTo" class="form-label">Assign To</label>
        <select class="form-control" id="assignedTo" required></select>
      </div>
      <div class="mb-3">
        <label for="dueDate" class="form-label">Due Date</label>
        <input type="datetime-local" class="form-control" id="dueDate" required>
      </div>
      <button type="submit" class="btn btn-primary">Create Task</button>
    </form>

    <h2>Task List</h2>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Content</th>
          <th>Assigned To</th>
          <th>Due Date</th>
          <th>Created By</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="task-list"></tbody>
    </table>
  </div>

  <script>
    // Load users into the "Assign To" dropdown
    fetch('/users')
      .then(res => res.json())
      .then(users => {
        const assignToSelect = document.getElementById('assignedTo');
        users.forEach(user => {
          const option = document.createElement('option');
          option.value = user;
          option.textContent = user;
          assignToSelect.appendChild(option);
        });
      });

    // Load tasks into the table
    fetch('/tasks')
      .then(res => res.json())
      .then(tasks => {
        const taskList = document.getElementById('task-list');
        tasks.forEach(task => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${task.id}</td>
            <td>${task.title}</td>
            <td>${task.content.replace(/\n/g, '<br>')}</td>
            <td>${task.assignedTo}</td>
            <td>${task.dueDate}</td>
            <td>${task.createdBy}</td>
            <td>
              ${task.status === 'Incomplete'
                ? `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">Complete</button>`
                : 'Completed'}
            </td>
          `;
          taskList.appendChild(row);
        });
      });

    // Complete a task
    function completeTask(id) {
      fetch(`/tasks/${id}`, { method: 'PUT' })
        .then(res => res.json())
        .then(() => location.reload());
    }
  </script>
</body>
</html>
```

### login.html
```
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h1>Login</h1>
    <form action="/login" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
</body>
</html>
```