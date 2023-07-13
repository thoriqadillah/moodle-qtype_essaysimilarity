<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/cleaner/cleaner.php');

class stopword implements cleaner {
  
  protected array $stopwords = [];

  public function __construct(string $lang) {
    $this->stopwords = require("lang/$lang.php");
  }

  public function clean(array $token): array {
    return array_udiff($token, $this->stopwords, 'strcasecmp');
  }
}
