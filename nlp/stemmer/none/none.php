<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/stemmer/stemmer.php');

class none_stemmer extends stemmer {

    public function stem(string $word): string {
        return $word;
    }

}