<?php
/**
 * Update example
 *
 */
use Prometheus\Update\SystemUpdate,
    Prometheus\Update\SystemUpdateInterface;

class AddCats extends SystemUpdate implements SystemUpdateInterface
{
    function run()
    {
        // It's important to return something to let Prometheus know
        // that your update completed successfully.
        return $this->addTable();
    }
    
    function addTable()
    {
        $table  = 'cats';
        $result = true;
        
        if ($this->hasTable($table) === false) {
            $this->getConsole()->info(sprintf('Adding table "%s"', $table));
            
            $result = $this->save('CREATE TABLE `prometheus`.`cats` (
                                      `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                                      `breed` VARCHAR(45) NOT NULL,
                                      PRIMARY KEY (`id`)
                                    )
                                    ENGINE = InnoDB
                                    CHARACTER SET utf8 COLLATE utf8_general_ci');
        } else {
            $this->getConsole()->warn(sprintf('Table "%s" exists', $table));
        }
        
        return $result;
    }
}







