<?php

namespace NightPilgrim\RestApi\Entity\Jwt;

use Bitrix\Main\Entity;

class JwtTable extends Entity\DataManager
{
    const SECRET_KEY = 'app_key';

    public static function getTableName()
    {
        return 'b_user_jwt';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField(
                'id', array(
                'primary' => true,
                'autocomplete' => true
            )
            ),
            new Entity\StringField(
                'secret_key', array(
                'required' => true,
            )
            ),
            new Entity\IntegerField(
                'user_id', array(
                'required' => true,
            )
            )
        );
    }
}