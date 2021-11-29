<?php
namespace Shamaseen\Repository\Traits;


trait PathsResolver
{
    function forwardSlashesToBackSlashes($path)
    {
        return str_replace("/", "\\", $path);
    }
}
