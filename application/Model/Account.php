<?php

class Account extends BaseModel
{
    private static $TABLE = 'accounts';

    public function getAccounts()
    {
        $accounts = $this->db->select(self::$TABLE, '*');
        if (!$accounts || $this->hasError()) {
            return [];
        }
        return $accounts;
    }
    public function getAccountByID($id)
    {
        $accounts = $this->db->select(self::$TABLE, '*', [
            'id' => (int) $id,
        ]);
        if (!$accounts || $this->hasError()) {
            return null;
        }
        return $accounts[0];
    }
    public function getRandomAccount()
    {
        $accounts = $this->db->select(self::$TABLE, '*', [
            'ORDER' => $this->raw('RANDOM()'),
            'LIMIT' => 1,
        ]);
        if (!$accounts || $this->hasError()) {
            return null;
        }
        return $accounts[0];
    }
    public function updateCookie($id, $cookie)
    {
        return $this->db->update(self::$TABLE, [
            'cookie' => $cookie ?: '',
        ], [
            'id' => (int) $id,
        ]) == 1 && !$this->hasError();
    }
}
