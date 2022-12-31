<?php

require_once('matrix.php');

class svd {

  /**
   * Left singular vector
   * @var array
   */
  public static $U  = [];

  /**
   * One-dimensional array of singular vector
   * @var array
   */
  public static $Sv  = [];

  /**
   * Diagonal matrix of singular vector
   * @var array
   */
  public static $S = [];

  /**
   * Right singular vector
   * @var array
   */
  public static $V  = [];

  /**
   * Transposed right singular vector
   * @var array
   */
  public static $Vt = [];

  /**
   * Low-rank Approximation
   * @var int
   */
  public static $K;

  /**
   * Rank
   * @var int
   */
  public static $rank;

  /**
   * Matrix that being passed
   * @var matrix
   */
  private $matrix = [];

  /**
   * Perform SVD
   * 
   * @param matrix $matrix Matrix class to perform calculation
   */
  public function __construct($matrix) {
    $this->matrix = $matrix;
    $this->decompose();
  }


  /**
   * Perform Singular Value Decomposition
   * Taken from SVD implementation from JAMA https://github.com/fiji/Jama/blob/master/src/main/java/Jama/SingularValueDecomposition.java
   */
  public function decompose() {
    // Convert array key from string to numeric
    $m = count($this->matrix->get());
    $n = count($this->matrix->get()[0]);
    $nu = min($m, $n);

    //Copy matrix to A
    $A =  $this->matrix->construct($this->matrix->get(), $m, $n);
    
    $s = array_fill(0, min($m+1, $n), 0);
    $U = array_fill(0, $m, array_fill(0, $nu, 0));
    $V = array_fill(0, $n, array_fill(0, $n, 0));
    $e = array_fill(0, $n, 0);
    $work = array_fill(0, $m, 0);

    //TODO: delete want u and want v later
    $wantu = true;
    $wantv = true;

    // Reduce A to bidiagonal form, 
    // storing the diagonal elements in S and the super-diagonal elements in e.
    $nct = min($m-1, $n);
    $nrt = max(0, min($n-2, $m));
    for ($k = 0; $k < max($nct, $nrt); $k++) {
      if ($k < $nct) {

        // Compute the transformation for the k-th column and
        // place the k-th diagonal in s[k].
        // Compute 2-norm of k-th column without under/overflow.
        $s[$k] = 0;
        for ($i = $k; $i < $m; $i++) {
          $s[$k] = hypot($s[$k], $A[$i][$k]);
        }

        if ($s[$k] != 0.0) {
          if ($A[$k][$k] < 0.0) {
             $s[$k] = -$s[$k];
          }
          for ($i = $k; $i < $m; $i++) {
            $A[$i][$k] /= $s[$k];
          }
          $A[$k][$k] += 1.0;
        }

        $s[$k] = -$s[$k];
      }

      for ($j = $k+1; $j < $n; $j++) {
        if (($k < $nct) && ($s[$k] != 0.0))  {

          // Apply the transformation.
          $t = 0;
          for ($i = $k; $i < $m; $i++) {
            $t += $A[$i][$k] * $A[$i][$j];
          }

          $t = -$t / $A[$k][$k];
          for ($i = $k; $i < $m; $i++) {
            $A[$i][$j] += $t * $A[$i][$k];
          }
        }

        // Place the k-th row of A into e for the
        // subsequent calculation of the row transformation.
        $e[$j] = $A[$k][$j];
      }

      //TODO: delete want u later
      if ($wantu && ($k < $nct)) {

        // Place the transformation in U for subsequent back
        // multiplication.
        for ($i = $k; $i < $m; $i++) {
          $U[$i][$k] = $A[$i][$k];
        }
      }

      if ($k < $nct) {

        // Compute the k-th row transformation and place the
        // k-th super-diagonal in e[k].
        // Compute 2-norm without under/overflow.
        $e[$k] = 0;
        for ($i = $k+1; $i < $n; $i++) {
          $e[$k] = hypot($e[$k], $e[$i]);
        }

        if ($e[$k] != 0.0) {
          if ($e[$k+1] < 0.0) {
            $e[$k] = -$e[$k];
          }

          for ($i = $k+1; $i < $n; $i++) {
            $e[$i] /= $e[$k];
          }

          $e[$k+1] += 1.0;
        }

        $e[$k] = -$e[$k];
        if (($k+1 < $m) && ($e[$k] != 0.0)) {

          // Apply the transformation.
          for ($i = $k+1; $i < $m; $i++) {
            $work[$i] = 0.0;
          }

          for ($j = $k+1; $j < $n; $j++) {
            for ($i = $k+1; $i < $m; $i++) {
              $work[$i] += $e[$j] * $A[$i][$j];
            }
          }

          for ($j = $k+1; $j < $n; $j++) {
            $t = -$e[$j] / $e[$k+1];
            for ($i = $k+1; $i < $m; $i++) {
              $A[$i][$j] += $t * $work[$i];
            }
          }
        }

        //TODO: delete want v later
        if ($wantv) {

          // Place the transformation in V for subsequent
          // back multiplication.
          for ($i = $k+1; $i < $n; $i++) {
            $V[$i][$k] = $e[$i];
          }
        }
      }
    }

    // Set up the final bidiagonal matrix or order p.
    $p = min($n, $m+1);
    if ($nct < $n) {
      $s[$nct] = $A[$nct][$nct];
    }

    if ($m < $p) {
      $s[$p-1] = 0.0;
    }

    if ($nrt+1 < $p) {
      $e[$nrt] = $A[$nrt][$p-1];
    }

    $e[$p-1] = 0.0;

    //TODO: delete want u later
    if ($wantu) {
      for ($j = $nct; $j < $nu; $j++) {
        for ($i = 0; $i < $m; $i++) {
          $U[$i][$j] = 0.0;
        }

        $U[$j][$j] = 1.0;
      }

      for ($k = $nct-1; $k >= 0; $k--) {
        if ($s[$k] != 0.0) {
          for ($j = $k+1; $j < $nu; $j++) {
            $t = 0;
            for ($i = $k; $i < $m; $i++) {
              $t += $U[$i][$k] * $U[$i][$j];
            }

            $t = -$t / $U[$k][$k];
            for ($i = $k; $i < $m; $i++) {
              $U[$i][$j] += $t * $U[$i][$k];
            }
          }

          for ($i = $k; $i < $m; $i++ ) {
            $U[$i][$k] = -$U[$i][$k];
          }

          $U[$k][$k] = 1.0 + $U[$k][$k];
          for ($i = 0; $i < $k-1; $i++) {
            $U[$i][$k] = 0.0;
          }
        } else {
          for ($i = 0; $i < $m; $i++) {
            $U[$i][$k] = 0.0;
          }

          $U[$k][$k] = 1.0;
        }
      }
    }

    //TODO: delete want v later
    if ($wantv) {
      for ($k = $n-1; $k >= 0; $k--) {
        if (($k < $nrt) && ($e[$k] != 0.0)) {
          for ($j = $k+1; $j < $nu; $j++) {
            $t = 0;
            for ($i = $k+1; $i < $n; $i++) {
              $t += $V[$i][$k] * $V[$i][$j];
            }

            $t = -$t / $V[$k+1][$k];
            for ($i = $k+1; $i < $n; $i++) {
              $V[$i][$j] += $t*$V[$i][$k];
            }
          }
        }

        for ($i = 0; $i < $n; $i++) {
          $V[$i][$k] = 0.0;
        }
        
        $V[$k][$k] = 1.0;
      }
    }

    // Main iteration loop for the singular values.
    $pp = $p-1;
    $iter = 0;
    $eps = pow(2.0, -52.0);
    $tiny = pow(2.0, -966.0);
    while ($p > 0) {
      $k = $kase = 0;

      // Here is where a test for too many iterations would go.

      // This section of the program inspects for
      // negligible elements in the s and e arrays.  On
      // completion the variables kase and k are set as follows.

      // kase = 1     if s(p) and e[k-1] are negligible and k<p
      // kase = 2     if s(k) is negligible and k<p
      // kase = 3     if e[k-1] is negligible, k<p, and
      //              s(k), ..., s(p) are not negligible (qr step).
      // kase = 4     if e(p-1) is negligible (convergence).
      for ($k = $p-2; $k >= -1; $k--) {
        if ($k == -1) {
          break;
        }

        if (abs($e[$k]) <= $tiny + $eps*(abs($s[$k]) + abs($s[$k+1]))) {
          $e[$k] = 0.0;
          break;
        }
      }

      if ($k == $p-2) {
        $kase = 4;
      } else {
        $ks = 0;
        for ($ks = $p-1; $ks >= $k; $ks--) {
          if ($ks == $k) break;

          $t = ($ks != $p ? abs($e[$ks]) : 0.) + ($ks != $k+1 ? abs($e[$ks-1]) : 0.);
          if (abs($s[$ks]) <= $tiny + $eps*$t)  {
            $s[$ks] = 0.0;
            break;
          }
        }

        if ($ks == $k) {
          $kase = 3;
        } else if ($ks == $p-1) {
          $kase = 1;
        } else {
          $kase = 2;
          $k = $ks;
        }
      }
      $k++;

      // Perform the task indicated by kase.
      switch ($kase) {

        // Deflate negligible s(p).
        case 1: {
          $f = $e[$p-2];
          $e[$p-2] = 0.0;

          for ($j = $p-2; $j >= $k; $j--) {
            $t = hypot($s[$j], $f);
            $cs = $s[$j] / $t;
            $sn = $f / $t;
            $s[$j] = $t;
            if ($j != $k) {
              $f = -$sn * $e[$j-1];
              $e[$j-1] = $cs * $e[$j-1];
            }

            //TODO: delete want v later
            if ($wantv) {
              for ($i = 0; $i < $n; $i++) {
                $t = $cs * $V[$i][$j] + $sn*$V[$i][$p-1];
                $V[$i][$p-1] = -$sn*$V[$i][$j] + $cs*$V[$i][$p-1];
                $V[$i][$j] = $t;
              }
            }
          }
        }
        break;

        // Split at negligible s(k).
        case 2: {
          $f = $e[$k-1];
          $e[$k-1] = 0.0;

          for ($j = $k; $j < $p; $j++) {
            $t = hypot($s[$j], $f);
            $cs = $s[$j] / $t;
            $sn = $f / $t;
            $s[$j] = $t;
            $f = -$sn*$e[$j];
            $e[$j] = $cs*$e[$j];

            //TODO: delete want u later
            if ($wantu) {
              for ($i = 0; $i < $m; $i++) {
                $t = $cs*$U[$i][$j] + $sn*$U[$i][$k-1];
                $U[$i][$k-1] = -$sn*$U[$i][$j] + $cs*$U[$i][$k-1];
                $U[$i][$j] = $t;
              }
            }
          }
        }
        break;

        // Perform one qr step.
        case 3: {
          // Calculate the shift.
          $scale = max(max(max(max(
            abs($s[$p-1]), abs($s[$p-2])), abs($e[$p-2])), 
            abs($s[$k])), abs($e[$k]));
          $sp = $s[$p-1] / $scale;
          $spm1 = $s[$p-2] / $scale;
          $epm1 = $e[$p-2] / $scale;
          $sk = $s[$k] / $scale;
          $ek = $e[$k] / $scale;
          $b = (($spm1 + $sp)*($spm1 - $sp) + $epm1*$epm1) / 2.0;
          $c = ($sp*$epm1) * ($sp*$epm1);
          $shift = 0.0;

          if (($b != 0.0) | ($c != 0.0)) {
            $shift = sqrt($b*$b + $c);
            if ($b < 0.0) $shift = -$shift;
            $shift = $c / ($b + $shift);
          }

          $f = ($sk + $sp) * ($sk - $sp) + $shift;
          $g = $sk * $ek;

          // Chase zeros.
          for ($j = $k; $j < $p-1; $j++) {
            $t = hypot($f, $g);
            $cs = $f / $t;
            $sn = $g / $t;
            if ($j != $k) {
              $e[$j-1] = $t;
            }

            $f = $cs*$s[$j] + $sn*$e[$j];
            $e[$j] = $cs*$e[$j] - $sn*$s[$j];
            $g = $sn*$s[$j+1];
            $s[$j+1] = $cs*$s[$j+1];

            //TODO: delete want v later
            if ($wantv) {
              for ($i = 0; $i < $n; $i++) {
                $t = $cs*$V[$i][$j] + $sn*$V[$i][$j+1];
                $V[$i][$j+1] = -$sn*$V[$i][$j] + $cs*$V[$i][$j+1];
                $V[$i][$j] = $t;
              }
            }

            $t = hypot($f, $g);
            $cs = $f / $t;
            $sn = $g / $t;
            $s[$j] = $t;
            $f = $cs*$e[$j] + $sn*$s[$j+1];
            $s[$j+1] = -$sn*$e[$j] + $cs*$s[$j+1];
            $g = $sn*$e[$j+1];
            $e[$j+1] = $cs*$e[$j+1];

            //TODO: delete want u later
            if ($wantu && ($j < $m-1)) {
              for ($i = 0; $i < $m; $i++) {
                $t = $cs*$U[$i][$j] + $sn*$U[$i][$j+1];
                $U[$i][$j+1] = -$sn*$U[$i][$j] + $cs*$U[$i][$j+1];
                $U[$i][$j] = $t;
              }
            }
          }

          $e[$p-2] = $f;
          $iter = $iter + 1;
        }
        break;

        case 4: {
          // Make the singular values positive.
          if ($s[$k] <= 0.0) {
            $s[$k] = ($s[$k] < 0.0 ? -$s[$k] : 0.0);
            
            //TODO: delete want v later
            if ($wantv) {
              for ($i = 0; $i <= $pp; $i++) {
                $V[$i][$k] = -$V[$i][$k];
              }
            }
          }

          // Order the singular values.
          while ($k < $pp) {
            if ($s[$k] >= $s[$k+1]) break;
            $t = $s[$k];
            $s[$k] = $s[$k+1];
            $s[$k+1] = $t;

            //TODO: delete want v later
            if ($wantv && ($k < $n-1)) {
              for ($i = 0; $i < $n; $i++) {
                $t = $V[$i][$k+1]; 
                $V[$i][$k+1] = $V[$i][$k]; 
                $V[$i][$k] = $t;
              }
            }

            //TODO: delete want u later
            if ($wantu && ($k < $m-1)) {
              for ($i = 0; $i < $m; $i++) {
                $t = $U[$i][$k+1]; 
                $U[$i][$k+1] = $U[$i][$k];
                $U[$i][$k] = $t;
              }
            }

            $k++;
          }

          $iter = 0;
          $p--;
        }
        break;
      }
    }


    // Calculate the rank
    $EPS = pow(2, -52);
    $TOL = max($m, $n) * $s[0] * $EPS;
    $rank = 0;
    for ($i = 0; $i < count($s); $i++) { 
      if ($s[$i] > $TOL) {
        ++$rank;
      }
    }
    
    // Low-Rank Approximation
    $q = 0.9;
    $K = 0;
    $frobA = 0;
    $frobAk = 0;
    for($i = 0; $i < $rank; $i++) $frobA += $s[$i];
    do {
      for($i = 0; $i <= $K; $i++) $frobAk += $s[$i];
      $clt = $frobAk / $frobA;
      $K++;
    } while ($clt < $q);

    // Calculate the multi-diagonal S
    $S = array_fill(0, $m, array_fill(0, $n, 0));
    for ($i = 0; $i < $m; $i++) {
      $S[$i][$i] = $s[$i];
    }

    self::$U = $U;
    self::$Sv = $s;
    self::$S = $S;
    self::$V = $V;
    self::$Vt = $this->matrix->transpose($V);
    self::$rank = $rank;
    self::$K = $K;

    return $this;
  }

  /**
   * Truncate the matrix with low-rank approximation
   */
  public function truncate() {
    for ($i = self::$K; $i < count(self::$S); $i++) { 
      self::$S[$i][$i] = 0;
    }

    return $this->get();
  }

  /**
   * Get the decomposed matrix
   * @return array transformed matrix
   */
  public function get() {
    return $this->matrix->multiply($this->matrix->multiply(self::$U, self::$S), self::$Vt);
  }

}