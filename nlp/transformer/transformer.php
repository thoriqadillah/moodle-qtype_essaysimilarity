<?php

require_once('matrix.php');

interface transofrmer {

    /**
     * @param matrix $matrix The document matrix want to be transformed
     * @return array Transofrmed documents
     */
    function transform($matrix);
}