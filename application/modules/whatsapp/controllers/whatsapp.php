<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Whatsapp extends Admin_Controller
{
    public function index()
    {
        $this->layout->buffer('content', 'whatsapp/index');
        $this->layout->render();
    }
}
