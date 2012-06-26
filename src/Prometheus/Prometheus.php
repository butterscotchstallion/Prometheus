<?php
/**
 * Prometheus - find/run updates
 *
 */
namespace Prometheus;

class Prometheus
{
    private $console;
    private $success          = false;
    private $updatesProcessed = 0;
    
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
     * @return bool
     *
     */
    function run(array $directories)
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
                                
                                try {
                                    // Initialize update and run()
                                    if (class_exists($class)) {
                                        $update = new $class();
                                        $result = $update->run();
                                        
                                        if ($result) {
                                            $this->updatesProcessed++;                                            
                                            $this->console->ok(sprintf('Processed "%s" successfully!', $class));
                                        } else {
                                            $this->abort(sprintf('Error processing "%s"', $class));
                                        }
                                    
                                    } else {
                                        $this->abort(sprintf('Class "%s" does not exist', $class));
                                    }
                                    
                                } catch (Exception $e) {
                                    $this->abort($e->getMessage());
                                }                                
                                
                            } else {
                                $this->console->warn(sprintf('Could not read "%s"', $f));
                            }
                        }
                        
                        // If we've gotten this far, everything is cool
                        $this->success = true;
                        
                        $this->console->log(sprintf("[OK] Processed %d updates successfully", 
                                                    $this->updatesProcessed),
                                            'bold_cyan');
                        
                        return $this->success;
                        
                    } else {
                        $this->abort(sprintf('No files found in %s', $dir));
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
        $this->success = false;
        $this->console->error($msg);
    }
    
    function getProcessedUpdates()
    {
        return $this->updatesProcessed;
    }
}