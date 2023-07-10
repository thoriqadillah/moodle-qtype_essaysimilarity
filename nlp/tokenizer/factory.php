<?php

require_once('tokenizer.php');

class tokenizer_factory {

    public static function create(string $lang): tokenizer {
        require_once("lang/".$lang.".php");

        $tokenizer = $lang."_tokenizer";
        return new $tokenizer();
    }


}