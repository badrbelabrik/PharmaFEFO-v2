<?php

namespace Controller\Web;

class HomeController
{
    public function index(): void {


        // Show the landing page
        require_once __DIR__ . '/../../../templates/home/index.php';
    }
}