<?php


namespace Services;


class UserService
{
    private string $firstParam;
    private string $secondParam;

    public function __construct(string $firstParam, string $secondParam)
    {
        $this->firstParam = $firstParam;
        $this->secondParam = $secondParam;
    }


}