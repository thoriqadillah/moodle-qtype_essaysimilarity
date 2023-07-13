<?php

global $CFG;
require_once($CFG->dirroot.'/question/type/essaysimilarity/nlp/vectorizer/vectorizer.php');

class en_whitespace_vectorizer implements vectorizer {

  /**
   * Whitespace vectorizer
   * Credit to @angeloskath, copied from https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Tokenizers/WhitespaceTokenizer.php
   */
  public function vectorize(string $str): array {
    $str = $this->normalize($str);
    $token = preg_split('/[\pZ\pC]+/u', $str, -1, PREG_SPLIT_NO_EMPTY);

    return $token;
  }

  /**
   * Normalize the string from special characters and symbols
   */
  protected function normalize(string $str): string {
    $str = preg_replace('/[^a-z -]/im', ' ', $str);
    $str = preg_replace('/( +)/im', ' ', $str);
    $str = str_replace('- ', '', $str);

    return trim($str);
  }
}