<?php

interface vectorizer {
    
    /**
     * Turn string into tokens
     */
    public function vectorize(string $str): array;
}