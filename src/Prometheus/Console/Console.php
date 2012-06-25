<?php
/**
 * Console - command-line utilities
 * 
 */
namespace Prometheus\Console;

class Console
{
    private $colors = array('light_blue' => '1;34',
                            'red'        => '0;31',
                            'yellow'     => '1;33',
                            'green'      => '1;32',
                            'dark_gray'  => '1;30',
                            'bold_red'   => '1;31',
                            'bold_blue'  => '1;34',
                            'purple'     => '0;35',
                            'cyan'       => '0;36',
                            'bold_cyan'  => '1;36',
                            'white'      => '1;37',
                            'bold_gray'  => '0;37');
    
    /**
     * Returns a colored stirng
     * @param  string $input
     * @param  string $color
     * @return string
     */
    function getColoredString($input, $color)
    {
        return sprintf("\033[%sm%s\033[0m", 
                       $this->getColor($color), 
                       $input);
    }
    
    /**
     * Return a color based on input
     * @param string $color
     * @return string
     */
    function getColor($color)
    {
        return isset($this->colors[$color]) ? $this->colors[$color] : '';
    }
    
    /**
     * Print a message to stdout optionally with colors
     * @param string $msg
     * @param string $color
     * @return null
     *
     */
    function log($msg, $color = null, $newLine = true)
    {
        if ($color) {
            $msg = $this->getColoredString($msg, $color);
        } 
        
        echo $msg;
        
        if ($newLine) {
            echo PHP_EOL;
        }
    }
    
    function ok($msg)
    {
        return $this->log(sprintf('[OK] %s', $msg), 'green');
    }
    
    function error($msg)
    {
        return $this->log(sprintf('[ERROR] %s', $msg), 'red');
    }
    
    function warn($msg)
    {
        return $this->log(sprintf('[WARN] %s', $msg), 'yellow');
    }
    
    function info($msg)
    {
        return $this->log(sprintf('[INFO] %s', $msg), 'purple');
    }
}












