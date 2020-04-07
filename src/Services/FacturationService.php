<?php

namespace App\Services;

class FacturationService
{
    private $enteteFacture;

    public function __construct($entFacture)
    {
        $this->enteteFacture = $entFacture;
        
    }

    public function create()
    {
        dump("Creation de la facture X realisee". $this->enteteFacture);
    }

}