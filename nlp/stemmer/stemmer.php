<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/cleaner/cleaner.php');

abstract class stemmer implements cleaner {

  /**
   * Stem word to its root
   */
  public abstract function stem(string $word): string;

  public function clean(array $token): array {
    foreach ($token as &$tok) {
      $tok = $this->stem($tok);
    }

    return $token;
  }
}