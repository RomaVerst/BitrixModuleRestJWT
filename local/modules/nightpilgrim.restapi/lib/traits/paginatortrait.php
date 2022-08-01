<?php

namespace NightPilgrim\RestApi\Traits;

trait PaginatorTrait
{
    public function getPagin($arValues, $pageSize, $page)
    {
        $start = ($page * $pageSize) - $pageSize;
        return array_slice($arValues, $start, $pageSize);
    }
}