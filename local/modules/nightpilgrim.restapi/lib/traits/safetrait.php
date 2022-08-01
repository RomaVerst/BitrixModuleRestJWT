<?php

namespace NightPilgrim\RestApi\Traits;

trait SafeTrait
{
    /**
     * Удаление инъекций в параметрах
     * @param $value Параметр для проверки
     * @param int $isPassword Флаг пароль или нет
     * @return string
     */
    public function safeParams($value, $isPassword = 0)
    {
        if($isPassword) {
            return htmlspecialchars($value);
        } else {
            return htmlspecialchars(trim($value));
        }
    }
}
