<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BizException;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

class AuthController extends Controller
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 600;

    public function loginPage(): void
    {
        if (Auth::check()) {
            Response::redirect(url('/'));
            return;
        }
        $this->render('auth/login', ['title' => '登录'], null);
    }

    public function login(): void
    {
        $username = Request::str('username');
        $password = (string)Request::post('password', '');
        if ($username === '' || $password === '') {
            throw new BizException('请输入用户名和密码');
        }

        // 简单的登录失败限流（按会话）
        $fails = $_SESSION['login_fails'] ?? ['n' => 0, 't' => 0];
        if ($fails['n'] >= self::MAX_FAILS && time() - $fails['t'] < self::LOCK_SECONDS) {
            throw new BizException('失败次数过多，请 10 分钟后再试');
        }

        try {
            Auth::attempt($username, $password);
        } catch (BizException $e) {
            $_SESSION['login_fails'] = ['n' => $fails['n'] + 1, 't' => time()];
            throw $e;
        }
        unset($_SESSION['login_fails']);
        Logger::log('auth', 'login', Auth::id(), '登录成功');
        $this->success('登录成功', ['redirect' => url('/')]);
    }

    public function logout(): void
    {
        Logger::log('auth', 'logout', Auth::id());
        Auth::logout();
        $this->success('已退出');
    }

    public function passwordPage(): void
    {
        $this->render('auth/password', ['title' => '修改密码'], 'layout/blank');
    }

    public function changePassword(): void
    {
        $old = (string)Request::post('old_password', '');
        $new = (string)Request::post('new_password', '');
        $confirm = (string)Request::post('confirm_password', '');
        if (strlen($new) < 8) {
            throw new BizException('新密码至少 8 位');
        }
        if ($new !== $confirm) {
            throw new BizException('两次输入的新密码不一致');
        }
        $hash = DB::value('SELECT password FROM sys_user WHERE id = ?', [Auth::id()]);
        if (!$hash || !password_verify($old, (string)$hash)) {
            throw new BizException('原密码不正确');
        }
        DB::update('sys_user', ['password' => password_hash($new, PASSWORD_DEFAULT)], (int)Auth::id());
        Logger::log('auth', 'change_password', Auth::id());
        $this->success('密码已修改');
    }
}
