<?php

namespace Ekapusta\OAuth2Esia\Security\JWTSigner;

final class TmpFile
{
    private $handle;
    private $path;

    public function __construct($content)
    {
        $this->handle = tmpfile();
        fwrite($this->handle, $content);
        fseek($this->handle, 0);

        $this->path = stream_get_meta_data($this->handle)['uri'];
    }

    public function __toString()
    {
        return $this->path;
    }
}
