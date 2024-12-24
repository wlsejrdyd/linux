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
  const { username, password, name, email, phoneNumber } = req.body;
  const connection = await mysql.createConnection(dbConfig);

  try {
    const hashedPassword = await bcrypt.hash(password, 10);
    await connection.execute(
      'INSERT INTO users (username, password, name, email, phoneNumber) VALUES (?, ?, ?, ?, ?)',
      [username, hashedPassword, name, email, phoneNumber]
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
//app.get('/users', async (req, res) => {
//  const connection = await mysql.createConnection(dbConfig);
//  const [rows] = await connection.execute('SELECT username FROM users');
//  res.json(rows.map(row => row.username));
//  await connection.end();
//});
app.get('/users', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);
  const [rows] = await connection.execute('SELECT name FROM users');
  res.json(rows.map(row => row.name));
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
  try {
    const [rows] = await connection.execute(`
      SELECT
        tasks.*,
        users.name AS createdByName -- 작성자의 이름 가져오기
      FROM tasks
      LEFT JOIN users ON tasks.createdBy = users.username -- username과 조인
      ORDER BY
        CASE WHEN status = 'Incomplete' THEN 1 ELSE 2 END, createdAt DESC
      LIMIT ?
    `, [limit]);

    // KST로 변환 및 `name` 포함
    const tasks = rows.map(task => {
      // dueDate KST 변환
      const dueDate = new Date(task.dueDate);
      const kstDueDate = new Date(dueDate.getTime() + (9 * 60 * 60 * 1000));
      task.dueDate = kstDueDate.toISOString().split('T')[0]; // YYYY-MM-DD 형식

      // completedAt KST 변환 (완료된 경우만)
      if (task.completedAt) {
        const completedAt = new Date(task.completedAt);
        const kstCompletedAt = new Date(completedAt.getTime() + (9 * 60 * 60 * 1000));
        task.completedAt = kstCompletedAt.toISOString().split('T')[0]; // YYYY-MM-DD 형식
      } else {
        task.completedAt = '-'; // 완료되지 않은 경우 "-"
      }

      // createdBy를 name으로 대체
      return {
        ...task,
        createdBy: task.createdByName // 작성자 이름으로 대체
      };
    });

    res.json(tasks);
  } catch (error) {
    console.error('Error fetching tasks:', error);
    res.status(500).json({ error: 'Failed to fetch tasks' });
  } finally {
    await connection.end();
  }
});

app.post('/tasks', async (req, res) => {
  const { title, content, assignedTo, dueDate } = req.body;

  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  // assignedTo가 배열이므로 JSON 문자열로 저장
  const assignedToString = JSON.stringify(assignedTo);

  const connection = await mysql.createConnection(dbConfig);
  try {
    await connection.execute(`
      INSERT INTO tasks (title, content, assignedTo, dueDate, createdBy, completedAt)
      VALUES (?, ?, ?, ?, ?, NULL)
    `, [title, content, assignedToString, dueDate, req.session.user.username]);

    res.json({ success: true });
  } catch (error) {
    console.error('Error creating task:', error);
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

  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' }); // 비로그인 사용자
  }

  const connection = await mysql.createConnection(dbConfig);

  try {
    // Task 정보를 조회
    const [taskRows] = await connection.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    if (taskRows.length === 0) {
      return res.status(404).json({ error: 'Task not found' }); // 작업이 없을 때
    }

    const task = taskRows[0];

    // `assignedTo` 필드를 쉼표로 분리하여 배열로 변환
    const assignedToArray = task.assignedTo ? task.assignedTo.split(',') : [];

    // 현재 사용자가 `assignedTo` 배열에 포함되어 있는지 확인
    if (!assignedToArray.includes(req.session.user.username) && task.createdBy !== req.session.user.username) {
      return res.status(403).json({ error: 'Not authorized to complete this task' });
    }

    // Task 상태를 Complete로 업데이트하고 완료 시간 저장
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

// 사용자 정보 조회
app.get('/user-info', async (req, res) => {
  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const connection = await mysql.createConnection(dbConfig);
  try {
    const [rows] = await connection.execute(
      'SELECT email, phoneNumber FROM users WHERE id = ?',
      [req.session.user.id]
    );
    res.json(rows[0]);
  } catch (error) {
    console.error('Error fetching user info:', error);
    res.status(500).json({ error: 'Failed to fetch user info' });
  } finally {
    await connection.end();
  }
});

// 사용자 정보 수정
app.put('/update-user-info', async (req, res) => {
  const { email, phoneNumber } = req.body;
  if (!req.session.user) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const connection = await mysql.createConnection(dbConfig);
  try {
    await connection.execute(
      'UPDATE users SET email = ?, phoneNumber = ? WHERE id = ?',
      [email, phoneNumber, req.session.user.id]
    );
    res.json({ success: true });
  } catch (error) {
    console.error('Error updating user info:', error);
    res.status(500).json({ error: 'Failed to update user info' });
  } finally {
    await connection.end();
  }
});

// weblist 페이지 라우트
app.get('/weblist', (req, res) => {
  if (!req.session.user) return res.redirect('/login');
  res.sendFile(path.join(__dirname, 'public', 'weblist.html'));
});

// 서비스 목록 불러오기 API
app.get('/services', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);

  try {
    const [rows] = await connection.execute('SELECT * FROM weblist ORDER BY createdAt DESC');
    res.json(rows);
  } catch (error) {
    console.error('Error fetching services:', error);
    res.status(500).json({ error: 'Failed to fetch services' });
  } finally {
    await connection.end();
  }
});

// 서비스 생성 API
app.post('/services', async (req, res) => {
  const { serviceName, url, owner, description } = req.body;

  if (!serviceName || !url || !owner || !description) {
    return res.status(400).json({ error: 'All fields are required' });
  }

  const connection = await mysql.createConnection(dbConfig);

  try {
    await connection.execute(
      'INSERT INTO weblist (serviceName, url, owner, description) VALUES (?, ?, ?, ?)',
      [serviceName, url, owner, description]
    );
    res.json({ success: true });
  } catch (error) {
    console.error('Error creating service:', error);
    res.status(500).json({ error: 'Failed to create service' });
  } finally {
    await connection.end();
  }
});

// 서비스 수정
app.put('/services/:id', async (req, res) => {
  const { id } = req.params;
  const { serviceName, url, owner, description } = req.body;

  const connection = await mysql.createConnection(dbConfig);

  try {
    await connection.execute(
      'UPDATE weblist SET serviceName = ?, url = ?, owner = ?, description = ? WHERE id = ?',
      [serviceName, url, owner, description, id]
    );
    res.json({ success: true });
  } catch (error) {
    console.error('Error updating service:', error);
    res.status(500).json({ error: 'Failed to update service' });
  } finally {
    await connection.end();
  }
});

// 프로젝트 페이지 라우트
app.get('/projects', (req, res) => {
  if (!req.session.user) return res.redirect('/login');
  res.sendFile(path.join(__dirname, 'public', 'projects.html'));
});

app.get('/api/projects', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);

  try {
    // 프로젝트 목록 불러오기
    const [projects] = await connection.execute('SELECT * FROM projects');

    // 각 프로젝트의 체크리스트 불러오기
    for (let project of projects) {
      const [tasks] = await connection.execute(`
        SELECT taskName, completedBy, completedAt FROM project_tasks WHERE projectId = ?
      `, [project.id]);

      project.checklist = {};
      tasks.forEach(task => {
        project.checklist[task.taskName] = {
          completedBy: task.completedBy,
          completedAt: task.completedAt
        };
      });
    }

    res.json(projects);
  } catch (error) {
    console.error('Error fetching projects:', error);
    res.status(500).json({ error: 'Failed to fetch projects' });
  } finally {
    await connection.end();
  }
});

// 프로젝트 생성 API
app.post('/api/projects', async (req, res) => {
  const { projectName, pm, surveyPath, openDate } = req.body;

  if (!projectName || !pm || !surveyPath || !openDate) {
    return res.status(400).json({ error: 'All fields are required' });
  }

  const connection = await mysql.createConnection(dbConfig);

  try {
    const [result] = await connection.execute(
      `INSERT INTO projects (projectName, pm, surveyPath, openDate)
       VALUES (?, ?, ?, ?)`,
      [projectName, pm, surveyPath, openDate]
    );

    // 각 프로젝트에 대한 체크 항목 초기화
    const tasks = ['IP 발급', '방화벽 적용', 'VM 생성', 'OS 세팅', '접근 제어', 'V3 설치'];
    for (const task of tasks) {
      await connection.execute(
        `INSERT INTO project_tasks (projectId, taskName) VALUES (?, ?)`,
        [result.insertId, task]
      );
    }

    res.json({ success: true });
  } catch (error) {
    console.error('Error creating project:', error);
    res.status(500).json({ error: 'Failed to create project' });
  } finally {
    await connection.end();
  }
});

// 프로젝트 완료처리 라우트
app.put('/api/projects/:projectId/tasks/:taskName/complete', async (req, res) => {
  const { projectId, taskName } = req.params;
  const username = req.session.user.username;

  // KST로 현재 시간 설정
  const now = new Date();
  const kstCompletedAt = new Date(now.getTime() + (9 * 60 * 60 * 1000))
    .toISOString()
    .split('T')[0]; // YYYY-MM-DD 형식으로 변환

  const connection = await mysql.createConnection(dbConfig);

  try {
    // 사용자 이름 가져오기
    const [userRows] = await connection.execute(
      `SELECT name FROM users WHERE username = ?`,
      [username]
    );

    if (userRows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const completedByName = userRows[0].name;

    // 체크리스트 항목을 완료 상태로 업데이트
    await connection.execute(`
      UPDATE project_tasks
      SET completedBy = ?, completedAt = ?
      WHERE projectId = ? AND taskName = ?
    `, [completedByName, kstCompletedAt, projectId, taskName]);

    // 진행률 업데이트 (완료된 항목 수를 기준으로 백분율 계산)
    const [totalTasks] = await connection.execute(
      `SELECT COUNT(*) as total FROM project_tasks WHERE projectId = ?`,
      [projectId]
    );

    const [completedTasks] = await connection.execute(
      `SELECT COUNT(*) as completed FROM project_tasks WHERE projectId = ? AND completedAt IS NOT NULL`,
      [projectId]
    );

    const progress = Math.round((completedTasks[0].completed / totalTasks[0].total) * 100);

    await connection.execute(
      `UPDATE projects SET progress = ? WHERE id = ?`,
      [progress, projectId]
    );

    res.json({ success: true, progress, completedBy: completedByName, completedAt: kstCompletedAt });
  } catch (error) {
    console.error('Error completing task:', error);
    res.status(500).json({ error: 'Failed to complete task' });
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
  <title>작업 보드</title>
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
    .task-content {
      max-width: 300px;
      max-height: 80px;
      text-overflow: ellipsis;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    .user-menu {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    .user-icon {
      border: none;
      background: none;
      padding: 0;
    }
    .user-icon img {
      border: 2px solid #ccc;
    }
  </style>
</head>
<!-- Bootstrap JavaScript 및 Popper.js 추가 -->
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<body>
  <div class="container mt-5">
    <!-- 사용자 메뉴 아이콘 -->
    <div class="user-menu">
      <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle user-icon" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="/images/user-icon.png" alt="User Icon" class="rounded-circle" width="30" height="30">
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserInfoModal">회원정보 수정</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">비밀번호 변경</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">계정 탈퇴</a></li>
          <li><a class="dropdown-item" href="/logout">로그아웃</a></li>
        </ul>
      </div>
    </div>

    <!-- 회원정보 수정 -->
    <div class="modal fade" id="editUserInfoModal" tabindex="-1" aria-labelledby="editUserInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserInfoModalLabel">회원정보 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editUserInfoForm">
              <div class="mb-3">
                <label for="edit-email" class="form-label">이메일</label>
                <input type="email" class="form-control" id="edit-email" required>
              </div>
              <div class="mb-3">
                <label for="edit-phoneNumber" class="form-label">핸드폰 번호</label>
                <input type="tel" class="form-control" id="edit-phoneNumber" required>
              </div>
              <button type="submit" class="btn btn-primary">저장</button>
            </form>
          </div>
        </div>
      </div>
    </div>

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
                <label for="newPassword" class="form-label">새 비밀번호</label>
                <input type="password" class="form-control" id="newPassword" required>
              </div>
              <button type="submit" class="btn btn-primary">변경</button>
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
            <h5 class="modal-title" id="deleteAccountModalLabel">계정 탈퇴</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>정말로 계정을 탈퇴하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
            <button id="confirmDeleteAccount" class="btn btn-danger">탈퇴</button>
          </div>
        </div>
      </div>
    </div>

    <h1>작업 보드</h1>
    <a href="/projects" class="btn btn-secondary mb-3">프로젝트 보드</a>
    <a href="/weblist" class="btn btn-secondary mb-3">웹 서비스 목록</a>

    <h2>작업 생성</h2>
    <form id="task-form" class="form-inline mb-4">
      <div class="mb-3">
        <label for="title" class="form-label">제목</label>
        <input type="text" class="form-control" id="title" required>
      </div>
      <div class="mb-3">
        <label for="assignedTo" class="form-label">담당자 지정</label>
        <select class="form-control" id="assignedTo" multiple="multiple" required></select>
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
            <th></th>
          </tr>
        </thead>
        <tbody id="task-list"></tbody>
      </table>
    </div>
  </div>

<style>
  .select2-container {
    width: 100% !important;
  }
  .select2-selection--multiple {
    min-height: 38px;
  }
</style>


  <script>
  // Load users into the "Assign To" dropdown
  $(document).ready(function() {
  $('#assignedTo').select2({
    placeholder: '담당자 선택',
    allowClear: true
    });
  });

  // 사용자 목록 불러오기
  fetch('/users')
    .then(res => res.json())
    .then(users => {
      const assignToSelect = $('#assignedTo');
      users.forEach(user => {
        const option = new Option(user, user, false, false);
        assignToSelect.append(option);
      });
    })
    .catch(err => console.error('Error fetching users:', err));


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
            <td class="task-content">${task.content.replace(/\n/g, '<br>')}</td>
            <td>${task.assignedTo}</td>
            <td>${task.dueDate}</td>
            <td>${task.createdBy}</td>
            <td>
              ${task.status === 'Complete'
                ? `<span class="text-success">Complete</span>`
                : `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">완료버튼</button>`}
            </td>
            <td>${task.completedAt}</td>
            <td><button onclick="deleteTask(${task.id})" class="btn btn-danger btn-sm">삭제</button></td>
          `;
          taskList.appendChild(row);
        });
      });
  }

  function renderTaskList(tasks) {
    const taskList = document.getElementById('task-list');
    taskList.innerHTML = '';

    tasks.forEach(task => {
      // 담당자를 파싱하여 문자열로 변환
      const assignedTo = Array.from(document.querySelectorAll('#assignedTo option:checked'))
                        .map(option => option.value);

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${task.id}</td>
        <td>${task.title}</td>
        <td class="task-content">${task.content.replace(/\n/g, '<br>')}</td>
        <td>${assignedTo}</td>
        <td>${task.dueDate}</td>
        <td>${task.createdBy}</td>
        <td>
          ${task.status === 'Complete'
            ? `<span class="text-success">Complete</span>`
            : `<button onclick="completeTask(${task.id})" class="btn btn-success btn-sm">완료버튼</button>`}
        </td>
        <td>${task.completedAt}</td>
        <td><button onclick="deleteTask(${task.id})" class="btn btn-danger btn-sm">삭제</button></td>
      `;
      taskList.appendChild(row);
    });
  }

  // Create Task
  document.getElementById('task-form').addEventListener('submit', handleTaskSubmit);

  function handleTaskSubmit(e) {
    e.preventDefault();

    // 선택된 모든 담당자를 배열로 가져옵니다.
    const assignedToSelect = document.getElementById('assignedTo');
    const assignedTo = Array.from(assignedToSelect.selectedOptions).map(option => option.value);

    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const dueDate = document.getElementById('dueDate').value;

    // content가 비어있는 경우 경고 및 작업 중단
    if (!content) {
      alert('내용을 입력해주세요.');
      return;
    }

    const task = {
      title,
      content,
      assignedTo, // 배열 형태로 전송
      dueDate,
    };

    fetch('/tasks', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(task),
    })
      .then(res => {
        if (!res.ok) {
          throw new Error('Failed to create task');
        }
        return res.json();
      })
      .then(() => {
        alert('작업이 성공적으로 생성되었습니다.');
        window.location.reload(); // 작업 성공 시 페이지 새로고침
      })
      .catch(err => alert('작업 생성 중 오류가 발생했습니다: ' + err.message));
  }

  // Complete Task
  function completeTask(id) {
    fetch(`/tasks/${id}`, { method: 'PUT' })
      .then(res => {
        if (!res.ok) {
          throw new Error('Failed to complete task');
        }
        return res.json();
      })
      .then(() => {
        loadTasks(); // 완료 후 목록 새로고침
      })
      .catch(err => alert('Error completing task: ' + err.message));
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

  <script>
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
      if (!res.ok) throw new Error('비밀번호 변경 실패');
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
        if (!res.ok) throw new Error('계정 탈퇴 실패');
        return res.text();
      })
      .then(message => {
        alert(message);
        window.location.href = '/login';
      })
      .catch(err => alert(err.message));
  });

  // 정보 수정 핸들러
  document.getElementById('editUserInfoForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const email = document.getElementById('edit-email').value.trim();
    const phoneNumber = document.getElementById('edit-phoneNumber').value.trim();

    fetch('/update-user-info', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, phoneNumber }),
    })
      .then(res => {
        if (!res.ok) {
          throw new Error('회원정보 수정 실패');
        }
        alert('회원정보가 성공적으로 수정되었습니다.');
        bootstrap.Modal.getInstance(document.getElementById('editUserInfoModal')).hide();
      })
      .catch(err => alert(err.message));
  });

  // 초기값 설정 (API에서 사용자 정보 가져오기)
  fetch('/user-info')
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit-email').value = data.email || '';
      document.getElementById('edit-phoneNumber').value = data.phoneNumber || '';
    })
    .catch(err => console.error('Error fetching user info:', err));
  </script>
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
      <form id="registerForm">
        <div class="mb-3">
          <label for="username" class="form-label">ID</label>
          <input type="text" id="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">비밀번호</label>
          <input type="password" id="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="name" class="form-label">이름</label>
          <input type="text" id="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">이메일</label>
          <input type="email" id="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="phoneNumber" class="form-label">핸드폰 번호</label>
          <input type="tel" id="phoneNumber" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">회원가입</button>
        <p class="mt-3">Already have an account? <a href="/login">Login here</a></p>
      </form>

  <script>
  document.getElementById('registerForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const data = {
      username: document.getElementById('username').value,
      password: document.getElementById('password').value,
      name: document.getElementById('name').value,
      email: document.getElementById('email').value,
      phoneNumber: document.getElementById('phoneNumber').value,
    };

    fetch('/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
      .then(res => {
        if (!res.ok) {
          throw new Error('회원가입 실패');
        }
        return res.text();
      })
      .then(() => {
        alert('회원가입 성공');
        window.location.href = '/login';
      })
      .catch(err => alert(err.message));
  });
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
      Don't have an account? <a href="/register" class="btn btn-outline-secondary">회원가입</a>
    </p>
  </div>
</body>
</html>
```

### weblist.html
```
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>웹 서비스 목록</title>
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
    .service-box {
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      background-color: #f8f9fa;
      margin-bottom: 20px;
    }
    .service-content {
      max-width: 300px;
      max-height: 80px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    .user-menu {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    .user-icon {
      border: none;
      background: none;
      padding: 0;
    }
    .user-icon img {
      border: 2px solid #ccc;
    }
  </style>
</head>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<body>
  <div class="container mt-5">
    <!-- 사용자 메뉴 아이콘 -->
    <div class="user-menu">
      <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle user-icon" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="/images/user-icon.png" alt="User Icon" class="rounded-circle" width="30" height="30">
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserInfoModal">회원정보 수정</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">비밀번호 변경</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">계정 탈퇴</a></li>
          <li><a class="dropdown-item" href="/logout">로그아웃</a></li>
        </ul>
      </div>
    </div>

    <!-- 회원정보 수정 -->
    <div class="modal fade" id="editUserInfoModal" tabindex="-1" aria-labelledby="editUserInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserInfoModalLabel">회원정보 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editUserInfoForm">
              <div class="mb-3">
                <label for="edit-email" class="form-label">이메일</label>
                <input type="email" class="form-control" id="edit-email" required>
              </div>
              <div class="mb-3">
                <label for="edit-phoneNumber" class="form-label">핸드폰 번호</label>
                <input type="tel" class="form-control" id="edit-phoneNumber" required>
              </div>
              <button type="submit" class="btn btn-primary">저장</button>
            </form>
          </div>
        </div>
      </div>
    </div>

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
                <label for="newPassword" class="form-label">새 비밀번호</label>
                <input type="password" class="form-control" id="newPassword" required>
              </div>
              <button type="submit" class="btn btn-primary">변경</button>
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
            <h5 class="modal-title" id="deleteAccountModalLabel">계정 탈퇴</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>정말로 계정을 탈퇴하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
            <button id="confirmDeleteAccount" class="btn btn-danger">탈퇴</button>
          </div>
        </div>
      </div>
    </div>

    <h1>웹 서비스 목록</h1>
    <a href="/dashboard" class="btn btn-secondary mb-3">작업 보드</a>
    <a href="/projects" class="btn btn-secondary mb-3">프로젝트 보드</a>

    <h2>웹 서비스 등록</h2>
    <form id="web-form" class="form-inline mb-4">
      <div class="mb-3">
        <label for="serviceName" class="form-label">서비스 이름</label>
        <input type="text" class="form-control" id="serviceName" required>
      </div>
      <div class="mb-3">
        <label for="serviceURL" class="form-label">URL 주소</label>
        <input type="url" class="form-control" id="serviceURL" required>
      </div>
      <div class="mb-3">
        <label for="owner" class="form-label">담당자</label>
        <input type="text" class="form-control" id="owner" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">설명</label>
        <input type="text" class="form-control" id="description" required>
      </div>
      <button type="submit" class="btn btn-primary">생성</button>
    </form>

    <!-- 수정 모달 -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editServiceModalLabel">서비스 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="edit-service-form">
              <input type="hidden" id="edit-service-id">
              <div class="mb-3">
                <label for="edit-service-name" class="form-label">서비스 이름</label>
                <input type="text" class="form-control" id="edit-service-name" required>
              </div>
              <div class="mb-3">
                <label for="edit-service-url" class="form-label">URL 주소</label>
                <input type="text" class="form-control" id="edit-service-url" required>
              </div>
              <div class="mb-3">
                <label for="edit-service-owner" class="form-label">담당자</label>
                <input type="text" class="form-control" id="edit-service-owner" required>
              </div>
              <div class="mb-3">
                <label for="edit-service-description" class="form-label">설명</label>
                <textarea class="form-control" id="edit-service-description" rows="3" required></textarea>
              </div>
              <button type="submit" class="btn btn-primary">저장</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <h2>등록된 웹 서비스 목록</h2>
    <div class="service-box">
      <table class="table">
         <thead>
           <tr>
             <th>서비스 이름</th>
             <th>URL 주소</th>
             <th>담당자</th>
             <th>설명</th>
             <th></th>   <!-- 수정 버튼이 들어갈 열 -->
           </tr>
         </thead>
        <tbody id="service-list"></tbody>
      </table>
    </div>
  </div>

  <script>
    // Load Services
    function loadServices() {
      fetch('/services')
        .then(res => res.json())
        .then(services => {
          const serviceList = document.getElementById('service-list');
          serviceList.innerHTML = ''; // 기존 목록 지우기

          services.forEach(service => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${service.serviceName}</td>
              <td><a href="${service.url}" target="_blank">${service.url}</a></td>
              <td>${service.owner}</td>
              <td class="service-content">${service.description}</td>
              <td>
                <button class="btn btn-sm btn-warning" onclick="openEditModal(${service.id}, '${service.serviceName}', '${service.url}', '${service.owner}', '${service.description}')">수정</button>
              </td>
            `;
            serviceList.appendChild(row);
          });
        })
        .catch(err => console.error('Error loading services:', err));
    }
    // Create Service
    document.getElementById('web-form').addEventListener('submit', (e) => {
      e.preventDefault();

      const service = {
        serviceName: document.getElementById('serviceName').value,
        url: document.getElementById('serviceURL').value,
        owner: document.getElementById('owner').value,
        description: document.getElementById('description').value
      };

      fetch('/services', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(service)
      })
      .then(res => res.json())
      .then(() => {
        loadServices();
        document.getElementById('web-form').reset(); // 폼 리셋
      })
      .catch(err => console.error('Error creating service:', err));
    });

    function openEditModal(id, name, url, owner, description) {
      document.getElementById('edit-service-id').value = id;
      document.getElementById('edit-service-name').value = name;
      document.getElementById('edit-service-url').value = url;
      document.getElementById('edit-service-owner').value = owner;
      document.getElementById('edit-service-description').value = description;
      new bootstrap.Modal(document.getElementById('editServiceModal')).show();
    }

    document.getElementById('edit-service-form').addEventListener('submit', e => {
      e.preventDefault();
      const id = document.getElementById('edit-service-id').value;
      const service = {
        serviceName: document.getElementById('edit-service-name').value,
        url: document.getElementById('edit-service-url').value,
        owner: document.getElementById('edit-service-owner').value,
        description: document.getElementById('edit-service-description').value
      };

      fetch(`/services/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(service)
      })
        .then(() => {
          loadServices();
          bootstrap.Modal.getInstance(document.getElementById('editServiceModal')).hide();
        });
    });

    // Initial Load
    loadServices();
  </script>

  <script>
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
      if (!res.ok) throw new Error('비밀번호 변경 실패');
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
        if (!res.ok) throw new Error('계정 탈퇴 실패');
        return res.text();
      })
      .then(message => {
        alert(message);
        window.location.href = '/login';
      })
      .catch(err => alert(err.message));
  });

  // 정보 수정 핸들러
  document.getElementById('editUserInfoForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const email = document.getElementById('edit-email').value.trim();
    const phoneNumber = document.getElementById('edit-phoneNumber').value.trim();

    fetch('/update-user-info', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, phoneNumber }),
    })
      .then(res => {
        if (!res.ok) {
          throw new Error('회원정보 수정 실패');
        }
        alert('회원정보가 성공적으로 수정되었습니다.');
        bootstrap.Modal.getInstance(document.getElementById('editUserInfoModal')).hide();
      })
      .catch(err => alert(err.message));
  });

  // 초기값 설정 (API에서 사용자 정보 가져오기)
  fetch('/user-info')
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit-email').value = data.email || '';
      document.getElementById('edit-phoneNumber').value = data.phoneNumber || '';
    })
    .catch(err => console.error('Error fetching user info:', err));
  </script>
</body>
</html>
```

### projects.html
```
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>프로젝트 보드</title>
  <link rel="stylesheet" href="/css/bootstrap.min.css">
  <style>
    .form-inline {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
    }
    .task-box {
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      background-color: #f8f9fa;
      margin-bottom: 20px;
    }
    .due-date-input {
      max-width: 250px;
    }
    .checklist {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
    .checklist button {
      margin-right: 10px;
    }
    .completed-info {
      font-size: 0.9em;
      color: #555;
      margin-top: 5px;
    }
    .project-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .user-menu {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    .user-icon {
      border: none;
      background: none;
      padding: 0;
    }
    .user-icon img {
      border: 2px solid #ccc;
    }
  </style>
</head>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<body>
  <div class="container mt-5">
    <!-- 사용자 메뉴 아이콘 -->
    <div class="user-menu">
      <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle user-icon" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="/images/user-icon.png" alt="User Icon" class="rounded-circle" width="30" height="30">
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserInfoModal">회원정보 수정</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">비밀번호 변경</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">계정 탈퇴</a></li>
          <li><a class="dropdown-item" href="/logout">로그아웃</a></li>
        </ul>
      </div>
    </div>

    <!-- 회원정보 수정 -->
    <div class="modal fade" id="editUserInfoModal" tabindex="-1" aria-labelledby="editUserInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserInfoModalLabel">회원정보 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editUserInfoForm">
              <div class="mb-3">
                <label for="edit-email" class="form-label">이메일</label>
                <input type="email" class="form-control" id="edit-email" required>
              </div>
              <div class="mb-3">
                <label for="edit-phoneNumber" class="form-label">핸드폰 번호</label>
                <input type="tel" class="form-control" id="edit-phoneNumber" required>
              </div>
              <button type="submit" class="btn btn-primary">저장</button>
            </form>
          </div>
        </div>
      </div>
    </div>

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
                <label for="newPassword" class="form-label">새 비밀번호</label>
                <input type="password" class="form-control" id="newPassword" required>
              </div>
              <button type="submit" class="btn btn-primary">변경</button>
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
            <h5 class="modal-title" id="deleteAccountModalLabel">계정 탈퇴</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>정말로 계정을 탈퇴하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
            <button id="confirmDeleteAccount" class="btn btn-danger">탈퇴</button>
          </div>
        </div>
      </div>
    </div>

    <h1>프로젝트 보드</h1>
    <a href="/dashboard" class="btn btn-secondary mb-3">작업 보드</a>
    <a href="/weblist" class="btn btn-secondary mb-3">웹 서비스 항목</a>

    <h2>프로젝트 생성</h2>
    <form id="project-form" class="form-inline mb-4">
      <input type="text" class="form-control mb-2" id="projectName" placeholder="프로젝트 이름" maxlength="20" required>
      <input type="text" class="form-control mb-2" id="surveyPath" placeholder="조사양식 경로" required>
      <div class="mb-2">
        <label for="pm" class="form-label">PM</label>
        <input type="text" class="form-control due-date-input" id="pm" required>
      </div>
      <div class="mb-2">
        <label for="openDate" class="form-label">오픈 예정일</label>
        <input type="date" class="form-control due-date-input" id="openDate" required>
      </div>
      <button type="submit" class="btn btn-primary mb-2">생성</button>
    </form>

    <!-- 완료된 프로젝트 보기 토글 버튼 -->
    <button id="toggle-completed-btn" class="btn btn-info mb-4">완료된 프로젝트 보기</button>

    <h2>프로젝트 목록</h2>
    <div id="project-list" class="task-box"></div>
    <div id="completed-project-list" class="task-box" style="display: none;"></div>
  </div>

  <script>
   // 프로젝트 목록 불러오기
   function loadProjects(showCompleted = false) {
     fetch('/api/projects')
       .then(res => res.json())
       .then(projects => {
         const projectList = document.getElementById('project-list');
         const completedProjectList = document.getElementById('completed-project-list');
         projectList.innerHTML = '';
         completedProjectList.innerHTML = '';
         projects.forEach(project => {
           const div = document.createElement('div');
           div.classList.add('mb-4');
           div.innerHTML = `
             <div class="project-header">
               <h4>${project.projectName} (${project.progress}%)</h4>
               <p>PM: ${project.pm}</p>
             </div>
             <p>조사양식 경로: ${project.surveyPath}</p>
             <p>오픈일: ${formatDate(project.openDate)}</p>
             <div class="checklist" id="checklist-${project.id}">
               ${renderChecklist(project.id, project.checklist)}
             </div>
           `;
           if (project.progress === 100) {
               completedProjectList.appendChild(div);
             } else {
               projectList.appendChild(div);
             }
           });
           // 완료된 프로젝트 목록 표시 여부
           completedProjectList.style.display = showCompleted ? 'block' : 'none';
       })
       .catch(err => console.error('Error loading projects:', err));
   }

   // 완료된 프로젝트 보기 토글 기능
   document.getElementById('toggle-completed-btn').addEventListener('click', () => {
     const completedProjectList = document.getElementById('completed-project-list');
     const isVisible = completedProjectList.style.display === 'block';
     loadProjects(!isVisible);
     document.getElementById('toggle-completed-btn').textContent = isVisible ? '완료된 프로젝트 보기' : '완료된 프로젝트 숨기기';
   });

   // 프로젝트 생성
   document.getElementById('project-form').addEventListener('submit', e => {
     e.preventDefault();
     const project = {
       projectName: document.getElementById('projectName').value,
       pm: document.getElementById('pm').value,
       surveyPath: document.getElementById('surveyPath').value,
       openDate: document.getElementById('openDate').value
     };
     fetch('/api/projects', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify(project)
     })
     .then(() => {
       loadProjects();
       document.getElementById('project-form').reset();
     });
   });

   // 체크 리스트 완료 처리
   function completeChecklistItem(projectId, taskName) {
     fetch(`/api/projects/${projectId}/tasks/${encodeURIComponent(taskName)}/complete`, {
       method: 'PUT',
       headers: { 'Content-Type': 'application/json' }
     })
       .then(response => {
         if (!response.ok) {
           throw new Error('Failed to complete task');
         }
         return response.json();
       })
       .then(data => {
         loadProjects(); // 완료 후 프로젝트 목록 새로고침
       })
       .catch(err => console.error('Error completing task:', err));
   }


   // 체크리스트 렌더링 함수
   function renderChecklist(projectId, checklist) {
     const checklistItems = [
       'IP 발급',
       '방화벽 적용',
       'VM 생성',
       'OS 세팅',
       '접근 제어',
       'V3 설치'
     ];

     checklist = checklist || {};

     return checklistItems.map(item => {
       const isChecked = checklist[item] && checklist[item].completedAt;
       const completedBy = isChecked ? checklist[item].completedBy : '';
       const completedAt = isChecked ? formatDate(checklist[item].completedAt) : '';

       return `
         <div class="form-check mb-2">
           <input type="checkbox" class="form-check-input" id="check-${projectId}-${item}" ${isChecked ? 'checked disabled' : ''}>
           <label class="form-check-label" for="check-${projectId}-${item}">
             ${item} ${isChecked ? ` (완료: ${completedBy} @ ${completedAt})` : ''}
           </label>
           ${!isChecked ? `<button class="btn btn-sm btn-success" onclick="completeChecklistItem(${projectId}, '${item}')">완료</button>` : ''}
         </div>
       `;
     }).join('');
   }

   // 체크 항목 완료 처리
   function completeTask(projectId, taskId) {
     fetch(`/api/projects/${projectId}/tasks/${taskId}/complete`, { method: 'PUT' })
       .then(() => loadProjects())
       .catch(err => console.error('Error completing task:', err));
   }

  // 페이지 로드 시 프로젝트 목록 불러오기
  loadProjects();
  </script>

  <script>
  function formatDate(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);
    // KST로 변환 (UTC + 9)
    const kstDate = new Date(date.getTime() + (9 * 60 * 60 * 1000));

    // 날짜 추출
    const year = kstDate.getFullYear();
    const month = String(kstDate.getMonth() + 1).padStart(2, '0');
    const day = String(kstDate.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }
  </script>

  <script>
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
      if (!res.ok) throw new Error('비밀번호 변경 실패');
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
        if (!res.ok) throw new Error('계정 탈퇴 실패');
        return res.text();
      })
      .then(message => {
        alert(message);
        window.location.href = '/login';
      })
      .catch(err => alert(err.message));
  });

  // 정보 수정 핸들러
  document.getElementById('editUserInfoForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const email = document.getElementById('edit-email').value.trim();
    const phoneNumber = document.getElementById('edit-phoneNumber').value.trim();

    fetch('/update-user-info', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, phoneNumber }),
    })
      .then(res => {
        if (!res.ok) {
          throw new Error('회원정보 수정 실패');
        }
        alert('회원정보가 성공적으로 수정되었습니다.');
        bootstrap.Modal.getInstance(document.getElementById('editUserInfoModal')).hide();
      })
      .catch(err => alert(err.message));
  });

  // 초기값 설정 (API에서 사용자 정보 가져오기)
  fetch('/user-info')
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit-email').value = data.email || '';
      document.getElementById('edit-phoneNumber').value = data.phoneNumber || '';
    })
    .catch(err => console.error('Error fetching user info:', err));
  </script>
</body>
</html>
```

## taskdb DB
* 테이블 컬럼 추가 참고용 ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';
* 테이블 수정 참고용 ALTER TABLE tasks MODIFY completedAt DATETIME DEFAULT NULL;

### 작업 테이블 생성
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
```

ALTER TABLE users
ADD COLUMN email VARCHAR(255),
ADD COLUMN phoneNumber VARCHAR(15);

### 사용자 테이블 생성
```
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NULL,
  phoneNumber VARCHAR(15) NULL,
  role ENUM('admin', 'user') DEFAULT 'user'
)DEFAULT CHARSET=UTF8;
```

### 웹 서비스 테이블 생성
```
CREATE TABLE weblist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  serviceName VARCHAR(255) NOT NULL,
  url VARCHAR(255) NOT NULL,
  owner VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)DEFAULT CHARSET=UTF8;
```

### 프로젝트 테이블 생성
```
CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projectName VARCHAR(20) NOT NULL,
  pm VARCHAR(50) NOT NULL,
  surveyPath VARCHAR(255) NOT NULL,
  openDate DATETIME NOT NULL,
  progress INT DEFAULT 0
)DEFAULT CHARSET=UTF8;

CREATE TABLE project_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projectId INT,
  taskId INT,
  taskName VARCHAR(50),
  completedBy VARCHAR(50),
  completedAt DATETIME,
  FOREIGN KEY (projectId) REFERENCES projects(id)
)DEFAULT CHARSET=UTF8;
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

WORKDIR /app/node02

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3000

ENV TZ=Asia/Seoul

CMD ["node", "index.js"]
```