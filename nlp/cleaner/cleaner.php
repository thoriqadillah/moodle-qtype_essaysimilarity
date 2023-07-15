<?php

interface cleaner {

    /**
     * Perform vector cleaning
     */
    public function clean(array $vector): array;
}