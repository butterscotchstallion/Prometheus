<?php
/**
 * Prometheus - find/run updates
 *
 */
namespace Prometheus;

class Prometheus
{
    private $console;
    
    /**
     * @param array $connectionInfo (optional, but necessary for db updates)
     *
     */
    function __construct($connectionInfo = array())
    {
        $this->console        = new Console\Console();
        $this->connectionInfo = $connectionInfo;
    }
    
    /**
     * Runs all upgrades in specified directories
     * @param array $directories
     *
     */
    function run($directories)
    {
        if ($directories) {
            foreach ($directories as $dir) {
                if (is_readable($dir)) {
                    $files = glob(sprintf('%s/*.php', $dir));
                    
                    if ($files) {
                        $this->console->ok(sprintf('Processing %s (%d files)', 
                                                   basename($dir), 
                                                   count($files)));                        
                        
                        foreach ($files as $f) {
                            // Log current update
                            $filename = basename($f);
                            
                            // Trim off the file extension to get the class name
                            // (the class name must match the filename)
                            $class    = rtrim($filename, '.php');
                            
                            // Include file
                            // Instantiate class
                            // Run!
                            if (is_readable($f)) {
                                
                                $this->console->info(sprintf('Running %s', $class));
                                
                                include $f;
                                
                                $update = new $class();
                                $result = $update->run();
                                
                                if ($result) {
                                    $this->console->ok(sprintf('Processed "%s" successfully!', $class));
                                } else {
                                    $this->console->error(sprintf('Error processing "%s"', $class));
                                }
                                
                            } else {
                                $this->console->warn(sprintf('Could not read "%s"', $f));
                            }
                        }
                        
                    } else {
                        $this->console->warn(sprintf('No files found in %s', $dir));
                    }
                    
                } else {
                    $this->console->warn(sprintf('Could not read "%s"', $dir));
                }
            }
            
        } else {
            $this->abort('No directories specified.');
        }
    }
    
    function abort($msg)
    {
        $this->console->error($msg);
    }
}