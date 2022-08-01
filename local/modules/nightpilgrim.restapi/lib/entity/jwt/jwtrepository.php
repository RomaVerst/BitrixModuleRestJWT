<?php

namespace NightPilgrim\RestApi\Entity\Jwt;

class JwtRepository
{
    const SECRET_KEY = 'app_key';

    public function getUserIdByHash($hash)
    {
        $userId = JwtTable::getList([
             'filter' => ['secret_key' => $hash],
             'select' => ['user_id']
        ])->fetch()['user_id'];
        if (!$userId) {
            return false;
        }
        return $userId;
    }

    private function getHeader()
    {
        return  base64_encode(json_encode(['alg' => 'HS256','typ' => 'JWT']));
    }

    private function getPayload($userId)
    {
        return base64_encode(json_encode(['userId' => $userId]));
    }

    private function getSignature($secret, $userId)
    {
        $unsignedToken = $this->getHeader() . '.' . $this->getPayload($userId);
        return base64_encode(hash_hmac('sha256', $unsignedToken, $secret));
    }

    public function issetUserJwt($userId)
    {
        return JwtTable::getList([
           'filter' => ['user_id' => $userId],
           'select' => ['*']
        ])->fetch();
    }

    public function createToken($userId, $password = '', $signature = '')
    {
        $userId = (int)$userId;
        $arUserInJwt = $this->issetUserJwt($userId);
        if ($arUserInJwt['id'] && $signature === '') {
            $signature = $arUserInJwt['secret_key'];
        } elseif ($password !== '') {
            $secret = md5(self::SECRET_KEY . $password);
            $signature = $this->getSignature($secret, $userId);
            JwtTable::add([
               'secret_key' => $signature,
               'user_id'    => $userId
            ]);
        }


        if ($signature) {
            return $this->getHeader() . '.' . $this->getPayload($userId) . '.' . $signature;
        } else {
            return false;
        }
    }

}