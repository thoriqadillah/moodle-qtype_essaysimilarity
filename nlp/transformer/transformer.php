<?php

require_once('matrix.php');

interface transofrmer {

    public function transform(matrix $matrix): array;
}