<?php

class Profile {

    protected static $instance;

    public static function get_instance() {
        null === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function foo() {

        return $this->var; // never echo or print in a shortcode!
    }
}
