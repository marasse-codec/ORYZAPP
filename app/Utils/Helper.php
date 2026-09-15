<?php

namespace App\Utils;

class Helper
{
    /**
     * Le layout applicatif. Un seul suffit désormais : la barre latérale se
     * compose à partir des permissions du rôle connecté.
     */
    public static function layout(): string
    {
        return 'layouts.aderiz';
    }
}
