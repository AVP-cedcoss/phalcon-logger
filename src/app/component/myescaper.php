<?php

namespace component;

use Phalcon\Escaper;

class myescaper
{
    public function sanitize($html)
    {
        $escaper = new Escaper();
        return $escaper -> escapeHtml($html);
    }
}