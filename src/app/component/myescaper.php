<?php

namespace component;

use Phalcon\Escaper;

class myescaper
{
    /**
     * function sanitize
     * escapes HTML
     *
     * @param [type] $html
     * @return void
     */
    public function sanitize($html)
    {
        $escaper = new Escaper();
        return $escaper->escapeHtml($html);
    }
}
