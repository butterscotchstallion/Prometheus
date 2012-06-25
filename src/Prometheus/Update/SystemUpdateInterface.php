<?php
/**
 * SystemUpdateInterface - system updates must implement these
 * methods
 *
 */
namespace Prometheus\Update;

interface SystemUpdateInterface
{   
    /**
     * After each update is instantiated, this method is called. Each
     * update must implement it
     */
    function run();   
}








