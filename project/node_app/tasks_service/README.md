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
    // dueDate KST 변환
    const dueDate = new Date(task.dueDate);
    const kstDueDate = new Date(dueDate.getTime() + (9 * 60 * 60 * 1000));
    task.dueDate = kstDueDate.toISOString().replace('T', ' ').substring(0, 16); // YYYY-MM-DD HH:MM 형식

    // completedAt KST 변환 (완료된 경우만)
    if (task.completedAt) {
      const completedAt = new Date(task.completedAt);
      const kstCompletedAt = new Date(completedAt.getTime() + (9 * 60 * 60 * 1000));
      task.completedAt = kstCompletedAt.toISOString().replace('T', ' ').substring(0, 16); // YYYY-MM-DD HH:MM 형식
    } else {
      task.completedAt = '-'; // 완료되지 않은 경우 "-"
    }

    return task;
  });

  res.json(tasks);
  await connection.end();
});


app.post('/tasks', async (req, res) => {
  const { title, content, assignedTo, dueDate } = req.body;

  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const connection = await mysql.createConnection(dbConfig);
  try {
    await connection.execute(`
      INSERT INTO tasks (title, content, assignedTo, dueDate, createdBy, completedAt)
      VALUES (?, ?, ?, ?, ?, NULL)
    `, [title, content, assignedTo, dueDate, req.session.user.username]);
    res.json({ success: true });
  } catch (error) {
    console.error('Error creating task:', error); // 에러 로그 출력
    res.status(500).json({ error: 'Failed to create task' });
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

// 작업 완료 처리 API
app.put('/tasks/:id', async (req, res) => {
  const { id } = req.params;

  // KST로 현재 시간 설정
  const now = new Date();
  const kstCompletedAt = new Date(now.getTime() + (9 * 60 * 60 * 1000))
    .toISOString()
    .replace('T', ' ')
    .substring(0, 19); // YYYY-MM-DD HH:MM:SS 형식

  if (!req.session.user) return res.status(401).json({ error: 'Unauthorized' });

  const connection = await mysql.createConnection(dbConfig);

  try {
    const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    if (taskRows.length === 0) {
      return res.status(404).json({ error: 'Task not found' });
    }

    const task = taskRows[0];
    if (task.assignedTo !== req.session.user.username) {
      return res.status(403).json({ error: 'Not authorized to complete this task' });
    }

    // Task 상태를 Complete로 업데이트하고 completedAt 시간 저장
    await connection.execute(
      'UPDATE tasks SET status = "Complete", completedAt = ? WHERE id = ?',
      [kstCompletedAt, id]
    );

    res.json({ success: true });
  } catch (error) {
    console.error('Error completing task:', error);
    res.status(500).json({ error: 'Failed to complete task' });
  } finally {
    await connection.end();
  }
});


// task 수정
app.put('/tasks/:id', async (req, res) => {
  const { id } = req.params;
  const { title, content } = req.body;

  const connection = await mysql.createConnection(dbConfig);

  try {
    const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    if (taskRows.length === 0) {
      return res.status(404).json({ error: 'Task not found' });
    }

    const task = taskRows[0];
    if (task.createdBy !== req.session.user.username && task.assignedTo !== req.session.user.username) {
      return res.status(403).json({ error: 'Unauthorized to edit this task' });
    }

    await connection.execute(
      'UPDATE tasks SET title = ?, content = ? WHERE id = ?',
      [title, content, id]
    );

    res.json({ success: true });
  } catch (error) {
    console.error('Error updating task:', error);
    res.status(500).json({ error: 'Failed to update task' });
  } finally {
    await connection.end();
  }
});

// task 삭제
app.delete('/tasks/:id', async (req, res) => {
  const { id } = req.params;

  const connection = await mysql.createConnection(dbConfig);

  try {
    const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    if (taskRows.length === 0) return res.status(404).json({ error: 'Task not found' });

    const task = taskRows[0];
    if (task.createdBy !== req.session.user.username && task.assignedTo !== req.session.user.username) {
      return res.status(403).json({ error: 'Unauthorized to delete this task' });
    }

    await connection.execute('DELETE FROM tasks WHERE id = ?', [id]);
    res.json({ success: true });
  } catch (error) {
    console.error('Error deleting task:', error);
    res.status(500).json({ error: 'Failed to delete task' });
  } finally {
    await connection.end();
  }
});

// task 상세 조회
app.get('/tasks/:id', async (req, res) => {
  const { id } = req.params;

  const connection = await mysql.createConnection(dbConfig);

  try {
    const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    if (taskRows.length === 0) return res.status(404).json({ error: 'Task not found' });

    res.json(taskRows[0]);
  } catch (error) {
    console.error('Error fetching task:', error);
    res.status(500).json({ error: 'Failed to fetch task' });
  } finally {
    await connection.end();
  }
});

// 프로젝트 게시판 페이지 라우트
app.get('/projects', (req, res) => {
  if (!req.session.user) return res.redirect('/login');
  res.sendFile(path.join(__dirname, 'public', 'projects.html'));
});

// 비밀번호 변경
app.post('/change-password', async (req, res) => {
  const { currentPassword, newPassword } = req.body;
  const connection = await mysql.createConnection(dbConfig);

  try {
    const [rows] = await connection.execute('SELECT * FROM users WHERE id = ?', [req.session.user.id]);
    if (!await bcrypt.compare(currentPassword, rows[0].password)) {
      return res.status(400).send('Current password is incorrect');
    }

    const hashedPassword = await bcrypt.hash(newPassword, 10);
    await connection.execute('UPDATE users SET password = ? WHERE id = ?', [hashedPassword, req.session.user.id]);
    res.send('Password changed successfully');
  } catch (error) {
    console.error('Error changing password:', error);
    res.status(500).send('Failed to change password');
  } finally {
    await connection.end();
  }
});

// 계정 탈퇴
app.delete('/delete-account', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);

  try {
    await connection.execute('DELETE FROM users WHERE id = ?', [req.session.user.id]);
    req.session.destroy();
    res.send('Account deleted successfully');
  } catch (error) {
    console.error('Error deleting account:', error);
    res.status(500).send('Failed to delete account');
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
  <title>개발중</title>
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
<!-- Bootstrap JavaScript 및 Popper.js 추가 -->
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<body>
  <div class="container mt-5">
    <a href="/logout" class="btn btn-secondary mb-3">Logout</a>
    <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">비밀번호 변경</button>
    <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">계정 삭제</button>
    <!-- 비밀번호 변경 모달 -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="changePasswordModalLabel">비밀번호 변경</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="changePasswordForm">
              <div class="mb-3">
                <label for="currentPassword" class="form-label">현재 비밀번호</label>
                <input type="password" class="form-control" id="currentPassword" required>
              </div>
              <div class="mb-3">
                <label for="newPassword" class="form-label">새로운 비밀변호</label>
                <input type="password" class="form-control" id="newPassword" required>
              </div>
              <button type="submit" class="btn btn-warning">변경</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- 계정 탈퇴 모달 -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete your account? This action cannot be undone.</p>
            <button id="confirmDeleteAccount" class="btn btn-danger">Delete Account</button>
          </div>
        </div>
      </div>
    </div>
    <h1>작업 보드</h1>
    <a href="/projects" class="btn btn-secondary mb-3">프로젝트 보드</a>

    <h2>작업 생성</h2>
    <form id="task-form" class="form-inline mb-4">
      <div class="mb-3">
        <label for="title" class="form-label">제목</label>
        <input type="text" class="form-control" id="title" required>
      </div>
      <div class="mb-3">
        <label for="assignedTo" class="form-label">담당자 지정</label>
        <select class="form-control" id="assignedTo" required></select>
      </div>
      <div class="mb-3">
        <label for="dueDate" class="form-label">완료 요청일</label>
        <input type="datetime-local" class="form-control due-date-input" id="dueDate" required>
      </div>
      <button type="submit" class="btn btn-primary">신청</button>
    </form>
      <div class="mb-3">
        <label for="content" class="form-label">내용</label>
        <textarea class="form-control" id="content" rows="3" required></textarea>
      </div>

    <h2>작업 목록</h2>
      <div>
        <label for="taskLimit" class="form-label me-2">Show:</label>
        <select id="taskLimit" class="form-select d-inline-block w-auto">
          <option value="10">10</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      <form id="filter-form" class="form-inline mb-4">
        <div class="mb-3">
          <label for="filterKeyword" class="form-label">제목/내용 검색</label>
          <input type="text" class="form-control" id="filterKeyword" placeholder="Enter keyword">
        </div>
        <button type="button" onclick="filterTasks()" class="btn btn-primary">검색</button>
      </form>
    <div class="task-box">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>제목</th>
            <th>내용</th>
            <th>담당자</th>
            <th>완료 요청일</th>
            <th>작성자</th>
            <th>상태</th>
            <th>완료일</th>
          </tr>
        </thead>
        <tbody id="task-list"></tbody>
      </table>
    </div>
  </div>
  <!-- Task 수정 모달 -->
  <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editTaskForm">
            <input type="hidden" id="editTaskId">
            <div class="mb-3">
              <label for="editTitle" class="form-label">제목</label>
              <input type="text" class="form-control" id="editTitle" required>
            </div>
            <div class="mb-3">
              <label for="editContent" class="form-label">내용</label>
              <textarea class="form-control" id="editContent" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-warning">저장</button>
          </form>
        </div>
      </div>
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

    // Task List limit
    document.getElementById('taskLimit').addEventListener('change', loadTasks);
    function loadTasks() {
      const limit = document.getElementById('taskLimit').value;
      fetch(`/tasks?limit=${limit}`)
        .then(res => res.json())
        .then(tasks => {
          const taskList = document.getElementById('task-list');
          taskList.innerHTML = ''; // 기존 목록 지우기

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
                  : `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">완료버튼</button>`}
                  <button onclick="editTask(${task.id})" class="btn btn-warning btn-sm me-1">수정</button>
                  <button onclick="deleteTask(${task.id})" class="btn btn-danger btn-sm">삭제</button>
              </td>
              <td>${task.completedAt}</td>
            `;
            taskList.appendChild(row);
          });
        });
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
              : `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">완료버튼</button>`}
              <button onclick="editTask(${task.id})" class="btn btn-warning btn-sm me-1">수정</button>
              <button onclick="deleteTask(${task.id})" class="btn btn-danger btn-sm">삭제</button>
          </td>
          <td>${task.completedAt}</td>
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

    // 비밀번호 변경 핸들러
    document.getElementById('changePasswordForm').addEventListener('submit', (e) => {
      e.preventDefault();

      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;

      fetch('/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ currentPassword, newPassword })
      })
        .then(res => {
          if (!res.ok) throw new Error('Password change failed');
          return res.text();
        })
        .then(message => {
          alert(message);
          document.getElementById('changePasswordForm').reset();
          bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
        })
        .catch(err => alert(err.message));
    });

    // 계정 탈퇴 핸들러
    document.getElementById('confirmDeleteAccount').addEventListener('click', () => {
      fetch('/delete-account', { method: 'DELETE' })
        .then(res => {
          if (!res.ok) throw new Error('Account deletion failed');
          return res.text();
        })
        .then(message => {
          alert(message);
          window.location.href = '/login';
        })
        .catch(err => alert(err.message));
    });

    // Edit Task 함수
    function editTask(id) {
      fetch(`/tasks/${id}`)
        .then(res => {
          if (!res.ok) throw new Error('Failed to fetch task data');
          return res.json();
        })
        .then(task => {
          document.getElementById('editTaskId').value = task.id;
          document.getElementById('editTitle').value = task.title;
          document.getElementById('editContent').value = task.content;
          new bootstrap.Modal(document.getElementById('editTaskModal')).show();
        })
        .catch(err => {
          console.error('Error fetching task:', err);
          alert('Error fetching task data');
        });
    }


    // Task 수정 핸들러
    document.getElementById('editTaskForm').addEventListener('submit', (e) => {
      e.preventDefault();

      const id = document.getElementById('editTaskId').value;
      const updatedTask = {
        title: document.getElementById('editTitle').value,
        content: document.getElementById('editContent').value
      };

      fetch(`/tasks/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updatedTask)
      })
        .then(res => {
          if (!res.ok) throw new Error('Failed to update task');
          return res.json();
        })
        .then(() => {
          alert('Task updated successfully');
          loadTasks();
          bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
        })
        .catch(err => {
          console.error('Error updating task:', err);
          alert(err.message);
        });
    });

    // Delete Task 함수
    function deleteTask(id) {
      if (!confirm('Are you sure you want to delete this task?')) return;

      fetch(`/tasks/${id}`, { method: 'DELETE' })
        .then(res => {
          if (!res.ok) throw new Error('Failed to delete task');
          return res.json();
        })
        .then(() => loadTasks())
        .catch(err => alert(err.message));
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
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completedAt DATETIME DEFAULT NULL
)DEFAULT CHARSET=UTF8;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user'
)DEFAULT CHARSET=UTF8;

# 테이블 컬러 추가 참고용 ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';

# 테이블 수정 참고용 ALTER TABLE tasks MODIFY completedAt DATETIME DEFAULT NULL;
```

## Docker
```
docker save -o task.tar task:1.0
docker load -i task.tar
```

* image 수정 후 commit 하면 /bin/bash 가 CMD 로 박힘, 커밋 한번 더 해주면 해결,
```
docker commit --change='CMD ["node", "index.js"]' <container> <image>
```
* 참고로 확인하는 방법은 docker inspect <image> 명령어로 CMD 라인을 보면 됨.

### Dockerfile
```
FROM node:16

# 작업 디렉토리 설정
WORKDIR /app/node02

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3000

ENV TZ=Asia/Seoul

CMD ["node", "index.js"]
```