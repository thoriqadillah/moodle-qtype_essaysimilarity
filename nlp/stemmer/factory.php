<?php

require_once('stemmer.php');

class stemmer_factory {

    public static function create(string $lang): stemmer {
        require_once($lang."/".$lang.".php");

        $stemmer = $lang."_stemmer";
        return new $stemmer();
    }
}