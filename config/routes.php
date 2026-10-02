<?php
declare(strict_types=1);

use App\Controllers;
use App\Controllers\System;
use App\Core\Router;

/**
 * 路由表：每条路由声明所需权限码，未声明的仅需登录。
 * resource() 自动注册 列表/数据/导出/详情/表单/保存/删除 7 条路由。
 */
return function (Router $r): void {
    // 认证
    $r->get('/login', Controllers\AuthController::class, 'loginPage', null, true);
    $r->post('/login', Controllers\AuthController::class, 'login', null, true);
    $r->post('/logout', Controllers\AuthController::class, 'logout');
    $r->get('/profile/password', Controllers\AuthController::class, 'passwordPage');
    $r->post('/profile/password', Controllers\AuthController::class, 'changePassword');

    // 仪表盘
    $r->get('/', Controllers\DashboardController::class, 'index', 'dashboard.view');

    // 客户管理
    $r->resource('/customers', Controllers\CustomerController::class, 'customer');
    $r->resource('/contacts', Controllers\ContactController::class, 'contact');
    $r->resource('/followups', Controllers\FollowupController::class, 'followup');

    // 商机管理
    $r->resource('/opportunities', Controllers\OpportunityController::class, 'opportunity');
    $r->post('/opportunities/convert', Controllers\OpportunityController::class, 'convert', 'project.edit');

    // 执行管理
    $r->resource('/projects', Controllers\ProjectController::class, 'project');
    $r->resource('/tasks', Controllers\TaskController::class, 'task');

    // 财务管理
    $r->resource('/receivables', Controllers\ReceivableController::class, 'receivable');
    $r->resource('/payables', Controllers\PayableController::class, 'payable');
    $r->resource('/transactions', Controllers\TransactionController::class, 'transaction');
    $r->resource('/expenses', Controllers\ExpenseController::class, 'expense');
    $r->post('/expenses/approve', Controllers\ExpenseController::class, 'approve', 'expense.approve');
    $r->post('/expenses/reject', Controllers\ExpenseController::class, 'reject', 'expense.approve');
    $r->post('/expenses/pay', Controllers\ExpenseController::class, 'pay', 'expense.pay');

    // 供应商管理
    $r->resource('/suppliers', Controllers\SupplierController::class, 'supplier');
    $r->resource('/purchases', Controllers\PurchaseController::class, 'purchase');
    $r->post('/purchases/payable', Controllers\PurchaseController::class, 'payable', 'payable.edit');

    // 系统管理
    $r->resource('/system/users', System\UserController::class, 'user');
    $r->resource('/system/roles', System\RoleController::class, 'role');
    $r->get('/system/roles/perms', System\RoleController::class, 'permsPage', 'role.edit');
    $r->post('/system/roles/perms', System\RoleController::class, 'savePerms', 'role.edit');
    $r->resource('/system/depts', System\DeptController::class, 'dept');
    $r->resource('/system/menus', System\MenuController::class, 'menu');
    $r->resource('/system/logs', System\LogController::class, 'log');
};
