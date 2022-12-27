<?php

require_once('matrix.php');
require_once('svd.php');

class lsa {

  /**
   * Perform latent semantic analysis
   * @param matrix $matrix
   * @param int $features total feature that want to be extracted
   * 
   */
  public function transform($matrix, $features = null) {
    $m = count($matrix->get());
    $n = count($matrix->get()[0]);

    $svd = new svd($matrix);
    $min = min($features ?? $svd::$K, $m, $n);

    // not sure what to use, U or V
    // U based on https://stackoverflow.com/a/1039035/19323874
    // V based on https://www.youtube.com/watch?v=K38wVcdNuFc&t=199s
    // 
    // In theory, the U is for word to concept, and V is document to concept
    // So, I think it is more appropriate to use U like code below
    $matrix->truncate($svd::$U, $m, $min);
    $Ut = $matrix->transpose($svd::$U);

    return $matrix->multiply($matrix->get(), $Ut);
  }
}