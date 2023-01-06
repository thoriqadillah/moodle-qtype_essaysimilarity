<?php

interface stemmer {

  /**
   * Stem word to its root
   * @param string $word
   * @return string root of the word
   */
  public function stem($word);
}