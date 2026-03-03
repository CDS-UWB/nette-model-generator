<?php

namespace Cds\NetteModelGenerator;

class Logger
{
    public function log(string ...$args): void
    {
        echo implode($args), PHP_EOL;
    }
}
