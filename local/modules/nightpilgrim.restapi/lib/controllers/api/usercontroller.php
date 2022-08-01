<?php

namespace NightPilgrim\RestApi\Controllers\Api;

use NightPilgrim\RestApi\Traits\ValidatorTrait;
use NightPilgrim\RestApi\Traits\SafeTrait;
use NightPilgrim\RestApi\Traits\PaginatorTrait;
use \Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use NightPilgrim\RestApi\Entity\Jwt\JwtRepository;
use Bitrix\Main\Security\Password;
use CPHPCache;

class UserController extends \Bitrix\Main\Engine\Controller
{
    use ValidatorTrait;
    use SafeTrait;
    use PaginatorTrait;

    const PAGE_SIZE = 10;

    public function registerAction()
    {
        try {
            $login = $this->safeParams($this->request->getPost('login'));
            $name = $this->safeParams($this->request->getPost('name'));
            $lastName = $this->safeParams($this->request->getPost('lastName'));
            $email = $this->safeParams($this->request->getPost('email'));
            $password = $this->safeParams($this->request->getPost('password'), 1);
            $phone = $this->safeParams($this->request->getPost('phone'));
            $arParams = [
                'login'     => $login,
                'name'      => $name,
                'lastName'  => $lastName,
                'email'     => $email,
                'password'  => $password,
                'phone'     => $phone,
            ];
            $arCheckedParams = $this->checkParams($arParams);
            if ($arCheckedParams['status'] === 'error') {
                throw new \Exception($arCheckedParams['message']);
            }
            if (!$this->validateEmail($email)) {
                throw new \Exception(Loc::getMessage('EMAIL_INVALID'));
            }
            if (!$this->validatePhone($phone)) {
                throw new \Exception(Loc::getMessage('PHONE_INVALID'));
            }
            $phone = preg_replace('/^[8,7]/', '+7', $phone);
            $userId = $this->addNewUser($login, $password, $name, $lastName, $email, $phone);
            $jwt = new JwtRepository();
            $token = $jwt->createToken($userId,$password);
            return ['token' => $token];
        } catch (\Exception $e) {
            header('400 Bad Request', true, 400);
            $this->addError(new Error($e->getMessage(), 400));
            return false;
        }
    }

    /**
     * Добавление нового пользователя
     * @param string $login Логин
     * @param string $password Пароль
     * @param string $name Имя пользователя
     * @param string $lastName Фамилия пользователя
     * @param string $email Email
     * @param string $phone Телефон пользователя
     * @return int
     * @throws \Exception
     */
    public function addNewUser(string $login, string $password, string $name, string $lastName, string $email, string $phone)
    {
        $user = new \CUser;
        $arFields = Array(
            'NAME'              => $name,
            'LAST_NAME'         => $lastName,
            'EMAIL'             => $email,
            'LOGIN'             => $login,
            'LID'               => 'ru',
            'ACTIVE'            => 'Y',
            'GROUP_ID'          => [3,4],
            'PASSWORD'          => $password,
            'CONFIRM_PASSWORD'  => $password,
            'PHONE_NUMBER'      => $phone
        );
        $id = $user->Add($arFields);
        if (intval($id) > 0) {
            return $id;
        } else {
            throw new \Exception(Loc::getMessage('USER_NOT_ADDED'));
        }
    }

    public function authAction()
    {
        try {
            $login = $this->safeParams($this->request->getPost('login'));
            $password = $this->safeParams($this->request->getPost('password'), 1);
            $arParams = [
                'login'     => $login,
                'password'  => $password
            ];
            $arCheckedParams = $this->checkParams($arParams);
            if ($arCheckedParams['status'] === 'error') {
                throw new \Exception($arCheckedParams['message']);
            }
            $userData = \CUser::GetByLogin($login)->Fetch();
            if (!$userData) {
                throw new \Exception(Loc::getMessage('INVALID_LOGIN'));
            }
            if (!$this->checkUserPassword($userData['PASSWORD'], $password)) {
                throw new \Exception(Loc::getMessage('INVALID_PASSWORD'));
            }

            $jwt = new JwtRepository();
            $token = $jwt->createToken($userData['ID']);
            if ($token) {
                return ['token' => $token];
            } else {
                throw new \Exception(Loc::getMessage('INVALID_PASSWORD'));
            }
        } catch(\Exception $e) {
            header('400 Bad Request', true, 400);
            $this->addError(new Error($e->getMessage(), 400));
            return false;
        }
    }

    /**
     * Проверяем, является ли $password текущим паролем пользователя.
     *
     * @param string $hash
     * @param string $password
     * @return bool
     */
    function checkUserPassword(string $hash, string $password)
    {
        return Password::equals($hash, $password);
    }

    private function checkToken()
    {
        if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return false;
        }
        $jwt = new JwtRepository();

        $signature = explode('.', $matches[1])[2];
        $userId = $jwt->getUserIdByHash($signature);
        if ($userId) {
            $token = $jwt->createToken($userId, '', $signature);
            if ($token == $matches[1]) {
                return $userId;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function showUsersAction()
    {
        try {
            if(!$userId = $this->checkToken()) {
                throw new \Exception(Loc::getMessage('INVALID_AUTH'));
            }
            $cache = new CPHPCache();
            $cache_id = md5(serialize($userId));
            if ($cache->InitCache(36000, $cache_id, '/UserList')) {
                $arResult = $cache->GetVars();
            } elseif ($cache->StartDataCache()) {
                $arResult = [];
                $res = \CUser::GetList(
                    $by = 'id',
                    ['sort' => 'asc'],
                    ['ACTIVE' => 'Y']
                );
                while ($arRes = $res->Fetch()) {
                    $arResult[$arRes['ID']] = $arRes;
                    $arResult[$arRes['ID']]['GROUPS_ID'] = \CUser::GetUserGroup($arRes['ID']);
                }
                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache('/UserList');
                $CACHE_MANAGER->RegisterTag('user_list');
                $CACHE_MANAGER->EndTagCache();

                $cache->EndDataCache($arResult);
            }
            $page = $this->safeParams($this->request->getPost('page'));
            $arResult = $this->getPagin($arResult, self::PAGE_SIZE, ($page) ?? 1);
            if(!$arResult) {
                throw new \Exception(Loc::getMessage('NO_ITEMS'));
            }
            return ['user_list' => $arResult];
        } catch (\Exception $e) {
            switch ($e->getMessage()) {
                case Loc::getMessage('NO_ITEMS'):
                    $code = 404;
                    header('404 Not Found', true, 404);
                    break;
                case Loc::getMessage('INVALID_AUTH'):
                    $code = 400;
                    header('400 Bad Request', true, 400);
                    break;
            }

            $this->addError(new Error($e->getMessage(), $code));
            return false;
        }
    }

    public function showOneUserAction($id)
    {
        try {
            $id = (int)$this->safeParams($id);
            if (!$id) {
                throw new \Exception(Loc::getMessage('INVALID_ID'));
            }
            if(!$this->checkToken()) {
                throw new \Exception(Loc::getMessage('INVALID_AUTH'));
            }
            $arFields = \CUser::GetByID($id)->Fetch();
            if (!$arFields) {
                throw new \Exception(Loc::getMessage('INVALID_USER'));
            }
            $arFields['GROUPS_ID'] = \CUser::GetUserGroup($id);
            return ['user_fields' => $arFields];
        } catch (\Exception $e) {
            header('400 Bad Request', true, 400);
            $this->addError(new Error($e->getMessage(), 400));
            return false;
        }
    }


    /**
     * Проверка переданных параметров
     *
     * @param array $arValues массив параметров
     * @return array
     */
    private function checkParams(array $arValues)
    {
        foreach ($arValues as $key => $value) {
            if (!$value) {
                return [
                    'status' => 'error',
                    'message' => str_replace('#FIELD#', $key, Loc::getMessage('VALUE_NOT_DIFINED'))
                ];
            }
        }
        return [
            'status' => 'success',
            'message'=> ''
        ];
    }

    /**
     * @return array
     */
    public function configureActions()
    {
        return [
            'showOneUser' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'showUsers' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'auth' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'register' => [
                'prefilters' => [],
                'postfilters' => []
            ],
        ];
    }
}