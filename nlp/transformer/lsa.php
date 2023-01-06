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
   * @param array $documents 
   */
  public function __construct($documents) {
    $this->matrix = new matrix($documents);
  }

  /**
   * Perform latent semantic analysis to get the most important topic of the word with dimensional reduction
   */
  public function transform() {
    $svd = (new svd($this->matrix))->transform();
    $transformed = [];

    foreach ($this->matrix->original() as $i => $_) {
      $transformed[$i] = array_combine(
        array_keys($this->matrix->original()[0]), array_values($svd[$i])
      );
    }

    return $transformed;
  }
}