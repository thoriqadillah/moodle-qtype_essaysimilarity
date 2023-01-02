<?php

class matrix {

  private $matrix = [];
  private $original = [];

  /**
   * @param array $matrix a multi-dimensional array
   */
  public function __construct($matrix) {
    $this->original = $matrix;

    // Convert string key to numerical key for operational
    foreach ($matrix as $mtx) {
      $this->matrix[] = array_values($mtx);
    }
  }

  /**
   * Convert numerical key to string key for peeking
   */
  public function replace_original($with) {
    foreach ($this->original as $i => $_) {
      $this->original[$i] = array_combine(array_keys($this->original[0]), array_values($with[$i]));
    }

    return $this->original;
  }

  public function truncate(&$matrix, $rows, $columns) {
    for ($i = 0; $i < count($matrix); $i++) {
      if ($i > $rows) {
        array_splice($matrix, $rows);
        break;
      } else array_splice($matrix, $columns);
    }
  }

  public function get() {
    return $this->matrix;
  }
  /**
	 * Matrix multiplication
	 * 
	 * @param array $matrix_a A multi-dimensional matrix
	 * @param array $matrix_a A matrix that at least one dimensional
	 * @return array 
	 */
	public function multiply($matrix_a, $matrix_b) {
		$product = [];

		$rows_a = count($matrix_a);
		$cols_a = count($matrix_a[0]);

		$rows_b = count($matrix_b);
		$cols_b = count($matrix_b[0]);
		
		// multiplication cannot be done
		if ($cols_a !== $rows_b) {
      throw new InvalidArgumentException("Column A ($cols_a) and Row B ($rows_b) is not equal");
    }

		for($i = 0; $i < $rows_a; $i++){
			for($j = 0; $j < $cols_b; $j++){
				for($p = 0; $p < $cols_a; $p++){
					$product[$i][$j] += $matrix_a[$i][$p] * $matrix_b[$p][$j];
				}
			}
		}
		
		return $product;
	}

	/**
	 * Matrix transposition
	 * 
	 * @param array $matrix
	 * @return array
	 */
	public function transpose($matrix) {
    $result = [];

		$m = count($matrix);
		$n = count($matrix[0]);

    for($i = 0; $i < $n; $i++){
      for($j = 0; $j < $m; $j++){
        $result[$i][$j] = $matrix[$j][$i];
      }
    }

    return $result;
	}

  /**
   * Matrix rounding
   * 
   * @param array $matrix
   * @return array 
   */
  public function round($matrix) {
    $result = [];

    $m = count($matrix);
    $n = count($matrix[0]);
    
    for($i = 0; $i < $m; $i++){
      for($j = 0; $j < $n; $j++){
        $result[$i][$j] = round($matrix[$i][$j], 2);
      }
    }

    return $result;
  }

  public function construct($matrix, $rows, $columns) {
    $neo_matrix = [];
    for($i = 0; $i < $rows; $i++){
      for($j = 0; $j < $columns; $j++){
        $neo_matrix[$i][$j] = $matrix[$i][$j];
      }
    }

    return $neo_matrix;
  }

  public function pythag($a, $b) {
    $a = abs($a);
    $b = abs($b);

    if ($a > $b) {
      return $a * sqrt(1.0 + pow($b/$a, 2));
    }

    if ($b > 0.0) {
      return $b * sqrt(1.0 + pow($a/$b, 2));
    }

    return 0;
  }
}