<?php

require_once('transformer.php');
require_once('matrix.php');
require_once('svd.php');

class lsa implements transformer {

  /**
   * @var matrix 
   */
  private $matrix;

  /**
   * @param matrix $matrix Matrix object
   */
  public function __construct($matrix) {
    $this->matrix = $matrix;
  }

  /**
   * Perform latent semantic analysis to get the most important topic of the word with dimensional reduction
   */
  public function transform() {
    return (new svd($this->matrix))->truncate()->transform();
    // $transformed = (new svd($this->matrix))->truncate()->transform();
    // return $this->matrix->replace_original($transformed);
  }
}