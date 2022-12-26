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
  public static function transform($matrix, $features = null) {
    $m = $matrix->get();
    $n = $matrix->get()[0];

    $svd = new svd($matrix);
    $min = min($features ?? $svd::$K, count($m), count($n));
    
    //FIXME
    $matrix->truncate($svd::$Vt, count($n), $min);
    $V = $matrix->transpose($svd::$Vt);

    return $matrix->multiply($m, $V);
  }
}