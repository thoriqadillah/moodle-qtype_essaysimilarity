<?php

include('./../stemmer.php');

/**
 * Stemming bahasa indonesia menggunakan algoritma Nazief dan Adriani
 */
class id_stemmer implements stemmer {
  
  private $kamus;

  public function __construct() {
    $this->kamus = require('dictionary.php');
  }

  /**
   * Cek apakah suatu kata sudah termasuk ke dalam kata dasar
   */
  private function cek_kamus($kata) {
    return in_array($kata, $this->kamus);
  }

  /**
   * Menghapus suffix seperti -ku, -mu, -kah, dsb
   */
  private function del_inflection_suffixes($kata) {
    if (!preg_match('/([km]u|nya|[kl]ah|pun)\z/i', $kata)) { // Cek Inflection Suffixes
      return $kata;
    }

    return preg_replace('/([km]u|nya|[kl]ah|pun)\z/i', '', $kata);
  }

  /**
   * Cek prefix disallowed sufixes (kombinasi awalan dan akhiran yang tidak diizinkan)
   */
  private function Cek_Prefix_Disallowed_Sufixes($kata) {
    if (preg_match('/^(be)[[:alpha:]]+/(i)\z/i', $kata)) { // be- dan -i
      return true;
    }

    if (preg_match('/^(se)[[:alpha:]]+/(i|kan)\z/i', $kata)) { // se- dan -i,-kan
      return true;
    }

    if (preg_match('/^(di)[[:alpha:]]+/(an)\z/i', $kata)) { // di- dan -an
      return true;
    }

    if (preg_match('/^(me)[[:alpha:]]+/(an)\z/i', $kata)) { // me- dan -an
      return true;
    }

    if (preg_match('/^(ke)[[:alpha:]]+/(i|kan)\z/i', $kata)) { // ke- dan -i,-kan
      return true;
    }

    return false;
  }

  /**
   * Hapus derivation suffixes ("-i", "-an" atau "-kan")
   */
  function del_derivation_suffixes($kata) {
    if (!preg_match('/(i|an)\z/i', $kata)) return $kata; // Cek Suffixes
    
    $_kata = preg_replace('/(i|an)\z/i', '', $kata);
    if ($this->cek_kamus($_kata)) return $_kata;

    if (preg_match('/(kan)\z/i', $kata)) {
      $_kata = preg_replace('/(kan)\z/i', '', $kata);
      if ($this->cek_kamus($_kata)) return $_kata;
    }
  }

  /**
   * Hapus derivation prefix ("di-", "ke-", "se-", "te-", "be-", "me-", atau "pe-")
   */
  private function del_derivation_prefix($kata) {
    $kata_asal = $kata;

    /* —— Tentukan Tipe Awalan ————*/
    if (preg_match('/^(di|[ks]e)/', $kata)) { // Jika di-,ke-,se-
      $_kata = preg_replace('/^(di|[ks]e)/', '', $kata);
      if ($this->cek_kamus($_kata)) {
        return $_kata;
      }

      $_kata__ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata__)) {
        return $_kata__;
      }

      if (preg_match('/^(diper)/', $kata)) { //diper-
        $_kata = preg_replace('/^(diper)/', '', $kata);
        $_kata__ = $this->del_derivation_suffixes($_kata);

        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^(ke[bt]er)/', $kata)) {  //keber- dan keter-
        $_kata = preg_replace('/^(ke[bt]er)/', '', $kata);
        $_kata__ = $this->del_derivation_suffixes($_kata);

        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }
    }

    if (preg_match('/^([bt]e)/', $kata)) { //Jika awalannya adalah "te-","ter-", "be-","ber-"
      $_kata = preg_replace('/^([bt]e)/', '', $kata);
      if ($this->cek_kamus($_kata)) {
        return $_kata; // Jika ada balik
      }

      $_kata = preg_replace('/^([bt]e[lr])/', '', $kata);
      if ($this->cek_kamus($_kata)) {
        return $_kata; // Jika ada balik
      }

      $_kata__ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata__)) {
        return $_kata__;
      }
    }

    if (preg_match('/^([mp]e)/', $kata)) {
      $_kata = preg_replace('/^([mp]e)/', '', $kata);
      if ($this->cek_kamus($_kata)) {
        return $_kata; // Jika ada balik
      }

      $_kata__ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata__)) {
        return $_kata__;
      }

      if (preg_match('/^(memper)/', $kata)) {
        $_kata = preg_replace('/^(memper)/', '', $kata);
        if ($this->cek_kamus($kata)) {
          return $_kata;
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^([mp]eng)/', $kata)) {
        $_kata = preg_replace('/^([mp]eng)/', '', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }

        $_kata = preg_replace('/^([mp]eng)/', 'k', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^([mp]eny)/', $kata)) {
        
        $_kata = preg_replace('/^([mp]eny)/', 's', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^([mp]e[lr])/', $kata)) {
        $_kata = preg_replace('/^([mp]e[lr])/', '', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^([mp]en)/', $kata)) {
        $_kata = preg_replace('/^([mp]en)/', 't', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }

        $_kata = preg_replace('/^([mp]en)/', '', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }

      if (preg_match('/^([mp]em)/', $kata)) {
        $_kata = preg_replace('/^([mp]em)/', '', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }

        $_kata = preg_replace('/^([mp]em)/', 'p', $kata);
        if ($this->cek_kamus($_kata)) {
          return $_kata; // Jika ada balik
        }

        $_kata__ = $this->del_derivation_suffixes($_kata);
        if ($this->cek_kamus($_kata__)) {
          return $_kata__;
        }
      }
    }

    return $kata_asal;
  }

  public function stem($word) {
    // Jika Ada maka kata tersebut adalah kata dasar
    if ($this->cek_kamus($word)) return $word;

    //jika tidak ada dalam kamus maka dilakukan stemming
    $word = $this->del_inflection_suffixes($word);
    if ($this->cek_kamus($word)) {
      return $word;
    }

    $word = $this->del_derivation_suffixes($word);
    if ($this->cek_kamus($word)) {
      return $word;
    }

    $word = $this->del_derivation_prefix($word);
    if ($this->cek_kamus($word)) {
      return $word;
    }
  }
}