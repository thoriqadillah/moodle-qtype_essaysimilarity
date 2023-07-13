<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/vectorizer/vectorizer.php');

class whitespace_vectorizer {

    public static function create(string $lang): vectorizer {
        require_once("lang/".$lang.".php");

        $vectorizer = $lang."_whitespace_vectorizer";
        return new $vectorizer();
    }
}