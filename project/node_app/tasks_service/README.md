# Tasks Web Service
* 개요 : 작업 기록을 남기고 싶고, 실무자들이 어떤 작업을 맡았고, 어떤 작업이 밀려있는지 작업자들이 확인 할 수 있는 서비스가 없어서 기획 하게 됨. 있으면 좋을 거 같아서 기획 함. 안쓰면? 몰루...
* 주요기능
  + 작업 요청 건 등록
  + 작업 요청 리스트 확인
  + 담당자 할당
  + 작업 예정일 설정
* 추후 추가 할 기능들
  + [x] 작업 할당 된 사용자만 task status 변경 기능
  + [x] 회원가입 추가
  + [ ] 비밀변호 변경
  + [?] 알림 기능 추가
  + [ ] task list 날짜 및 사용자 별 필터링 추가
  + [ ] 시스템팀 관리 웹서비스 URL 목록 페이지 생성
  + [ ] 시스템팀 관리 솔루션 설치 파일 다운로드 및 설치 매뉴얼 페이지 생성
  + [ ] 일일점검 체크 리스트
  + [ ] Dockerfile 작성하여 container 진행, k8s 도입하여 버전 관리

## 환경
* 환경
  + OS : Rocky Linux release 9.4
  + CPU : 2c
  + 메모리 : 4gb
  + application : nodejs(16.20.2) , mariadb(10.5.22)

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

// 회원가입 페이지 라우트
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

// 사용자 회원가입 처리
app.post('/register', async (req, res) => {
  const { username, password } = req.body;
  const connection = await mysql.createConnection(dbConfig);

  try {
    const hashedPassword = await bcrypt.hash(password, 10); // 비밀번호 해싱
    await connection.execute(
      'INSERT INTO users (username, password) VALUES (?, ?)',
      [username, hashedPassword]
    );
    res.redirect('/login'); // 회원가입 후 로그인 페이지로 리디렉트
  } catch (error) {
    console.error('Error registering user:', error);
    res.status(500).send('Failed to register user');
  } finally {
    await connection.end();
  }
});

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

  req.session.user = { id: rows[0].id, username: rows[0].username, role: rows[0].role };
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
  // KST로 변환
  const tasks = rows.map(task => {
    const dueDate = new Date(task.dueDate);
    const kstDate = new Date(dueDate.getTime() + (9 * 60 * 60 * 1000)); // UTC+9
    task.dueDate = kstDate.toISOString().replace('T', ' ').substring(0, 19); // YYYY-MM-DD HH:MM:SS 형식
    return task;
  });
  res.json(rows);
  await connection.end();
});

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

// 작업 삭제 API (관리자만 가능)
app.delete('/tasks/:id', async (req, res) => {
  const { id } = req.params;
  const connection = await mysql.createConnection(dbConfig);

  try {
    await connection.execute('DELETE FROM tasks WHERE id = ?', [id]);
    res.json({ success: true });
  } catch (error) {
    console.error('Error deleting task:', error);
    res.status(500).json({ error: 'Failed to delete task' });
  } finally {
    await connection.end();
  }
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
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
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

  <!-- JavaScript 코드 -->
  <script>
    // 알림 권한 요청
    if (Notification.permission === 'default') {
      Notification.requestPermission();
    }

    // 알림 함수
    function showNotification(title) {
      if (Notification.permission === 'granted') {
        new Notification('New Task Created', {
          body: `Task: ${title} has been created.`,
          icon: '/icon.png' // 알림 아이콘 (옵션)
        });
      }
    }

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
              ${task.status === 'Complete'
                ? `<span class="text-success">Complete</span>`
                : `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">Mark Complete</button>`}
            </td>
          `;
          taskList.appendChild(row);
        });
      });

    // Create a new task (중복 이벤트 리스너 방지)
    const taskForm = document.getElementById('task-form');
    taskForm.addEventListener('submit', handleTaskSubmit);

    function handleTaskSubmit(e) {
      e.preventDefault();

      const task = {
        title: document.getElementById('title').value,
        content: document.getElementById('content').value,
        assignedTo: document.getElementById('assignedTo').value,
        dueDate: document.getElementById('dueDate').value
      };

      fetch('/tasks', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(task)
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`Failed to create task: ${response.statusText}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Task created:', data);
          showNotification(task.title); // 알림 호출
          location.reload(); // 성공 시 페이지 새로고침
        })
        .catch(error => console.error('Error creating task:', error));
    }

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

### register.html
```
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h1>Register</h1>
    <form action="/register" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <p class="mt-3">Already have an account? <a href="/login">Login here</a></p>
  </div>
</body>
</html>
```

## taskdb DB
### 
```
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  assignedTo VARCHAR(50) NOT NULL,
  dueDate DATETIME NOT NULL,
  createdBy VARCHAR(50) NOT NULL,
  status ENUM('Incomplete', 'Complete') DEFAULT 'Incomplete',
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)DEFAULT CHARSET=UTF8;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
)DEFAULT CHARSET=UTF8;

ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';

```