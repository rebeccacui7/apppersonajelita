<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    public function run(): void
    {
        $router = new Router();
        (require ROOT . '/config/routes.php')($router);

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->currentPath();
        $ajax = Request::isAjax();

        try {
            $route = $router->match($method, $path);
            if (!$route) {
                $this->abort(404, '页面不存在', $ajax);
                return;
            }
            [$class, $action, $perm, $public] = $route;

            if (!$public) {
                if (!Auth::check()) {
                    if ($ajax) {
                        Response::json(['code' => 401, 'msg' => '登录已过期，请重新登录']);
                    } else {
                        Response::redirect(url('login'));
                    }
                    return;
                }
                if ($perm && !Auth::can($perm)) {
                    $this->abort(403, '没有权限访问该功能', $ajax);
                    return;
                }
            }
            if ($method === 'POST' && !Csrf::verify()) {
                $this->abort(419, '页面已过期，请刷新后重试', $ajax);
                return;
            }

            (new $class())->$action();
        } catch (BizException $e) {
            $this->abort(400, $e->getMessage(), $ajax);
        } catch (\Throwable $e) {
            error_log((string)$e);
            $msg = config('app.debug') ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : '系统错误，请稍后再试';
            $this->abort(500, $msg, $ajax);
        }
    }

    /** 兼容 URL 重写、/index.php/xxx（PATH_INFO）以及子目录部署 */
    private function currentPath(): string
    {
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        if (str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script));
        } else {
            $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($dir !== '' && str_starts_with($uri, $dir)) {
                $uri = substr($uri, strlen($dir));
            }
        }
        return $uri === '' ? '/' : $uri;
    }

    private function abort(int $code, string $msg, bool $ajax): void
    {
        if ($ajax) {
            Response::json(['code' => $code, 'msg' => $msg]);
            return;
        }
        http_response_code($code === 400 ? 400 : $code);
        echo View::render('common/error', ['code' => $code, 'msg' => $msg], null);
    }
}
