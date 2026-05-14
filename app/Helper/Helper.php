<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Modules\OTP\Models\OtpCode;

function getConfig($key, $default = '')
{
    return config('config.' . $key, $default);
}

function getConstant($key, $default = '')
{
    return config('constant.' . $key, $default);
}

/**
 * @param $name
 * @param $params
 * @return string
 * Generate full url backend route. Ex: https://example.com/user?page=1&limit=10
 */
function backendRoute($name, $params = [])
{
    return route( getConstant('BACKEND_NAME_PREFIX') . '.' . $name, $params);
}

/**
 * @param $name
 * @return string
 * Get backend route name: ex: be.user.index
 */
function backendRouteName($name)
{
    return getConstant('BACKEND_NAME_PREFIX') . '.' . $name;
}

/**
 * @param $name
 * @param $params
 * @return string
 * Generate full url client route. Ex: https://example.com/user?page=1&limit=10
 */
function clientRoute($name, $params = [])
{
    return route( getConstant('CLIENT_NAME_PREFIX') . '.' . $name, $params);
}

/**
 * @param $name
 * @return string
 * Get client route name: ex: client.user.index
 */
function clientRouteName($name)
{
    return getConstant('CLIENT_NAME_PREFIX') . '.' . $name;
}

function getSTTBackend($entities, $index)
{
    return getConstant('BACKEND_PAGINATE', 10) * ($entities->currentPage() -1) + 1 + $index;
}

function apiGuard()
{
    return Auth::guard('api');
}

// Common
function toSql($query)
{
    return sql_binding($query->toSql(), $query->getBindings());
}

function arrayGet($array, $key, $default = null)
{
    return Arr::get($array, $key, $default);
}
// End Common

// Auth
function isBeLogin()
{
    return beGuard()->check();
}

function beGuard()
{
    return Auth::guard(getConstant('BACKEND_NAME_PREFIX'));
}

function clientGuard()
{
    return Auth::guard(getConstant('CLIENT_NAME_PREFIX'));
}

function clientCheck()
{
    return Auth::guard(getConstant('CLIENT_NAME_PREFIX'))->check();
}

function clientUser()
{
    return Auth::guard(getConstant('CLIENT_NAME_PREFIX'))->user();
}

function clientUserId()
{
    return clientCheck() ? clientUser()->getKey() : null;
}
// End Auth

function sql_binding($sql, $bindings)
{
    $boundSql = str_replace(['%', '?'], ['%%', '%s'], $sql);
    foreach ($bindings as &$binding) {
        if ($binding instanceof \DateTime) {
            $binding = $binding->format('\'Y-m-d H:i:s\'');
        } elseif (is_string($binding)) {
            $binding = "'$binding'";
        }
    }
    $boundSql = vsprintf($boundSql, $bindings);
    return $boundSql;
}

/* Redirect */
function backSystemError($msg = '')
{
    $msg = empty($msg) ? t('system_error') : $msg;
    return redirect()->back()->with('notification_error', $msg);
}

function backSystemSuccess($msg = '')
{
    $msg = empty($msg) ? t('success') : $msg;
    return redirect()->back()->with('notification_success', $msg);
}

function backSuccess($msg = '')
{
    $msg = empty($msg) ? t('success') : $msg;
    return redirect()->back()->with('notification_success', $msg);
}

function backRouteSuccess($routeName = '', $msg = '', $params = [])
{
    $msg = empty($msg) ? t('success') : $msg;
    return redirect()->route($routeName, $params)->with('notification_success', $msg);
}

function backRouteError($routeName = '', $msg = '', $params = [])
{
    $msg = empty($msg) ? "Lỗi hệ thống": $msg;
    return redirect()->route($routeName, $params)->with('notification_error', $msg);
}
/* End redirect */

function extractNameFromEmail($email)
{
    $parts = explode("@", $email);
    $username = arrayGet($parts, 0);
    return $username;
}

function t($key, $default = '')
{
    return empty(trans('messages.' . $key)) ? $default : trans('messages.' . $key);
}

/**
 * @param $key
 * @param $default
 * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Translation\Translator|mixed|string|null
 * Trans error code file
 */
function __tEC($key, $default = '')
{
    return empty(trans('error_code.' . $key)) ? $default : trans('error_code.' . $key);
}

function getSiteName()
{
    return getConfig('system.SITE_NAME', 'Laravel');
}

function randomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function randomNumber($length = 6)
{
    $generator = "1357902468";
    $result = "";
    for ($i = 1; $i <= $length; $i++) {
        $result .= substr($generator, (rand() % (strlen($generator))), 1);
    }
    return $result;
}

function isEmptyObject($obj)
{
    if (empty($obj)) {
        return true;
    }
    return collect($obj)->isEmpty();
}

function getInputOld($old, $default = '')
{
    return empty($old) ? $default : $old;
}

function selectedMenu($name)
{
    return str_contains(Route::currentRouteName(), $name) ? 'selected' : '';
}

function activeMenu($name)
{
    return str_contains(Route::currentRouteName(), $name) ? 'active' : '';
}

if (!function_exists('activeMenuClient')) {
    function activeMenuClient($routeName)
    {
        $contains = str_contains(strtolower(\Route::currentRouteName()), 'client.' . $routeName);
        return $contains ? 'menu-active' : '';
    }
}

/**
 * @param $content: string
 * @return float|int
 */
function getReadingMinutes($content)
{
    $text = strip_tags($content);
    $wordCount = str_word_count($text);
    return max(1, ceil($wordCount / 250));
}

/* ICON SVG AREA */
function emotionIcon()
{
    return '<svg aria-hidden="true" focusable="false" role="img" viewBox="0 0 16 16" width="16" height="16"
               fill="currentColor" class="octicon octicon-smiley"
               style="display: inline-block; user-select: none; vertical-align: text-bottom; overflow: visible;">
            <path
              d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0ZM1.5 8a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0Zm3.82 1.636a.75.75 0 0 1 1.038.175l.007.009c.103.118.22.222.35.31.264.178.683.37 1.285.37.602 0 1.02-.192 1.285-.371.13-.088.247-.192.35-.31l.007-.008a.75.75 0 0 1 1.222.87l-.022-.015c.02.013.021.015.021.015v.001l-.001.002-.002.003-.005.007-.014.019a2.066 2.066 0 0 1-.184.213c-.16.166-.338.316-.53.445-.63.418-1.37.638-2.127.629-.946 0-1.652-.308-2.126-.63a3.331 3.331 0 0 1-.715-.657l-.014-.02-.005-.006-.002-.003v-.002h-.001l.613-.432-.614.43a.75.75 0 0 1 .183-1.044ZM12 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM5 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm5.25 2.25.592.416a97.71 97.71 0 0 0-.592-.416Z"></path>
          </svg>';
}

function keyIcon()
{
    return '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="6.12573"
                      cy="11.6971"
                      r="3"
                      stroke="black"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round">
                    </circle>
                    <path
                      d="M8.26324 9.55964L14.3757 3.44714" stroke="black" stroke-width="1.5" stroke-linecap="round"
                      stroke-linejoin="round"></path>
                    <path d="M13.6257 4.19714L15.1257 5.69714"
                          stroke="black"
                          stroke-width="1.5" stroke-linecap="round"
                          stroke-linejoin="round">
                    </path>
                    <path d="M11.3757 6.44714L12.8757 7.94714" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>';
}

function nextIcon()
{
    return '<svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="0.67627" y="0.447144" width="20" height="20" rx="8" fill="#FFF0F6"></rect>
                  <path d="M9.17627 6.94714L12.6763 10.4471L9.17627 13.9471" stroke="#FF5064" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>';
}
/* ICON SVG AREA */

/**
 * @param $str
 * @return mixed|string
 * 0964047123 => 0964047***
 */
function hidePartPhoneNumber($str)
{
    $length = strlen($str);
    if ($length <= 3) {
        return $str;
    }
    return substr($str,0,$length - 3) . '***';
}

/**
 * @param $str
 * @return mixed|string
 * 0964047123 => 0964047***
 */
function hidePartEmail($str)
{
    if (!$str) {
        return;
    }
    $tmp = explode("@",$str);
    $prefix = $tmp[0];
    $subfix = $tmp[1];
    $length = strlen($prefix);
    if ($length <= 3) {
        return "***@" . $subfix;
    }
    return substr($prefix,0,$length - 3) . '***@' . $subfix;
}


function formatPriceCurrency($value = null)
{
    $result = is_null($value) ? '' : number_format((float)$value, 2, ',', ',');

    if (substr($result, -3) == ',00') {
        return substr($result, 0, strlen($result) - 3);
    }

    if (substr($result, -2) == ',0') {
        return substr($result, 0, strlen($result) - 2);
    }

    return $result;
}

/**
 * @param $path: laravel.log
 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|string
 */
function showLogOnWeb($path = null)
{
    $lines = file(storage_path("logs/" . $path));

    // Get param blocks, mặc định 50
    $lineCount = (int) request('blocks', 50);
    if ($lineCount > 100) {
        return "Log file max 100 blocks, bạn đang yêu cầu $lineCount blocks";
    }

    // Gộp thành block
    $blocks = [];
    $currentBlock = '';

    foreach ($lines as $line) {
        if (preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
            if ($currentBlock !== '') {
                $blocks[] = $currentBlock;
            }
            $currentBlock = $line;
        } else {
            $currentBlock .= $line;
        }
    }

    if ($currentBlock !== '') {
        $blocks[] = $currentBlock;
    }

    // Giữ N block gần nhất
    $blocks = array_slice($blocks, -$lineCount);
    $blocks = array_reverse($blocks);

    // Render block
    $htmlBlocks = array_map(function ($block) {
        $escaped = htmlentities($block);
        $lines = explode("\n", $escaped);

        $firstLine = array_shift($lines);

        if (str_contains($firstLine, 'ERROR')) {
            $firstLine = '<span class="error">' . $firstLine . '</span>';
        }

        $htmlBlock = '<pre>' . $firstLine . "\n" . implode("\n", $lines) . '</pre>';

        return $htmlBlock;
    }, $blocks);

    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Simple Laravel Log</title>
            <style>
                body {
                    background: #fff;
                    color: #333;
                    font-family: monospace;
                    padding: 20px;
                }
                .error {
                    color: red;
                    font-weight: bold;
                    margin-bottom: 5px;
                    display: block;
                }
                pre {
                    background: #f9f9f9;
                    padding: 10px;
                    border: 1px solid #ddd;
                    margin-bottom: 10px;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }
            </style>
        </head>
        <body>
            <h2>Simple Laravel Log (latest {$lineCount} blocks - newest first)</h2>
        HTML;

    $html .= implode("\n", $htmlBlocks);

    $html .= '</body></html>';

    return response($html);
}

function oldInput($old, $db)
{
    return empty($old) ? $db : $old;
}

/**
 * @param $price
 * @return string
 * 100.00 → 100
 * 99.50 → 99.5
 * 99.99 → 99.99
 */
function formatPrice($price)
{
    return rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.');
}
