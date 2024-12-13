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
  + [x] task list 날짜 및 사용자 별 필터링 추가
  + [ ] 시스템팀 관리 웹 서비스 URL 목록 페이지 생성
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
  host: '10.104.1.1',
  user: 'task',
  port: '9981',
  password: 'qwe123QWE!@#',
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
  const { username, password, name } = req.body;
  const connection = await mysql.createConnection(dbConfig);

  try {
    const hashedPassword = await bcrypt.hash(password, 10);
    await connection.execute(
      'INSERT INTO users (username, password, name) VALUES (?, ?, ?)',
      [username, hashedPassword, name]
    );
    res.redirect('/login');
  } catch (error) {
    if (error.code === 'ER_DUP_ENTRY') {
      res.status(400).send('Username already exists. Please choose a different username.');
    } else {
      console.error('Error registering user:', error);
      res.status(500).send('Failed to register user');
    }
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
  const limit = parseInt(req.query.limit) || 10; // 기본값은 10
  const connection = await mysql.createConnection(dbConfig);
  const [rows] = await connection.execute(`
    SELECT * FROM tasks
    ORDER BY
      CASE WHEN status = 'Incomplete' THEN 1 ELSE 2 END, createdAt DESC
    LIMIT ?
  `, [limit]);

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

app.get('/tasks/latest', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);
  const [rows] = await connection.execute('SELECT MAX(createdAt) as latest FROM tasks');
  res.json({ latest: rows[0].latest });
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
```

### dashboard.html
```
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="/css/bootstrap.min.css">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
  <style>
    .form-inline {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
    }
    .form-inline .form-control {
      width: auto;
    }
    .due-date-input {
      max-width: 250px;
    }
    .filter-date-input {
      max-width: 200px;
    }
    .task-box {
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      background-color: #f8f9fa;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h1>Dashboard</h1>
    <a href="/logout" class="btn btn-secondary mb-3">Logout</a>

    <h2>Create Task</h2>
    <form id="task-form" class="form-inline mb-4">
      <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" required>
      </div>
      <div class="mb-3">
        <label for="assignedTo" class="form-label">Assign To</label>
        <select class="form-control" id="assignedTo" required></select>
      </div>
      <div class="mb-3">
        <label for="dueDate" class="form-label">Due Date</label>
        <input type="datetime-local" class="form-control due-date-input" id="dueDate" required>
      </div>
      <button type="submit" class="btn btn-primary">Create Task</button>
    </form>
      <div class="mb-3">
        <label for="content" class="form-label">Content</label>
        <textarea class="form-control" id="content" rows="3" required></textarea>
      </div>

    <h2>Filter Tasks</h2>
    <form id="filter-form" class="form-inline mb-4">
      <div class="mb-3">
        <label for="filterKeyword" class="form-label">Filter by Keyword</label>
        <input type="text" class="form-control" id="filterKeyword" placeholder="Enter keyword">
      </div>
      <button type="button" onclick="filterTasks()" class="btn btn-primary">Filter</button>
    </form>

    <h2>Task List</h2>
    <div class="task-box">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Content</th>
            <th>Assigned To</th>
            <th>Due Date</th>
            <th>Created By</th>
            <th>Action</th>
            <th>Completed At</th>
          </tr>
        </thead>
        <tbody id="task-list"></tbody>
      </table>
    </div>
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

    // Load Task List
    function loadTasks() {
      fetch('/tasks')
        .then(res => res.json())
        .then(tasks => renderTaskList(tasks));
    }

    // Render Task List
    function renderTaskList(tasks) {
      const taskList = document.getElementById('task-list');
      taskList.innerHTML = '';

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
          <td>${task.completedAt ? task.completedAt : '-'}</td>
        `;
        taskList.appendChild(row);
      });
    }

    // Create Task
    document.getElementById('task-form').addEventListener('submit', handleTaskSubmit);
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
      .then(res => res.json())
      .then(() => loadTasks());
    }

    // Complete Task
    function completeTask(id) {
      fetch(`/tasks/${id}`, { method: 'PUT' })
        .then(() => loadTasks());
    }

    // Filter Tasks by Keyword
    function filterTasks() {
      const filterKeyword = document.getElementById('filterKeyword').value.trim().toLowerCase();

      fetch('/tasks')
        .then(res => res.json())
        .then(tasks => {
          const filteredTasks = tasks.filter(task =>
            task.title.toLowerCase().includes(filterKeyword) ||
            task.content.toLowerCase().includes(filterKeyword)
          );
          renderTaskList(filteredTasks);
        });
    }

    // Initial Load
    loadTasks();

    let latestTaskTime = null;
    // 5초마다 새로운 Task가 있는지 확인
    setInterval(checkForNewTasks, 5000);

    function checkForNewTasks() {
      fetch('/tasks/latest')
        .then(res => res.json())
        .then(data => {
          if (!latestTaskTime) {
            latestTaskTime = data.latest;
          } else if (data.latest !== latestTaskTime) {
            latestTaskTime = data.latest;
            loadTasks(); // 새로운 Task가 있으면 Task List 새로고침
          }
        })
        .catch(err => console.error('Error checking for new tasks:', err));
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
  <link rel="stylesheet" href="/css/bootstrap.min.css">
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
    <p class="mt-3">
      Don't have an account? <a href="/register" class="btn btn-outline-secondary">Register here</a>
    </p>
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
  <link rel="stylesheet" href="/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h1>Register</h1>
    <form action="/register" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">login ID</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" id="name" name="name" required>
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
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user'
)DEFAULT CHARSET=UTF8;

ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';
```

## Docker
docker save -o task.tar task:1.0
docker load -i task.tar

### Dockerfile
```
FROM node:16

# 작업 디렉토리 설정
WORKDIR /app/node02

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3000

ENV TZ Asia/Seoul

CMD ["node", "index.js"]
```