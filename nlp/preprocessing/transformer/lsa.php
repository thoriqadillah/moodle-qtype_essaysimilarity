<?php

require_once('matrix.php');
require_once('svd.php');

class lsa {

  /**
   * Perform latent semantic analysis to get the topic of the word
   * @param matrix $matrix
   * @param int $features total feature that want to be extracted
   * 
   */
  public function transform($matrix) {
    // Perform dimensional reduction to get the most important topic
    $svd = new svd($matrix);
    $A = $svd->truncate();

    // Get word to topic
    return $matrix->multiply($A, $svd::$V);
  }
}