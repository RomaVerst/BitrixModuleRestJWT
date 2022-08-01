<?php

namespace NightPilgrim\RestApi\Traits;

trait ValidatorTrait
{
    /**
     * Валидация email.
     *
     * @param string $email Email.
     * @return bool
     */
    public static function validateEmail($email)
    {
        return check_email($email);
    }


    /**
     * Валидация номера телефона
     *
     * @param string $phone Номер телефона.
     * @return bool
     */
    public static function validatePhone($phone)
    {
        return (bool) preg_match('/^[\+]?[7,8][\d]{10}$/', $phone);
    }
}