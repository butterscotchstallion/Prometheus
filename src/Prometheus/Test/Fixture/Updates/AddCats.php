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
        $result = $this->addTable();

        // You'll often want to be able to run updates over and over. In order to do that
        // some checking is necessary. Here we'll run a simple COUNT() query to see
        // if the table has some breeds in it already.
        $result = $this->getAdapter()->fetch('SELECT COUNT(*) as breedCount
                                              FROM cats c');
        
        // No breeds found - proceed!
        if (isset($result->breedCount) && $result->breedCount == 0) {
            $this->addBreeds();
        } else {
            $this->getConsole()->info('Has breeds. Not adding.');
        }
        
        // It's important to return something to let Prometheus know
        // that your update completed successfully.
        return $result;
    }
    
    function addTable()
    {
        $table  = 'cats';
        $result = true;
        
        if ($this->hasTable($table) === false) {
            $this->getConsole()->info(sprintf('Adding table "%s"', $table));
            
            $add = $this->save('CREATE TABLE `cats` (
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
    
    function addBreeds()
    {
        // Thanks Wikipedia!
        $breeds = array('American Curl',
                        'American Longhair',
                        'American Shorthair',
                        'Abyssinian',
                        'Aegean',
                        'Australian Mist',
                        'American Polydactyl',
                        'American Wirehair',
                        'Arabian Mau',
                        'Asian',
                        'Asian Semi-longhair',
                        'Balinese',
                        'Bambino',
                        'Bengal',
                        'Birman',
                        'Bombay',
                        'Brazilian Shorthair',
                        'British Shorthair',
                        'British Longhair',
                        'Burmese',
                        'Burmilla',
                        'Calico',
                        'California Spangled ',
                        'Chantilly/Tiffany',
                        'Chartreux',
                        'Chausie',
                        'Cheetoh',
                        'Colorpoint Shorthair',
                        'Cornish Rex',
                        'Cymric',
                        'Cyprus Aphrodite',
                        'Devon Rex',
                        'Domestic shorthair ',
                        'Don Sphynx',
                        'Dragon Li',
                        'Dwelf',
                        'Egyptian Mau',
                        'European Shorthair',
                        'Exotic Shorthair',
                        'German Rex',
                        'Havana Brown',
                        'Himalayan/Colorpoint Persian',
                        'Japanese Bobtail',
                        'Javanese',
                        'Korat',
                        'Kurilian Bobtail',
                        'LaPerm',
                        'Maine Coon',
                        'Manx',
                        'Mekong bobtail',
                        'Minskin',
                        'Munchkin',
                        'Nebelung',
                        'Napoleon',
                        'Norwegian Forest ',
                        'Ocicat',
                        'Ojos Azules',
                        'Oregon Rex',
                        'Oriental Bicolor',
                        'Oriental Shorthair',
                        'Oriental Longhair',
                        'Persian',
                        'Peterbald',
                        'Pixie-bob',
                        'Ragamuffin',
                        'Ragdoll',
                        'Russian Blue',
                        'Tabby',
                        'Savannah',
                        'Scottish Fold',
                        'Selkirk Rex',
                        'Serengeti',
                        'Siamese',
                        'Siberian',
                        'Singapura',
                        'Snowshoe',
                        'Sokoke',
                        'Somali',
                        'Sphynx',
                        'Thai',
                        'Tonkinese',
                        'Toyger',
                        'Turkish Angora',
                        'Turkish Van',
                        'Ukrainian Levkoy',
                        'Ussuri',
                        'York Chocolate');
                        
        // In a real scenario I would use a single insert statement
        // with multiple values (INSERT INTO ... VALUES (),(),()....)
        // but I want to test the duration message
        foreach ($breeds as $key => $cat) {
            $addBreed = $this->save('INSERT INTO cats(breed) VALUES(:breed)',
                                    array(':breed' => $cat));
            
            if ($addBreed) {
                $this->getConsole()->info(sprintf('Added cat #%d "%s"', $key+1, $cat));
            } else {
                // XXX perhaps add option to break on first error...
                $this->getConsole()->warn(sprintf('Failed to add cat %d "%s"', $key+1, $cat));
            }
        }
    }
}







