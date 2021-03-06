<?php
/**
 * Prometheus - find/run updates
 *
 */
namespace Prometheus;

use Monolog\Logger,
    Monolog\Handler\StreamHandler,
    Exception;

class Prometheus
{
    private $console;
    private $success          = false;
    private $updatesProcessed = 0;
    private $logPath;
    private $logger;
    private $dumper;
    
    // Path
    private $backupPath;
    
    // Full path to completed backup
    private $completedBackupPath;
    private $removeBackupOnSuccess = true;
    
    // Will send report if there is something
    // in this array
    private $reportRecipients      = array();

    /**
     * @param array $connectionInfo
     *
     */
    function __construct($connectionInfo = array())
    {
        $this->console        = new Console\Console();
        $this->connectionInfo = $connectionInfo;
        $this->dumper         = new Adapter\MySQL\Dumper();
        
        $this->printIntroText();
    }
    
    /**
     * Runs all upgrades in specified directories
     * @param array $directories
     * @return bool
     *
     */
    function run(array $directories)
    {
        $this->backupDatabase();
        
        if ($directories) {
            $start = new \Datetime();
            
            foreach ($directories as $dir) {
                if (is_readable($dir)) {
                    $files = glob(sprintf('%s/*.php', $dir));
                    
                    if ($files) {
                        $this->ok(sprintf('Processing %s (%d files)', 
                                                   basename($dir), 
                                                   count($files)));                        
                        
                        $totalUpdates = 0;
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
                                
                                $this->info(sprintf('Running %s', $class));
                                
                                include $f;
                                
                                try {
                                    // Initialize update and run()
                                    if (class_exists($class)) {
                                        $totalUpdates++;
                                        $update = new $class();
                                        $result = $update->run();
                                        
                                        if ($result) {
                                            $this->updatesProcessed++;                                            
                                            $this->ok(sprintf('Processed "%s" successfully!', $class));
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
                                $this->warn(sprintf('Could not read "%s"', $f));
                            }
                        }
                        
                        // If we've gotten this far, everything is cool
                        $this->success = true;
                        
                        // Remove backup
                        if ($this->removeBackupOnSuccess) {                            
                            $result = $this->removeBackup();
                            
                            if ($result) {
                                $this->info('Removed backup');
                            } else {
                                $this->warn('Error Removing backup!');
                            }
                        }
                        
                        // Send report
                        if ($this->reportRecipients) {
                            // Get total duration of upgrade
                            $end      = new \Datetime();
                            $interval = $start->diff($end);
                            $duration = $interval->format("%h hours %i minutes %s seconds");
                            
                            $this->info('Sending report...');
                            
                            $result   = $this->sendReport(array('okCount'  => $this->updatesProcessed,
                                                                'total'    => $totalUpdates,
                                                                'duration' => $duration));
                                          
                            if ($result) {
                                $this->ok('Report sent successfully');
                            } else {
                                $this->error('Error sending report!');
                            }
                        }
                        
                        $this->ok(sprintf("Processed %d/%d updates successfully", 
                                            $this->updatesProcessed,
                                            $totalUpdates));                        
                        
                    } else {
                        $this->abort(sprintf('No files found in %s', $dir));
                    }
                    
                } else {
                    $this->warn(sprintf('Could not read "%s"', $dir));
                }
            }
            
        } else {
            $this->abort('No directories specified.');
        }
        
        return $this->success;                       
    }
    
    /**
     * Send report 
     * @param array $info
     * @return bool
     */
    function sendReport($info)
    {
        // Don't try to send mail unless these are valid
        $user = defined('MAIL_USER')     ? MAIL_USER     : '';
        $pw   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        
        // Bail out
        if (!$user || !$pw) {
            $this->warn('Mail user/password not set. Cannot send report.');
            return false;
        }
        
        try {
            // Set up/log body
            $body = sprintf('%d/%d updates processed successfully in %s',
                              $info['okCount'],
                              $info['total'],
                              $info['duration']);
            
            $this->logger->addInfo($body);
            
            $message = \Swift_Message::newInstance()
            
            // Give the message a subject
            ->setSubject('Prometheus Upgrade Report')

            // Set the From address with an associative array
            ->setFrom(array(MAIL_FROM_ADDR => MAIL_FROM_NAME))

            // Set the To addresses with an associative array
            ->setTo($this->reportRecipients)

            // Give it a body
            ->setBody($body);
            
            // And optionally an alternative body
            //->addPart('<q>Here is the message itself</q>', 'text/html')

            // Optionally add any attachments
            //->attach(Swift_Attachment::fromPath('my-document.pdf'));
            $transport = \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
                        ->setUsername($user)
                        ->setPassword($pw);
            
            $mailer    = \Swift_Mailer::newInstance($transport);
            
            $result = $mailer->send($message);
            
            return $result;
            
        } catch (\Swift_TransportException $e) {
            $this->abort(sprintf('Mailer: %s', $e->getMessage()));
            return false;
        }
    }
    
    /**
     * Use Dumper to back up db
     *
     */
    function backupDatabase()
    {
        if ($this->backupPath) {
            $result                    = $this->dumper->dump($this->backupPath);
            $this->completedBackupPath = $result['filename'];
            
            if ($result) {
                $this->ok(sprintf('Backup created: %s (%s)', 
                          $result['filename'],
                          $result['size']));
            } else {
                $this->error('Error creating backup!');
            }
        }
    }
    
    /**
     * Remove backup
     *
     */
    function removeBackup()
    {
        $result = unlink($this->completedBackupPath);

        return $result && is_readable($this->completedBackupPath) === false;
    }
    
    /**
     * Prints intro message
     *
     */
    function printIntroText()
    {
        $this->printBorder();
        $this->console->log(sprintf('* Prometheus: a tool for automated database changes %s *', 
                            str_repeat(' ', 8)),
                            'yellow');
        $this->printBorder();
    }
    
    /**
     * Prints a pretty ascii border around the text
     * @param int $length in characters
     *
     */
    function printBorder($length = 55)
    {
        $borderChar = '*';
        
        foreach (range(0, $length - 1) as $key => $chr) {
            if ($key % 2) {
                $color = 'yellow';
            } else {
                $color = 'red';
            }
            
            $this->console->log($borderChar, $color, false);
        }
        
        $this->console->log('');
    }
    
    /**
     * Indicate failure by setting $this->success to false, and log
     * the msg in red
     *
     */
    function abort($msg)
    {
        if ($this->logger) {
            $this->logger->addError($msg);
        }
        
        $this->success = false;
        $this->console->error($msg);
    }
    
    /**
     * Enables logging
     * @param string $path - full path to log
     *
     */
    function enableLogging($path)
    {   
        try {
            // Set up logger
            $this->logger = new Logger('Prometheus');   
            
            // Issue #5 - Logger does not log
            // Fixed by removing second argument to StreamHandler
            $handler      = new StreamHandler($path);
            $this->logger->pushHandler($handler);
        } catch (Exception $e) {
            $this->error(sprintf('Failed to initialize logger: %s', $e->getMessage()));
        }
    }
    
    /**
     * Enables db backup
     * @param string $path - full path to log
     *
     */
    function enableDatabaseBackup($path)
    {   
        $this->backupPath = $path;
    }
    
    function warn($msg)
    {
        if ($this->logger) {
            $this->logger->addWarning($msg);
        }
        
        return $this->console->warn($msg);
    }
    
    function ok($msg)
    {
        if ($this->logger) {
            $this->logger->info($msg);
        }
        
        return $this->console->ok($msg);
    }
    
    function info($msg)
    {
        if ($this->logger) {
            $this->logger->info($msg);
        }
        
        return $this->console->info($msg);
    }
    
    function disableRemoveBackupOnSuccess()
    {
        $this->removeBackupOnSuccess = false;
    }
    
    /**
     * Add emails to this array to receive a report
     * once the upgrade has completed
     * @param array $recipients
     *
     */
    function setReportRecipients(array $recipients) 
    {
        $this->reportRecipients = $recipients;
    }
    
    function getReportRecipients()
    {
        return $this->reportRecipients;
    }
}



















