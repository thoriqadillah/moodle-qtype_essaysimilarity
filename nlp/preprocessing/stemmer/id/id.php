<?php

include('./../stemmer.php');

/**
 * Stemming bahasa indonesia menggunakan algoritma Nazief dan Adriani
 * Credit to @ilhamdp10, copied and modified from https://github.com/ilhamdp10/algoritma-stemming-nazief-adriani/blob/master/enhanced_CS.php
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
   * Hapus inflection suffixes (“-lah”, “-kah”, “-ku”, “-mu”, atau “-nya”)
   */
  private function del_inflection_suffixes($kata) {
    $suffix = '/([km]u|nya|[kl]ah|pun)\z/i';
    if (!preg_match($suffix, $kata)) return $kata;

    $_kata = preg_replace($suffix, '', $kata);
    if (preg_match($suffix, $kata)) { // Cek Inflection Suffixes
      $_kata = preg_replace($suffix, '', $kata);

      if (!preg_match('/([klt]ah|pun)\z/i', $kata)) return $_kata;
        
      // Jika berupa particles (“-lah”, “-kah”, “-tah” atau “-pun”)
      // Hapus Possesive Pronouns (“-ku”, “-mu”, atau “-nya”)
      if (preg_match('/([km]u|nya)\z/i', $_kata)) {  
        return preg_replace('/([km]u|nya)\z/i', '', $_kata);
      }
    }

    return $kata;
  }

  /**
   * Cek prefix disallowed sufixes (kombinasi awalan dan akhiran yang tidak diizinkan)
   */
  private function cek_prefix_disallowed_suffixes($kata) {
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

    if ($this->cek_prefix_disallowed_suffixes($kata)) {
      return $kata;
    }

    return $kata;
  }

  /**
   * Hapus derivation prefix ("di-", "ke-", "se-", "te-", "be-", "me-", atau "pe-")
   */
  private function del_derivation_prefix($kata) {
    $kata_asal = $kata;	
    /* ------ Tentukan Tipe Awalan ------------*/
    if (preg_match('/^(di|[ks]e)\S{1,}/', $kata)) { // Jika di-,ke-,se-
      $_kata = preg_replace('/^(di|[ks]e)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    if (preg_match('/^([^aiueo])e\\1[aiueo]\S{1,}/i', $kata)) { // aturan  37
      $_kata = preg_replace('/^([^aiueo])e/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    if (preg_match('/^([tmbp]e)\S{1,}/', $kata)) { //Jika awalannya adalah “te-”, “me-”, “be-”, atau “pe-”
      /*------------ Awalan “be-”, ---------------------------------------------*/
      if (preg_match('/^(be)\S{1,}/', $kata)) { // Jika awalan “be-”,
        if (preg_match('/^(ber)[aiueo]\S{1,}/', $kata)) { // aturan 1.
          $_kata = preg_replace('/^(ber)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(ber)/', 'r', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(ber)[^aiueor][[:alpha:]](?!er)\S{1,}/', $kata)) { //aturan  2.
          $_kata = preg_replace('/^(ber)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(ber)[^aiueor][[:alpha:]]er[aiueo]\S{1,}/', $kata)) { //aturan  3.
          $_kata = preg_replace('/^(ber)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^belajar\S{0,}/', $kata)) { //aturan  4.
          $_kata = preg_replace('/^(bel)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(be)[^aiueolr]er[^aiueo]\S{1,}/', $kata)) { //aturan  5.
          $_kata = preg_replace('/^(be)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
      }

      /*------------end “be-”, ---------------------------------------------*/
      /*------------ Awalan “te-”, ---------------------------------------------*/
      if (preg_match('/^(te)\S{1,}/', $kata)) { // Jika awalan “te-”,
      
        if (preg_match('/^(terr)\S{1,}/', $kata)) { 
          return $kata;
        }

        if (preg_match('/^(ter)[aiueo]\S{1,}/', $kata)) { // aturan 6.
          $_kata = preg_replace('/^(ter)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(ter)/', 'r', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(ter)[^aiueor]er[aiueo]\S{1,}/', $kata)) { // aturan 7.
          $_kata = preg_replace('/^(ter)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }

        if (preg_match('/^(ter)[^aiueor](?!er)\S{1,}/', $kata)) { // aturan 8.
          $_kata = preg_replace('/^(ter)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }

        if (preg_match('/^(te)[^aiueor]er[aiueo]\S{1,}/', $kata)) { // aturan 9.
          $_kata = preg_replace('/^(te)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(ter)[^aiueor]er[^aiueo]\S{1,}/', $kata)) { // aturan  35 belum bisa
          $_kata = preg_replace('/^(ter)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
      }

      /*------------end “te-”, ---------------------------------------------*/
      /*------------ Awalan “me-”, ---------------------------------------------*/
      if (preg_match('/^(me)\S{1,}/', $kata)) { // Jika awalan “me-”,
    
        if (preg_match('/^(me)[lrwyv][aiueo]/', $kata)) { // aturan 10
          $_kata = preg_replace('/^(me)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }				

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(mem)[bfvp]\S{1,}/', $kata)) { // aturan 11.
          $_kata = preg_replace('/^(mem)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(mem)((r[aiueo])|[aiueo])\S{1,}/', $kata)) {//aturan 13
          $_kata = preg_replace('/^(mem)/', 'm', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(mem)/', 'p', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(men)[cdjszt]\S{1,}/', $kata)) { // aturan 14.
          $_kata = preg_replace('/^(men)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(men)[aiueo]\S{1,}/', $kata)) {//aturan 15
          $_kata = preg_replace('/^(men)/', 'n', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(men)/', 't', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(meng)[ghqk]\S{1,}/', $kata)) { // aturan 16.
          $_kata = preg_replace('/^(meng)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(meng)[aiueo]\S{1,}/', $kata)) { // aturan 17
          $_kata = preg_replace('/^(meng)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(meng)/', 'k', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(menge)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(meny)[aiueo]\S{1,}/', $kata)) { // aturan 18.
          $_kata = preg_replace('/^(meny)/', 's', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(me)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
      }

      /*------------end “me-”, ---------------------------------------------*/
      /*------------ Awalan “pe-”, ---------------------------------------------*/
      if (preg_match('/^(pe)\S{1,}/', $kata)) { // Jika awalan “pe-”,
      
        if (preg_match('/^(pe)[wy]\S{1,}/', $kata)) { // aturan 20.
          $_kata = preg_replace('/^(pe)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }	

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }				
        }
        
        if (preg_match('/^(per)[aiueo]\S{1,}/', $kata)) { // aturan 21
          $_kata = preg_replace('/^(per)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(per)/', 'r', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }

        if (preg_match('/^(per)[^aiueor][[:alpha:]](?!er)\S{1,}/', $kata)) { // aturan  23
          $_kata = preg_replace('/^(per)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(per)[^aiueor][[:alpha:]](er)[aiueo]\S{1,}/', $kata)) { // aturan  24
          $_kata = preg_replace('/^(per)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pem)[bfv]\S{1,}/', $kata)) { // aturan  25
          $_kata = preg_replace('/^(pem)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pem)(r[aiueo]|[aiueo])\S{1,}/', $kata)) { // aturan  26
          $_kata = preg_replace('/^(pem)/', 'm', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(pem)/', 'p', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pen)[cdjzt]\S{1,}/', $kata)) { // aturan  27
          $_kata = preg_replace('/^(pen)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pen)[aiueo]\S{1,}/', $kata)) { // aturan  28
          $_kata = preg_replace('/^(pen)/', 'n', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(pen)/', 't', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(peng)[^aiueo]\S{1,}/', $kata)) { // aturan  29
          $_kata = preg_replace('/^(peng)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(peng)[aiueo]\S{1,}/', $kata)) { // aturan  30
          $_kata = preg_replace('/^(peng)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(peng)/', 'k', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(penge)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(peny)[aiueo]\S{1,}/', $kata)) { // aturan  31
          $_kata = preg_replace('/^(peny)/', 's', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }

          $_kata = preg_replace('/^(pe)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pel)[aiueo]\S{1,}/', $kata)) { // aturan  32
          $_kata = preg_replace('/^(pel)/', 'l', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pelajar)\S{0,}/', $kata)) {
          $_kata = preg_replace('/^(pel)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pe)[^rwylmn]er[aiueo]\S{1,}/', $kata)) { // aturan  33
          $_kata = preg_replace('/^(pe)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pe)[^rwylmn](?!er)\S{1,}/', $kata)) { // aturan  34
          $_kata = preg_replace('/^(pe)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
        
        if (preg_match('/^(pe)[^aiueor]er[^aiueo]\S{1,}/', $kata)) { // aturan  36
          $_kata = preg_replace('/^(pe)/', '', $kata);
          if ($this->cek_kamus($_kata)) {			
            return $_kata; // Jika ada balik
          }

          $_kata_ = $this->del_derivation_suffixes($_kata);
          if ($this->cek_kamus($_kata_)) {
            return $_kata_;
          }
        }
      }
    }

    /*------------end “pe-”, ---------------------------------------------*/
    /*------------ Awalan “memper-”, ---------------------------------------------*/
    if (preg_match('/^(memper)\S{1,}/', $kata)) {				
      $_kata = preg_replace('/^(memper)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      //*-- Cek luluh -r ----------
      $_kata = preg_replace('/^(memper)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end “memper-”, ---------------------------------------------*/
    /*------------ Awalan “mempel-”, ---------------------------------------------*/
    if (preg_match('/^(mempel)\S{1,}/', $kata)) {				
      $_kata = preg_replace('/^(mempel)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      //*-- Cek luluh -r ----------
      $_kata = preg_replace('/^(mempel)/', 'l', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }
    /*------------end “mempel-”, ---------------------------------------------*/
    /*------------awalan  “memter-”, ---------------------------------------------*/
    if (preg_match('/^(menter)\S{1,}/', $kata)) {				
      $_kata = preg_replace('/^(menter)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      //*-- Cek luluh -r ----------
      $_kata = preg_replace('/^(menter)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }
    /*------------end “memter-”, ---------------------------------------------*/
    /*------------awalan “member-”, ---------------------------------------------*/
    if (preg_match('/^(member)\S{1,}/', $kata)) {				
      $_kata = preg_replace('/^(member)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      //*-- Cek luluh -r ----------
      $_kata = preg_replace('/^(member)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end member-”, ---------------------------------------------*/
    /*------------awalan “diper-”, ---------------------------------------------*/
    if (preg_match('/^(diper)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(diper)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -r ----------*/
      $_kata = preg_replace('/^(diper)', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end “diper-”, ---------------------------------------------*/
    /*------------awalan “diter-”, ---------------------------------------------*/
    if (preg_match('/^(diter)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(diter)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -r ----------*/
      $_kata = preg_replace('/^(diter)', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end “diter-”, ---------------------------------------------*/
    /*------------awalan “dipel-”, ---------------------------------------------*/
    if (preg_match('/^(dipel)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(dipel)/', 'l', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -l----------*/
      $_kata = preg_replace('/^(dipel)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end dipel-”, ---------------------------------------------*/
    /*------------kata “terpelajar”(kasus khusus), ---------------------------------------------*/
    if (preg_match('/terpelajar/', $kata)) {			
      $_kata = preg_replace('/terpel/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end “terpelajar”-”, ---------------------------------------------*/
    /*------------kata seseorang(kasus khusus), ---------------------------------------------*/
    if (preg_match('/seseorang/', $kata)) {			
      $_kata = preg_replace('/^(sese)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }
    }

    /*------------end seseorang-”, ---------------------------------------------*/
    /*------------awalan "diber-"---------------------------------------------*/
    if (preg_match('/^(diber)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(diber)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -l----------*/
      $_kata = preg_replace('/^(diber)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }
    
    /*------------end "diber-"---------------------------------------------*/
    /*------------awalan "keber-"---------------------------------------------*/
    if (preg_match('/^(keber)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(keber)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -l----------*/
      $_kata = preg_replace('/^(keber)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end "keber-"---------------------------------------------*/
    /*------------awalan "keter-"---------------------------------------------*/
    if (preg_match('/^(keter)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(keter)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }

      /*-- Cek luluh -l----------*/
      $_kata = preg_replace('/^(keter)/', 'r', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end "keter-"---------------------------------------------*/
    /*------------awalan "berke-"---------------------------------------------*/
    if (preg_match('/^(berke)\S{1,}/', $kata)) {			
      $_kata = preg_replace('/^(berke)/', '', $kata);
      if ($this->cek_kamus($_kata)) {			
        return $_kata; // Jika ada balik
      }

      $_kata_ = $this->del_derivation_suffixes($_kata);
      if ($this->cek_kamus($_kata_)) {
        return $_kata_;
      }
    }

    /*------------end "berke-"---------------------------------------------*/
    /* --- Cek Ada Tidaknya Prefik/Awalan (“di-”, “ke-”, “se-”, “te-”, “be-”, “me-”, atau “pe-”) ------*/
    if (preg_match('/^(di|[kstbmp]e)\S{1,}/', $kata) == FALSE) {
      return $kata_asal;
    }
    
    return $kata_asal;
  }

  private function is_plural($word) {
    // -ku|-mu|-nya
    // nikmat-Ku, etc
    if (preg_match('/^(.*)-(ku|mu|nya|lah|kah|tah|pun)$/', $word, $words)) {
      return strpos($words[1], '-') !== false;
    }

    return strpos($word, '-') !== false;
  }

  public function stem($word) {
    // Jika Ada maka kata tersebut adalah kata dasar
    if ($this->cek_kamus($word)) return $word;

    // stem kata plural
    if ($this->is_plural($word)) {
      preg_match('/^(.*)-(.*)$/', $word, $words);
      if (!empty($words)) {

        // malaikat-malaikat-nya -> malaikat malaikatnya
        $suffix = $words[2];
        if (in_array($suffix, ['ku', 'mu', 'nya', 'lah', 'kah', 'tah', 'pun']) &&
        preg_match('/^(.*)-(.*)$/', $words[1], $words)) {
          $words[2] .= $suffix;
        }
        
        $stem1 = $this->stem($words[1]);
        $stem2 = $this->stem($words[2]);
        
        if ($stem1 == $stem2) {
          return $stem1;
        }
      }
    }

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

    return $word;
  }
}