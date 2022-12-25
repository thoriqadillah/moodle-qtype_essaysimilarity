<?php

require_once('matrix.php');

class svd {

  public static $U  = [];
  public static $S  = [];
  public static $Sv = [];
  public static $V  = [];
  public static $Vt = [];
  public static $K;

  /**
   * @var matrix
   */
  private $matrix = [];

  /**
   * @param matrix $matrix Matrix class to perform calculation
   */
  public function __construct($matrix) {
    $this->matrix = $matrix;
    $this->decompose();
  }

  /**
   * Perform truncated SVD
   * 
   * @param matrix $matrix Matrix class to perform calculation
   * @param int|null $dimension desired dimension
   * @return array transformed matrix
   */
  public static function transform($matrix, $dimension) {
    return (new svd($matrix))->truncate($dimension);
  }

  /**
   * Perform SVD
   */
  private function decompose() {
    // Convert array key from string to numeric
    foreach ($this->matrix->get() as &$mtx) {
      $mtx = array_values($mtx);
    }

    $m = count($this->matrix->get());
    $n = count($this->matrix->get()[0]);

    // Copy matrix to $U
    $U = $this->matrix->construct($this->matrix->get(), $m, $n);
    
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

      if ($s < $this->matrix::$TOL) $g = 0;
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

      if ($s < $this->matrix::$TOL) $g = 0;
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
    $this->matrix::$EPS = $this->matrix::$EPS * $x;
    $total = 50;
    for ($k = $n - 1; $k >= 0; $k--) {
      for ($iteration = 0; $iteration < $total; $iteration++) {
        // Test f splitting
        for ($l = $k; $l >= 0; $l--) {
          $test_convergence = false;
          if (abs($e[$l]) <= $this->matrix::$EPS) {
            $test_convergence = true;
            break;
          }

          if (abs($S[$l-1]) <= $this->matrix::$EPS) break;
        }

        if (!$test_convergence) {
          // Cancellation of e[l] if l > 0
          $c = 0;
          $s = 1;
          $l1 = $l-1;
          for ($i = $l; $i < $k + 1; $i++) {
            $f = $s * $e[$i];
            $e[$i] = $c * $e[$i];
            if (abs($f) <= $this->matrix::$EPS) break;

            $g = $S[$i];
            $h = $this->matrix->pythag($f, $g);
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
        $g = $this->matrix->pythag($f, 1);

        if ($f < 0) $f = (($x-$z) * ($x+$z) + $h * ($y / ($f-$g) - $h)) / $x;
        else $f = (($x-$z) * ($x+$z) + $h * ($y / ($f+$g) - $h)) / $x;
            
        // S transformation
        $c = 1;
        $s = 1;
        for ($i = $l + 1; $i < $k + 1; $i++) {
          $g = $e[$i];
          $y = $S[$i];
          $h = $s * $g;
          $g = $c * $g;
          $z = $this->matrix->pythag($f, $h);
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

          $z = $this->matrix->pythag($f, $h);
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

    rsort($S);
    
    // calculate the rank
    $rank = 0;
    for($i = 0; $i < count($S); $i++){
      if (round($S[$i], 4) > 0) $rank += 1;
    }

    // Low-Rank Approximation
    $q = 0.9;
    $K = 0;
    $frobA = 0;
    for($i = 0; $i < $rank; $i++) $frobA += $S[$i];
    $frobAk = 0;
    do {
      for($i = 0; $i <= $K; $i++) $frobAk += $S[$i];
      $clt = $frobAk / $frobA;
      $K++;
    } while($clt < $q);
    
    // prepare Sv matrix as n*n daigonal matrix of singular values
    $Sv = [];
    for($i = 0; $i < $n; $i++){
      for ($j = 0; $j < $n; $j++){
        $Sv[$i][$j] = 0;
        $Sv[$i][$i] = $S[$i];
      }
    }

    self::$U = $U;
    self::$S = $S;
    self::$Sv = $Sv;
    self::$V = $V;
    self::$Vt = $this->matrix->transpose($V);
    self::$K = $K;

    return $this;
  }

  /**
   * Truncate the decomposed matrix to certain dimension
   * 
   * @param int|null $dimension desired dimension
   * @return array transformed matrix
   */
  public function truncate($dimension = null) {
    if ($dimension == null) {
      return $this->get();
    }

    // reducing S to desired dimension
    for ($i = $dimension; $i < count(self::$S); $i++) { 
      self::$S[$i] = 0;
    }
    
    for($i = 0; $i < count(self::$S); $i++){
      for ($j = 0; $j < count(self::$S); $j++){
        self::$Sv[$i][$j] = 0;
        self::$Sv[$i][$i] = self::$S[$i];
      }
    }
 
    return $this->get();
  }

  /**
   * Get the decomposed matrix
   * @return array transformed matrix
   */
  public function get() {
    return $this->matrix->multiply(self::$U, $this->matrix->multiply(self::$Sv, self::$Vt));
  }

}