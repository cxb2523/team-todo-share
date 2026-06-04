# 多用户待办事项应用

一个支持团队协作的待办事项管理应用，采用 Vue 3 + Laravel + MySQL + Pusher 技术栈。

## 功能特性

### 👥 用户认证
- 用户注册与登录
- JWT Token 认证
- 安全密码加密

### 📋 清单管理
- 创建、编辑、删除待办清单
- 清单拥有者管理
- 支持公开/私有清单

### 🔗 团队共享与权限系统
- 将清单分享给其他用户
- 三级权限体系：
  - **查看权限**：只能查看任务
  - **编辑权限**：可以创建和编辑任务
  - **管理权限**：可以分享、删除、管理权限
- 实时权限验证

### ✅ 任务管理
- 创建、编辑、删除任务
- 任务状态管理（待处理/进行中/已完成/已取消）
- 任务分配给团队成员
- 优先级设置（1-5级）
- 截止日期设置
- 按状态分组显示

### 🔔 实时通知
- 任务状态变更通知
- 清单共享通知
- 任务分配通知
- 实时 WebSocket 推送
- 通知中心（未读计数、标记已读）

### 🎨 用户界面
- 响应式设计
- 现代化 UI 风格
- 直观的操作流程
- 实时状态更新

## 技术架构

### 后端 (Laravel)
```
backend/
├── app/
│   ├── Models/
│   │   ├── User.php              # 用户模型
│   │   ├── TodoList.php          # 待办清单模型
│   │   ├── Task.php              # 任务模型
│   │   ├── ListShare.php         # 清单共享关系模型
│   │   └── Notification.php      # 通知模型
│   ├── Events/
│   │   ├── TaskStatusUpdated.php # 任务状态更新事件
│   │   └── ListShared.php        # 清单共享事件
│   └── Http/Controllers/Api/
│       ├── AuthController.php    # 认证控制器
│       ├── TodoListController.php # 清单控制器
│       ├── TaskController.php    # 任务控制器
│       └── NotificationController.php # 通知控制器
├── database/migrations/          # 数据库迁移文件
├── routes/
│   ├── api.php                   # API 路由
│   └── channels.php              # 广播通道路由
└── composer.json
```

### 前端 (Vue 3)
```
frontend/
├── src/
│   ├── views/
│   │   ├── Login.vue             # 登录页面
│   │   ├── Register.vue          # 注册页面
│   │   ├── Dashboard.vue         # 仪表盘（清单列表）
│   │   └── TodoListDetail.vue   # 清单详情页（任务管理）
│   ├── components/
│   │   └── NotificationToast.vue # 通知提示组件
│   ├── stores/
│   │   ├── auth.js               # 认证状态管理
│   │   ├── todo.js               # 清单任务状态管理
│   │   └── notifications.js      # 通知状态管理
│   ├── router/
│   │   └── index.js              # 路由配置
│   ├── axios.js                  # HTTP 客户端配置
│   ├── echo.js                   # WebSocket 配置
│   ├── App.vue                   # 根组件
│   └── main.js                   # 入口文件
├── index.html
├── package.json
├── vite.config.js
└── README.md
```

## 数据库设计

### users 表
- id, name, email, password, email_verified_at, remember_token, timestamps

### todo_lists 表
- id, title, description, owner_id (FK), is_public, timestamps

### list_shares 表
- id, todo_list_id (FK), user_id (FK), permission (view/edit/admin), timestamps
- 唯一索引：[todo_list_id, user_id]

### tasks 表
- id, todo_list_id (FK), title, description, status, assigned_to (FK), created_by (FK), due_date, priority, timestamps

### notifications 表
- id, user_id (FK), type, message, data (JSON), read, timestamps

## 权限验证逻辑

```php
// 用户模型中的权限检查方法
public function hasAccessToTodoList(TodoList $todoList, string $permission = 'view'): bool
{
    // 1. 所有者拥有全部权限
    if ($todoList->owner_id === $this->id) {
        return true;
    }

    // 2. 查找共享记录
    $share = $todoList->shares()->where('user_id', $this->id)->first();
    
    if (!$share) {
        // 3. 公开清单仅允许查看
        return $todoList->is_public && $permission === 'view';
    }

    // 4. 权限级别检查
    $permissionLevels = ['view' => 1, 'edit' => 2, 'admin' => 3];
    return $permissionLevels[$share->permission] >= $permissionLevels[$permission];
}
```

## 实时通知流程

```
1. 用户操作（更新任务状态、分享清单）
2. 触发 Laravel Event
3. Event 实现 ShouldBroadcast 接口
4. 通过 Pusher/WebSocket 广播
5. 前端 Laravel Echo 监听事件
6. 更新 UI 并显示通知
7. 创建数据库通知记录供后续查看
```

## 快速开始

### 后端启动
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 前端启动
```bash
cd frontend
npm install
npm run dev
```

## 核心 API 端点

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/register | 用户注册 |
| POST | /api/login | 用户登录 |
| POST | /api/logout | 用户登出 |
| GET | /api/todo-lists | 获取清单列表 |
| POST | /api/todo-lists | 创建清单 |
| POST | /api/todo-lists/{id}/share | 分享清单 |
| DELETE | /api/todo-lists/{id}/unshare/{user} | 取消共享 |
| GET | /api/todo-lists/{list}/tasks | 获取任务列表 |
| POST | /api/todo-lists/{list}/tasks | 创建任务 |
| PUT | /api/todo-lists/{list}/tasks/{task} | 更新任务 |
| GET | /api/notifications/unread | 获取未读通知 |
| PUT | /api/notifications/{id}/read | 标记通知已读 |

## 浏览器兼容性

- Chrome (推荐)
- Firefox
- Safari
- Edge

## 许可证

MIT
