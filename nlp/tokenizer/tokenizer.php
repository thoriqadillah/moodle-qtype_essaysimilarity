<?php

interface tokenizer {
    
    /**
     * Turn string into tokens
     */
    public function tokenize(string $str): array;
}