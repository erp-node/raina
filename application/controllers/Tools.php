<?php
error_reporting(0);
class Tools extends CI_Controller {

        public function message($to = 'World')
        {
                echo "Hello {$to}!".PHP_EOL;
        }

		
}