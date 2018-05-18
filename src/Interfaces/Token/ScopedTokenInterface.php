<?php

namespace Ekapusta\OAuth2Esia\Interfaces\Token;

interface ScopedTokenInterface
{
    /**
     * @return string[]
     */
    public function getScopes();
}
