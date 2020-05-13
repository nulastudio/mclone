<?php

class MCloneController extends BaseController
{
    private $account;
    private $gitee;

    public function __construct()
    {
        parent::__construct();
        $this->account = new Account();
    }
    private function checkRepo($repo)
    {
        return !empty($repo);
    }
    private function encrypt($data)
    {
        return base64_encode(encrypt(serialize($data)));
    }
    private function decrypt($data)
    {
        return unserialize(decrypt(base64_decode($data)));
    }

    public function mclone()
    {
        $repo = $this->post('repo');
        $repo = base64_decode($repo);
        if (!$this->checkRepo($repo)) {
            return jsonData(1, 'invalid git repository');
        }
        $account     = $this->account->getRandomAccount();
        if (empty($account)) {
            return jsonData(2, '没有可用账号');
        }
        /**
         * safeClone配置
         */
        $safeClone       = false;
        $force           = false;
        $safeCloneEnable = $this->app->getConfig('safeCloneEnable', false);
        if ($safeCloneEnable) {
            $forceSafeClone = $this->app->getConfig('forceSafeClone', false);
            if ($forceSafeClone) {
                $safeClone = true;
                $force     = true;
            } else {
                $safeClone = $this->app->getConfig('safeCloneDefault', false);
                $unsafe    = $this->post('unsafe');
                $safe      = $this->post('safe');
                if ($unsafe) {
                    $safeClone = false;
                }
                if ($safe) {
                    $safeClone = true;
                }
            }
        }

        $this->gitee = new Gitee($account['username'], $account['password'], $account['token'], $account['cookie'], $safeClone, $force);
        $repoToken   = $this->gitee->generateToken();
        if ($this->gitee->mclone($repo, $repoToken, $errorMessage)) {
            $cookie = $this->gitee->cookies();
            $this->account->updateCookie($account['id'], $cookie);
            return jsonData(0, '', [
                'token' => $this->encrypt([
                    'id'   => (int) $account['id'],
                    'repo' => $repoToken,
                ]),
                'safe'  => $safeClone,
                'force' => $force,
                'repo'  => "https://gitee.com/{$account['username']}/{$repoToken}.git",
            ]);
        } else {
            $this->userfriendlyErrorMessage($errorMessage);
            return jsonData(3, $errorMessage);
        }
    }

    public function status()
    {
        $token = $this->post('token');
        $token = $this->decrypt($token);
        if ($token) {
            $id = $token['id'] ?? '';
            $repo = $token['repo'] ?? '';
        }
        if (!$token || !$id || !$repo) {
            return jsonData(1, 'invalid arguments');
        }

        $account     = $this->account->getAccountByID((int)$id);
        $this->gitee = new Gitee($account['username'], $account['password'], $account['token'], $account['cookie']);
        if ($status = $this->gitee->status($repo, $errorMessage)) {
            return jsonData(0, '', [
                'status' => $status,
            ]);
        } else {
            $this->userfriendlyErrorMessage($errorMessage);
            return jsonData(2, $errorMessage);
        }
    }

    public function drop()
    {
        $token = $this->post('token');
        $token = $this->decrypt($token);
        if ($token) {
            $id = $token['id'] ?? '';
            $repo = $token['repo'] ?? '';
        }
        if (!$token || !$id || !$repo) {
            return jsonData(1, 'invalid arguments');
        }

        $account     = $this->account->getAccountByID((int)$id);
        $this->gitee = new Gitee($account['username'], $account['password'], $account['token'], $account['cookie']);
        if ($this->gitee->delete($repo, $errorMessage)) {
            return jsonData(0);
        } else {
            $this->userfriendlyErrorMessage($errorMessage);
            return jsonData(2, $errorMessage);
        }
    }

    private function userfriendlyErrorMessage(&$errorMessage)
    {
        $errorMessage = str_replace([
            '，请尝试升级为企业版',
        ], '', $errorMessage);
    }
}
