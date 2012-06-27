<?php
/**
 * Dumper - runs mysqldump
 * 
 */
namespace Prometheus\Adapter\MySQL;

class Dumper
{
    const RETURN_OK = 0;
    private $bin = '/usr/bin/mysqldump';
    
    /**
     * Perform a database dump using config constants
     * @param string $dumpPath 
     * @return array | false
     *
     */
    function dump($dumpPath)
    {
        $result   = false;
        $filename = $this->getFilename();
        $path     = sprintf('%s/%s', $dumpPath, $filename);
        $cmd      = $this->getCommandString($path);
        
        exec($cmd, $output, $returnValue);     
        
        if ($returnValue !== self::RETURN_OK) {
            $this->error('Possible failure creating backup; return value != 0!');
        }
        
        $verified = $this->verifyBackup($path);
        
        if ($verified) {
            // See what I did there?
            $byteSize = filesize($path);
            $size     = $this->getFileSize($byteSize);
            $result   = array('filename' => $filename, 
                              'size'     => $size);
        } 
        
        return $result;
    }
    
    /**
     * Scans file for common error strings
     * @param string $path - full path to backup
     *
     */
    function verifyBackup($path)
    {
        $contents = is_readable($path) ? file($path) : false;
        $verified = false;
        
        if ($contents) {
            // If we can at least read the file, that counts for something
            $verified = true;
            
            // Iterate each line in the file and look for errors, although
            // any error found will probably be in the first few lines
            foreach ($contents as $key => $line) {
                $lowerLine    = strtolower($line);
                
                $gotError     = strpos($line, 'got error')     !== false;
                $accessDenied = strpos($line, 'access denied') !== false;
                
                if ($gotError || $accessDenied) {
                    $verified = false;
                    break;
                }
            }
        }
        
        return $verified;
    }
    
    /**
     * Build command string for mysqldump using constants
     * defined in config
     * @param string $pathWithFilename
     * 
     */
    function getCommandString($pathWithFilename)
    {
        $hostFragment = '';
        if (in_array(DB_HOST, array('localhost', '127.0.0.1'))) {
            $hostFragment = sprintf(' -h%s', DB_HOST);
        }
        
        $redirectOutput = '2>&1';
        
        // Generate dump command
        $command = sprintf('%s%s -u%s -p%s %s > %s %s',
                           $this->bin,
                           $hostFragment,
                           DB_USER,
                           DB_PASSWORD,
                           DB_NAME,
                           $pathWithFilename,
                           $redirectOutput);
                           
        return $command;
    }
    
    /**
     * Generate a pretty filename based on db name
     * @return string
     *
     */
    function getFilename()
    {
        return sprintf('%s_%s.sql', 
                       DB_NAME,
                       date('M_j_Y_h:i_A'));
    }
    
    /**
     * Formats a filesize in a human readable form
     * @param int $size - filesize in bytes
     *
     */
    function getFileSize($size)
    {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        
        return sprintf('%d%s', round($size, 2), $units[$i]);
    }
}



























