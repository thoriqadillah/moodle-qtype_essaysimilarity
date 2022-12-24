<?php

use mod_bigbluebuttonbn\instance;

class matrix {

  private $EPS = 2.2204460492503E-16;
  private $TOL;
  private $matrix = [];

  /**
   * @param array $matrix a multi-dimensional array
   */
  public function __construct($matrix) {
    $this->matrix = $matrix;
    $this->TOL = 1e-64 / $this->EPS;
  }

  public function truncate(&$matrix, $rows, $columns) {
    for ($i = 0; $i < count($matrix); $i++) {
      if ($i > $rows) {
        array_splice($matrix, $rows);
        break;
      } else array_splice($matrix, $columns);
    }
  }

  /**
	 * Matrix multiplication
	 * 
	 * @param array $matrix_a
	 * @param array $matrix_a
	 * @return array 
	 */
	public function multiply($matrix_a, $matrix_b) {
		$product = [];

		$rows_a = count($matrix_a);
		$cols_a = count($matrix_a[0]);

		$rows_b = count($matrix_b);
		$cols_b = count($matrix_b[0]);
		
		// multiplication cannot be done
		if ($cols_a !== $rows_b) return $product;

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

  private function pythag($a, $b) {
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

  /**
   * Singular value decomposition
   * @param array $matrix
   * @return array
   */
  public function svd() {
    // Convert array key from string to numeric
    foreach ($this->matrix as &$matrix) {
      $matrix = array_values($matrix);
    }

    $m = count($this->matrix);
    $n = count($this->matrix[0]);

    // Copy matrix to $U
    $U = $this->construct($this->matrix, $m, $n);
    
    // Initialize $S and $V
    $S = array_fill(0, $n, 0);
    $V = array_fill(0, $n, array_fill(0, $n, 0));

    $e = array_fill(0, $n, 0);
    $g = 0;
    $x = 0;

    for ($i = 0; $i < $n; $i++) {
      $e[$i] = $g;
      $s = 0;
      $l = $i + 1;

      for ($j = $i; $j < $m; $j++) {
        $s += pow($U[$j][$i], 2);
      }

      if ($s < $this->TOL) $g = 0;
      else {
        $f = $U[$i][$i];
        $g = $f < 0 ? sqrt($s) : -sqrt($s);
        $h = $f*$g - $s;
        $U[$i][$i] = $f - $g;

        for ($j = $l; $j < $n; $j++) {
          $s = 0;
          for ($k = $i; $k < $m; $k++) {
            $s += $U[$k][$i] * $U[$k][$j];
          }

          $f = $s / $h;
          for ($k = $i; $k < $m; $k++) {
            $U[$k][$j] = $U[$k][$j] + $f * $U[$k][$i];
          }
        }
      }

      $S[$i] = $g;
      $s = 0;
      for ($j = $l; $j < $n; $j++) $s += pow($U[$i][$j], 2);

      if ($s < $this->TOL) $g = 0;
      else {
        $f = $U[$i][$i+1];
        $g = $f < 0 ? sqrt($s) : -sqrt($s);
        $h = $f*$g - $s;
        $U[$i][$i+1] = $f - $g;

        for ($j = $l; $j < $n; $j++) $e[$j] = $U[$i][$j] / $h;
        for ($j = $l; $j < $m; $j++) {
          $s = 0;
          for ($k = $l; $k < $n; $k++) $s += $U[$j][$k] * $U[$i][$k];
          for ($k = $l; $k < $n; $k++) $U[$j][$k] = $U[$j][$k] + $s * $e[$k];
        }
      }

      $y = abs($S[$i]) + abs($e[$i]);
      if ($y > $x) $x = $y;
    }

    // Accumulation of right-hand transformations
    for ($i = $n - 1; $i >= 0; $i--) {
      if ($g != 0) {
        $h = $g * $U[$i][$i+1];
        for ($j = $l; $j < $n; $j++) $V[$j][$i] = $U[$i][$j] / $h;
        for ($j = $l; $j < $n; $j++) {
          $s = 0;
          for ($k = $l; $k < $n; $k++) $s += $U[$i][$k] * $V[$k][$j];
          for ($k = $l; $k < $n; $k++) $V[$k][$j] += $s * $V[$k][$i];
        }
      }

      for ($j < $l; $j < $n; $j++) {
        $V[$i][$j] = 0;
        $V[$j][$i] = 0;
      }

      $V[$i][$i] = 1;
      $g = $e[$i];
      $l = $i;
    }

    //bug part
    // Accumulation of left hand transformations
    for ($i = $n - 1; $i >= 0; $i--) {
      $l = $i + 1;
      $g = $S[$i];
      for ($j = $l; $j < $n; $j++) $U[$i][$j] = 0;
      if ($g != 0) {
        $h = $U[$i][$i] * $g;
        for ($j = $l; $j < $n; $j++) {
          $s = 0;
          for ($k = $i; $k < $m; $k++) $s += $U[$k][$i] * $U[$k][$j];
          $f = $s / $h;
          for ($k = $i; $k < $m; $k++) $U[$k][$j] += $f * $U[$k][$i];
        }

        for ($j = $i; $j < $m; $j++) $U[$j][$i] = $U[$j][$i] / $g;
      } else {
        for ($j = $i; $j < $m; $j++) $U[$j][$i] = 0;
      }

      $U[$i][$i] += 1.0;
    }

    //possible bug part
    // Diagonalization of the bidiagonal form
    $this->EPS = $this->EPS * $x;
    $total = 50;
    for ($k = $n - 1; $k >= 0; $k--) {
      for ($iteration = 0; $iteration < $total; $iteration++) {
        // Test f splitting
        for ($l = $k; $l >= 0; $l--) {
          $test_convergence = false;
          if (abs($e[$l]) <= $this->EPS) {
            $test_convergence = true;
            break;
          }

          if (abs($S[$l-1]) <= $this->EPS) break;
        }

        if (!$test_convergence) {
          // Cancellation of e[l] if l > 0
          $c = 0;
          $s = 1;
          $l1 = $l-1;
          for ($i = $l; $i < $k + 1; $i++) {
            $f = $s * $e[$i];
            $e[$i] = $c * $e[$i];
            if (abs($f) <= $this->EPS) break;

            $g = $S[$i];
            $h = $this->pythag($f, $g);
            $S[$i] = $h;
            $c = $g / $h;
            $s = -$f / $h;
            for ($j = 0; $j < $m; $j++) {
              $y = $U[$j][$l1];
              $z = $U[$j][$i];
              $U[$j][$l1] = $y*$c + $z*$s;
              $U[$j][$i] = -$y*$s + $z*$c;
            }
          }
        }
        
        // Test f convergence
        $z = $S[$k];
        if ($l == $k) {
          if ($z < 0) { // Convergence
            $S[$k] = -$z; #$S[$k] is made non-negative
            for ($j = 0; $j < $n; $j++) {
              $V[$j][$k] = -$V[$j][$k];
            }
          } 
          break;
        }

        $x = $S[$l];
        $y = $S[$k-1];
        $g = $e[$k-1];
        $h = $e[$k];
        $f = (($y-$z) * ($y+$z) + ($g-$h) * ($g+$h)) / (2*$h*$y);
        $g = $this->pythag($f, 1);

        if ($f < 0) $f = (($x-$z) * ($x+$z) + $h * ($y / ($f-$g) - $h)) / $x;
        else $f = (($x-$z) * ($x+$z) + $h * ($y / ($f+$g) - $h)) / $x;
            
        # next QR transformation
        $c = 1;
        $s = 1;
        for ($i = $l + 1; $i < $k + 1; $i++) {
          $g = $e[$i];
          $y = $S[$i];
          $h = $s * $g;
          $g = $c * $g;
          $z = $this->pythag($f, $h);
          $e[$i-1] = $z;
          $c = $f / $z;
          $s = $h / $z;
          $f = $x*$c + $g*$s;
          $g = -$x*$s + $g*$c;
          $h = $y*$s;
          $y = $y*$c;
          for ($j = 0; $j < $n; $j++) {
            $x = $V[$j][$i-1];
            $z = $V[$j][$i];
            $V[$j][$i-1] = $x*$c + $z*$s;
            $V[$j][$i] = -$x*$s + $z*$c;
          }

          $z = $this->pythag($f, $h);
          $S[$i-1] = $z;
          $c = $f / $z;
          $s = $h / $z;
          $f = $c*$g + $s*$y;
          $x = -$s*$g + $c*$y;

          for ($j = 0; $j < $m; $j++){
            $y = $U[$j][$i-1];
            $z = $U[$j][$i];
            $U[$j][$i-1] = $y*$c + $z*$s;
            $U[$j][$i] = -$y*$s + $z*$c;
          }
        }

        $e[$l] = 0;
        $e[$k] = $f;
        $S[$k] = $x;
      }
    }     
    
    // prepare Sv matrix as n*n daigonal matrix of singular values
    $Sv = [];
    for($i = 0; $i < $n; $i++){
      for ($j = 0; $j < $n; $j++){
        $Sv[$i][$j] = 0;
        $Sv[$i][$i] = $S[$i];
      }
    }
    
    return [$U, $S, $Sv, $V];
  }
}