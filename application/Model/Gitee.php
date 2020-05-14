<?php

use liesauer\SimpleHttpClient;

class Gitee extends BaseModel
{
    private $description = 'mirror by mclone';

    private $username;
    private $password;
    private $token;
    private $cookie;

    private $allowPrivateRepo = false;
    /**
     * 是否safeClone
     */
    private $safeClone = false;
    private $forceSafeClone = false;

    private $defaultHeader;

    public function __construct($username, $password, $token, $cookie, $allowPrivateRepo = false, $safeClone = false, $forceSafeClone = false)
    {
        $this->username      = $username ?: '';
        $this->password      = $password ?: '';
        $this->token         = $token ?: '';
        $this->cookie        = $cookie ?: '';

        $this->allowPrivateRepo = (bool)$allowPrivateRepo;
        $this->safeClone        = (bool)$safeClone;
        $this->forceSafeClone   = (bool)$forceSafeClone;

        $this->defaultHeader = [
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
            'Sec-Fetch-Dest'            => 'document',
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Sec-Fetch-Site'            => 'none',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-User'            => '?1',
            'Accept-Encoding'           => '',
            'Accept-Language'           => 'zh-CN,zh;q=0.9,en;q=0.8',
        ];
    }

    public function mclone($repo, $repoToken, &$errorMessage = null)
    {
        return $this->cloneRepo($repo, $repoToken, $errorMessage);
    }

    public function status($repoToken, &$errorMessage = null)
    {
        return $this->checkStatus($repoToken, $errorMessage);
    }

    public function delete($repoToken, &$errorMessage = null)
    {
        return $this->deleteRepo($repoToken, $errorMessage);
    }

    public function cookies()
    {
        return $this->cookie;
    }

    private function updateCookies($new)
    {
        $cookie = [];
        foreach (explode(';', $this->cookie) as $cookiePair) {
            $kv             = explode('=', $cookiePair);
            if (empty($kv[0]) || empty($kv[1])) {
                continue;
            }
            $cookie[$kv[0]] = $kv[1];
        }
        foreach (explode(';', $new) as $cookiePair) {
            $kv             = explode('=', $cookiePair);
            if (empty($kv[0]) || empty($kv[1])) {
                continue;
            }
            $cookie[$kv[0]] = $kv[1];
        }
        array_walk($cookie, function (&$value, $key) {
            $value = "{$key}={$value}";
        });
        return implode(';', array_values($cookie));
    }

    public function generateToken()
    {
        return 'repo' . md5(uniqid(microtime(true), true));
    }

    private function encrypt($val, $salt, $separator, $pubKey)
    {
        $encrypted = '';
        $pub_key   = openssl_pkey_get_public($pubKey);
        $success   = openssl_public_encrypt("{$salt}{$separator}{$val}", $encrypted, $pub_key);

        return $success ? base64_encode($encrypted) : null;
    }

    private function loginAccount(&$errorMessage = null)
    {
        $loginResponse = SimpleHttpClient::quickGet('https://gitee.com/login', $this->defaultHeader);

        $cookies   = implode(';', getMiddleTexts($loginResponse['header'], 'Set-Cookie: ', ';'));
        $loginHTML = $loginResponse['data'];

        $startPosLen = strlen(getMiddleText($loginHTML, 'content="', '" name="csrf-param"', 0, $startPos));

        $csrfToken     = getMiddleText($loginHTML, 'content="', '" name="csrf-token"', $startPos + $startPosLen);
        $encryptConfig = json_decode(getMiddleText($loginHTML, 'gon.encrypt=', ';'), true);
        $salt          = $csrfToken;
        $separator     = $encryptConfig['separator'];
        $pubKey        = $encryptConfig['password_key'];

        // check login
        $checkLoginResponse = SimpleHttpClient::quickPost('https://gitee.com/check_user_login', $this->defaultHeader + [
            'Accept'           => '*/*',
            'Sec-Fetch-Dest'   => 'empty',
            'X-CSRF-Token'     => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Sec-Fetch-Site'   => 'same-origin',
            'Sec-Fetch-Mode'   => 'cors',
            'Referer'          => 'https://gitee.com/login',
        ], $cookies, http_build_query([
            'user_login' => $this->username,
        ]));

        $checkLoginJSON = json_decode($checkLoginResponse['data'], true);

        if ($checkLoginJSON['result'] === 1 && $checkLoginJSON['failed_count'] > 2) {
            $errorMessage = "{$this->username}账号状态异常，无法登陆";
            return false;
        }

        // login
        $loginResponse = SimpleHttpClient::quickPost('https://gitee.com/login', $this->defaultHeader + [
            'Accept'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Sec-Fetch-Dest'   => 'empty',
            'X-CSRF-Token'     => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Sec-Fetch-Site'   => 'same-origin',
            'Sec-Fetch-Mode'   => 'cors',
            'Referer'          => 'https://gitee.com/login',
        ], $cookies, http_build_query([
            'encrypt_key'                  => 'password',
            'utf8'                         => '✓',
            'authenticity_token'           => $csrfToken,
            'redirect_to_url'              => '',
            'user[login]'                  => $this->username,
            'encrypt_data[user[password]]' => $this->encrypt($this->password, $salt, $separator, $pubKey),
            'user[remember_me]'            => '1',
        ]), [
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if (strpos($loginResponse['data'], 'redirect_to') === false) {
            $errorMessage = '登录失败';
            return false;
        }

        $cookies = implode(';', getMiddleTexts($loginResponse['header'], 'Set-Cookie: ', ';'));

        // 访问主页
        $loginResponse = SimpleHttpClient::quickGet("https://gitee.com/{$this->username}/dashboard/projects", $this->defaultHeader, $cookies);

        $cookies   = implode(';', getMiddleTexts($loginResponse['header'], 'Set-Cookie: ', ';'));
        $this->cookie = $this->updateCookies($cookies);

        $errorMessage = '';
        return true;
    }

    private function cloneRepo($repo, $repoToken, &$errorMessage = null)
    {
        $username = '';
        $password = '';
        $repo     = preg_replace_callback('/(?<=\/\/)(?<username>[\w\-]+):(?<password>[\w\-]+)@/', function ($matches) use (&$username, &$password) {
            if (isset($matches['username']) && !empty($matches['username'])) {
                $username = $matches['username'];
            }
            if (isset($matches['password']) && !empty($matches['password'])) {
                $password = $matches['password'];
            }
            return '';
        }, $repo);
        if (!$this->allowPrivateRepo) {
            $username = $password = '';
        }

        // // 访问主页
        // $indexResponse = SimpleHttpClient::quickGet("https://gitee.com/{$this->username}/dashboard/projects", $this->defaultHeader, $this->cookie);

        // $cookies   = implode(';', getMiddleTexts($indexResponse['header'], 'Set-Cookie: ', ';'));

        import:
        $importResponse = SimpleHttpClient::quickGet('https://gitee.com/projects/import/url', $this->defaultHeader, $this->cookie, '', [
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $cookies = implode(';', getMiddleTexts($importResponse['header'], 'Set-Cookie: ', ';'));

        if ($importResponse['http_code'] == 302) {
            if ($this->loginAccount($errorMessage)) {
                goto import;
            } else {
                return false;
            }
        }

        $importHTML = $importResponse['data'];

        $startPosLen = strlen(getMiddleText($importHTML, 'content="', '" name="csrf-param"', 0, $startPos));

        $csrfToken = getMiddleText($importHTML, 'content="', '" name="csrf-token"', $startPos + $startPosLen);

        // checkImport
        $checkImportResponse = SimpleHttpClient::quickGet('https://gitee.com/projects/check_project_private', $this->defaultHeader + [
            'Referer' => 'https://gitee.com/projects/import/url',
        ], $cookies, http_build_query([
            'import_url' => $repo,
        ]));

        $checkImportJSON = json_decode($checkImportResponse['data'], true);

        // 只有message字段，地址可能有问题
        // 有private=true和message字段，repo不存在或者私有仓库
        // check_success=true和message字段，repo可导入
        // 有账号密码且private=true的话就当作私有仓库尝试导入
        if ($username && $password && isset($checkImportJSON['private']) && $checkImportJSON['private']) {
            // 尝试导入
        } else if (!$checkImportJSON['check_success']) {
            $errorMessage = $checkImportJSON['message'];
            return false;
        }

        $repoHash = $repoToken;

        // create
        $createResponse = SimpleHttpClient::quickPost("https://gitee.com/{$this->username}/projects", $this->defaultHeader + [
            'Accept'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Sec-Fetch-Dest'   => 'empty',
            'X-CSRF-Token'     => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Sec-Fetch-Site'   => 'same-origin',
            'Sec-Fetch-Mode'   => 'cors',
            'Referer'          => 'https://gitee.com/projects/import/url',
        ], $cookies, http_build_query([
            'utf8'                    => '✓',
            'authenticity_token'      => $csrfToken,
            'project[import_url]'     => $repo,
            'user_sync_code'          => $username,
            'password_sync_code'      => $password,
            'project[name]'           => $repoHash,
            'project[namespace_path]' => $this->username,
            'project[path]'           => $repoHash,
            'project[description]'    => $this->description,
            'project[public]'         => $this->safeClone ? '0' : '1',
        ]), [
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($createResponse['http_code'] !== 302) {
            if ($createResponse['http_code'] == 401) {
                $errorMessage = '账号异常';
                return false;
            }
            $errorMessage = getMiddleText($createResponse['data'], 'Flash.show("', '", ');
            return false;
        }

        $errorMessage = '';
        return true;
    }

    private function checkStatus($repoToken, &$errorMessage = null)
    {
        $repoUrl = "https://gitee.com/{$this->username}/{$repoToken}";

        // redirect
        $redirectResponse = SimpleHttpClient::quickGet($repoUrl, $this->defaultHeader, $this->cookie, '', [
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($redirectResponse['http_code'] == 404) {
            $errorMessage = '镜像不存在';
            return false;
        }

        $cookies = implode(';', getMiddleTexts($redirectResponse['header'], 'Set-Cookie: ', ';'));

        $importHTML = $redirectResponse['data'];

        $startPosLen = strlen(getMiddleText($importHTML, 'content="', '" name="csrf-param"', 0, $startPos));

        $csrfToken = getMiddleText($importHTML, 'content="', '" name="csrf-token"', $startPos + $startPosLen);

        // checkFetch
        $checkFetchUrl      = "{$repoUrl}/check_fetch";
        $checkFetchResponse = SimpleHttpClient::quickGet($checkFetchUrl, $this->defaultHeader + [
            'Referer'          => $repoUrl,
            'X-CSRF-Token'     => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
        ], $cookies, '');

        if ($checkFetchResponse['http_code'] != 200) {
            $errorMessage = '镜像异常';
            return false;
        }

        $checkFetchJSON = json_decode($checkFetchResponse['data'], true);

        if ($checkFetchJSON['in_fetch'] === false) {
            $errorMessage = '';
            return 1;
        }

        $errorMessage = '镜像中';
        return -1;
    }

    public function listRepo(&$errorMessage = null) {
        $response = SimpleHttpClient::quickGet("https://gitee.com/api/v5/user/repos?access_token={$this->token}&type=all&sort=full_name&page=1&per_page=50");

        $json = json_decode($response['data'], true);
        if (isset($json['message'])) {
            $errorMessage = $json['message'];
            return [];
        }

        $errorMessage = '';
        return array_filter($json, function ($repo) {
            // 超过两小时的repo
            return $repo['description'] === $this->description &&
                   time() - strtotime($repo['created_at']) >= 60 * 60 * 2;
        });
    }

    private function deleteRepo($repoToken, &$errorMessage = null)
    {
        $response = SimpleHttpClient::quickRequest("https://gitee.com/api/v5/repos/{$this->username}/{$repoToken}?access_token={$this->token}", 'DELETE');

        if ($response['http_code'] != 204) {
            $json         = json_decode($response['data'], true);
            $errorMessage = $json['message'];
            return false;
        }

        $errorMessage = '';
        return true;
    }
}
