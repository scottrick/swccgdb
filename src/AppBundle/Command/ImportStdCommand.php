<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Set;
use AppBundle\Entity\Card;

class ImportStdCommand extends ContainerAwareCommand {
    /* @var $em Doctrine ORM EntityManager */
    private $em;

    /* @var $output OutputInterface */
    private $output;

    private $collections = [];

    protected function configure() {
        $this
        ->setName('app:import:std')
        ->setDescription('Import cards data file in json format')
        ->addArgument('path', InputArgument::REQUIRED,
                      'Path to the repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $path     = $input->getArgument('path');
        $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->output = $output;

        /* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
        $helper = $this->getHelper('question');

        // sides

        $output->writeln("Importing Sides...");
        $sidesFileInfo = $this->getFileInfo($path, 'sides.json');
        $imported = $this->importSidesJsonFile($sidesFileInfo);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Side');
        $output->writeln("Done.");

        // types

        $output->writeln("Importing Types...");
        $typesFileInfo = $this->getFileInfo($path, 'types.json');
        $imported = $this->importTypesJsonFile($typesFileInfo);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Type');
        $output->writeln("Done.");

        // subtypes

        $output->writeln("Importing Subtypes...");
        $subtypesFileInfo = $this->getFileInfo($path, 'subtypes.json');
        $imported = $this->importSubtypesJsonFile($subtypesFileInfo);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Subtype');
        $output->writeln("Done.");

        // rarities

        $output->writeln("Importing Rarities...");
        $raritiesFileInfo = $this->getFileInfo($path, 'rarities.json');
        $imported = $this->importRaritiesJsonFile($raritiesFileInfo);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Rarity');
        $output->writeln("Done.");

        // cycles

        $output->writeln("Importing Cycles...");
        $cyclesFileInfo = $this->getFileInfo($path, 'cycles.json');
        $imported = $this->importCyclesJsonFile($cyclesFileInfo);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Cycle');
        $output->writeln("Done.");

        // second, sets

        $output->writeln("Importing Sets...");
        $setsFileInfo = $this->getFileInfo($path, 'sets.json');
        $imported = $this->importSetsJsonFile($setsFileInfo);
        $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $this->loadCollection('Set');
        $output->writeln("Done.");

        // third, cards

        $output->writeln("Importing Cards...");
        $fileSystemIterator = $this->getFileSystemIterator($path);
        $imported = [];
        foreach ($fileSystemIterator as $fileinfo) {
            $imported = array_merge($imported, $this->importCardsJsonFile($fileinfo));
        }
        #if (count($imported)) {
        #    $question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
        #    if (!$helper->ask($input, $output, $question)) {
        #        die();
        #    }
        #}
        $this->em->flush();
        $output->writeln("Done.");
    }

    ##
    ## [{"code": "light", "name": "Light"},
    ##  {"code": "dark",  "name": "Dark" }]
    ##
    ## select * FROM side;
    ## +----+-------+-------+
    ## | id | code  | name  |
    ## +----+-------+-------+
    ## |  1 | light | Light |
    ## |  2 | dark  | Dark  |
    ## +----+-------+-------+
    ##
    ## https://www.php.net/manual/en/class.splfileinfo.php
    ##
    protected function importSidesJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        ##
        ## Read in json file and return hash
        ##
        $list = $this->getDataFromFile($fileinfo);
        ##
        ## Parse hash
        ## parse "code" and "name" fields.
        ##
        foreach ($list as $data) {
            $side = $this->getEntityFromData('AppBundle\\Entity\\Side', $data, [
                    'code',
                    'name'
            ], [], []);
            if ($side) {
                $result[] = $side;
                ##
                ## Write fields to database.
                ##
                $this->em->persist($side);
            }
        }

        return $result;
    }

    protected function importTypesJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $list = $this->getDataFromFile($fileinfo);
        foreach ($list as $data) {
            $type = $this->getEntityFromData('AppBundle\\Entity\\Type', $data, [
                    'code',
                    'name'
            ], [], []);
            if ($type) {
                $result[] = $type;
                $this->em->persist($type);
            }
        }

        return $result;
    }

    protected function importSubtypesJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $list = $this->getDataFromFile($fileinfo);
        foreach ($list as $data) {
            $subtype = $this->getEntityFromData('AppBundle\\Entity\\Subtype', $data, [
                    'code',
                    'name'
            ], [], []);
            if ($subtype) {
                $result[] = $subtype;
                $this->em->persist($subtype);
            }
        }

        return $result;
    }

    protected function importRaritiesJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $list = $this->getDataFromFile($fileinfo);
        foreach ($list as $data) {
            $rarity = $this->getEntityFromData('AppBundle\\Entity\\Rarity', $data, [
                    'code',
                    'name'
            ], [], []);
            if ($rarity) {
                $result[] = $rarity;
                $this->em->persist($rarity);
            }
        }

        return $result;
    }

    protected function importCyclesJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $cyclesData = $this->getDataFromFile($fileinfo);
        foreach ($cyclesData as $cycleData) {
            $cycle = $this->getEntityFromData('AppBundle\Entity\Cycle', $cycleData, [
                    'code',
                    'name',
                    'position',
                    'size'
            ], [], []);
            if ($cycle) {
                $result[] = $cycle;
                $this->em->persist($cycle);
            }
        }

        return $result;
    }

    protected function importSetsJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $setsData = $this->getDataFromFile($fileinfo);
        foreach ($setsData as $setData) {
            $set = $this->getEntityFromData('AppBundle\Entity\Set', $setData, [
                    'code',
                    'name',
                    'position',
                    'size',
                    'date_release'
            ], [
                    'cycle_code'
            ], []);
            if ($set) {
                $result[] = $set;
                $this->em->persist($set);
            }
        }

        return $result;
    }

    protected function importCardsJsonFile(\SplFileInfo $fileinfo) {
        $result = [];

        $code = $fileinfo->getBasename('.json');

        $set = $this->em->getRepository('AppBundle:Set')->findOneBy(['code' => $code]);
        if (!$set) {
            throw new \Exception("Unable to find Set [$code]");
        }

        $cardsData = $this->getDataFromFile($fileinfo);
        foreach ($cardsData as $cardData) {
            $card = $this->getEntityFromData('AppBundle\Entity\Card', $cardData, [
                    'code',
                    'gametext',
                    'has_errata',
                    'image_url',
                    'name',
                    'position',
                    'uniqueness'
            ], [
                    'rarity_code',
                    'set_code',
                    'side_code',
                    'type_code',
                    'subtype_code'
            ], [
                    'ability',
                    'armor',
                    'characteristics',
                    'clone_army',
                    'dark_side_icons',
                    'dark_side_text',
                    'defense_value',
                    'defense_value_name',
                    'deploy',
                    'destiny',
                    'episode_1',
                    'episode_7',
                    'ferocity',
                    'first_order',
                    'force_aptitude',
                    'forfeit',
                    'grabber',
                    'hyperspeed',
                    'independent',
                    'is_horizontal',
                    'image_url_2',
                    'landspeed',
                    'light_side_icons',
                    'light_side_text',
                    'lore',
                    'maneuver',
                    'mobile',
                    'model_type',
                    'nav_computer',
                    'permanent_weapon',
                    'pilot',
                    'planet',
                    'politics',
                    'power',
                    'presence',
                    'republic',
                    'resistance',
                    'scomp_link',
                    'selective',
                    'separatist',
                    'site_creature',
                    'site_exterior',
                    'site_interior',
                    'site_starship',
                    'site_underground',
                    'site_underwater',
                    'site_vehicle',
                    'space',
                    'system_parsec',
                    'trade_federation',
                    'warrior'
            ]);
            if ($card) {
                $result[] = $card;
                $this->em->persist($card);
            }
        }

        return $result;
    }

    protected function copyFieldValueToEntity($entity, $entityName, $fieldName, $newJsonValue) {
        $metadata = $this->em->getClassMetadata($entityName);
        $type = $metadata->fieldMappings[$fieldName]['type'];

        // new value, by default what json gave us is the correct typed value
        $newTypedValue = $newJsonValue;

        // current value, by default the json, serialized value is the same as what's in the entity
        $getter = 'get'.ucfirst($fieldName);
        $currentJsonValue = $currentTypedValue = $entity->$getter();

        // if the field is a data, the default assumptions above are wrong
        if (in_array($type, ['date', 'datetime'])) {
            if ($newJsonValue !== null) {
                $newTypedValue = new \DateTime($newJsonValue);
            }
            if ($currentTypedValue !== null) {
                switch ($type) {
                    case 'date': {
                        $currentJsonValue = $currentTypedValue->format('Y-m-d');
                        break;
                    }
                    case 'datetime': {
                        $currentJsonValue = $currentTypedValue->format('Y-m-d H:i:s');
                    }
                }
            }
        }

        $different = ($currentJsonValue !== $newJsonValue);
        if ($different) {
            $this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info> ($currentJsonValue => $newJsonValue)");
            $setter = 'set'.ucfirst($fieldName);
            $entity->$setter($newTypedValue);
        }
    }

    protected function copyKeyToEntity($entity, $entityName, $data, $key, $isMandatory = true) {
        $metadata = $this->em->getClassMetadata($entityName);

        if (!key_exists($key, $data)) {
            if ($isMandatory) {
                throw new \Exception("Missing key [$key] in ".json_encode($data));
            } else {
                $data[$key] = null;
            }
        }
        $value = $data[$key];

        if (!key_exists($key, $metadata->fieldNames)) {
            throw new \Exception("Missing column [$key] in entity ".$entityName);
        }
        $fieldName = $metadata->fieldNames[$key];

        $this->copyFieldValueToEntity($entity, $entityName, $fieldName, $value);
    }

    protected function getEntityFromData($entityName, $data, $mandatoryKeys, $foreignKeys, $optionalKeys) {
        if (!key_exists('code', $data)) {
            throw new \Exception("Missing key [code] in ".json_encode($data));
        }

        $entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);
        if (!$entity) {
            $entity = new $entityName();
        }
        $orig = $entity->serialize();

        foreach ($mandatoryKeys as $key) {
            $this->copyKeyToEntity($entity, $entityName, $data, $key, true);
        }

        foreach ($optionalKeys as $key) {
            $this->copyKeyToEntity($entity, $entityName, $data, $key, false);
        }

        foreach ($foreignKeys as $key) {
            $foreignEntityShortName = ucfirst(str_replace('_code', '', $key));

            if (!key_exists($key, $data)) {
                if ($key === "subtype_code"){
                  continue;
                }
                throw new \Exception("Missing key [$key] in ".json_encode($data));
            }

            $foreignCode = $data[$key];
            if (!key_exists($foreignEntityShortName, $this->collections)) {
                throw new \Exception("No collection for [$foreignEntityShortName] in ".json_encode($data));
            }
            if (!key_exists($foreignCode, $this->collections[$foreignEntityShortName])) {
                print("\n\nValid Codes:\n");
                #print_r($this->collections);
                #foreach($this->collections as $row) {
                #    var_dump($row);
                #}
                print("##############################\n");
                throw new \Exception("Invalid code [$foreignCode] for key [$key] in ".json_encode($data));
            }
            $foreignEntity = $this->collections[$foreignEntityShortName][$foreignCode];

            $getter = 'get'.$foreignEntityShortName;
            if (!$entity->$getter() || $entity->$getter()->getId() !== $foreignEntity->getId()) {
                $this->output->writeln("Changing the <info>$key</info> of <info>".$entity->toString()."</info>");
                $setter = 'set'.$foreignEntityShortName;
                $entity->$setter($foreignEntity);
            }
        }

        if ($entity->serialize() !== $orig) {
            return $entity;
        }
    }

    ##
    ## https://www.php.net/manual/en/class.splfileinfo.php
    ##
    protected function getDataFromFile(\SplFileInfo $fileinfo) {
        ##
        ## https://www.php.net/manual/en/splfileinfo.openfile.php
        ##
        $file = $fileinfo->openFile('r');
        $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        ##
        ## Read in all lines of file
        ##
        $lines = [];
        foreach ($file as $line) {
            if ($line !== false) {
                $lines[] = $line;
            }
        }
        ##
        ## Convert file to string.
        ##
        $content = implode('', $lines);

        ##
        ## parse string as a json
        ## https://www.php.net/json_decode
        ##
        $data = json_decode($content, true);

        ##
        ## if NOT proper json, throw error
        ##
        if ($data === null) {
            throw new \Exception("File [".$fileinfo->getPathname()."] contains incorrect JSON (error code ".json_last_error().")");
        }

        return $data;
    }

    protected function getFileInfo($path, $filename) {
        $fs = new Filesystem();

        if (!$fs->exists($path)) {
            throw new \Exception("No repository found at [$path]");
        }

        $filepath = "$path/$filename";

        if (!$fs->exists($filepath)) {
            throw new \Exception("No $filename file found at [$path]");
        }

        return new \SplFileInfo($filepath);
    }

    protected function getFileSystemIterator($path) {
        $fs = new Filesystem();

        if (!$fs->exists($path)) {
            throw new \Exception("No repository found at [$path]");
        }

        $directory = 'set';

        if (!$fs->exists("$path/$directory")) {
            throw new \Exception("No '$directory' directory found at [$path]");
        }

        $iterator = new \GlobIterator("$path/$directory/*.json");

        if (!$iterator->count()) {
            throw new \Exception("No json file found at [$path/set]");
        }

        return $iterator;
    }

    ##
    ## Load Collection of cards from database
    ## https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html
    ##
    protected function loadCollection($entityShortName) {
        $this->collections[$entityShortName] = [];

        ##
        ## In Doctrine, a repository is a class that concentrates code 
        ## responsible for querying and filtering your tables.
        ##
        ## The "entityShortName" is the name of the table in MySQL.
        ## "findAll" will return all rows.
        ## https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html
        ##
        $entities = $this->em->getRepository('AppBundle:'.$entityShortName)->findAll();
        #error_reporting(E_ALL);
        #ini_set('display_errors', True);
        #print("$entityShortName entities");
        #print("\n##################\n");
        #print_r($entities);
        #print("\n##################");
        print("Loading $entityShortName collection\n");
        foreach ($entities as $entity) {
            ##
            ## "code" is a field in the table. Most tables have it.
            ##
            $code = $entity->getCode();
            print("  * ".$entityShortName.": [".$code."]");
            #print("\n##################\n");
            #var_dump($entity);
            #print("\n##################");
            print("\n");
            #die();
            $this->collections[$entityShortName][$code] = $entity;
            # object(AppBundle\Entity\Side)#756 (4) {
            #   ["id":"AppBundle\Entity\Side":private]=> int(1)
            #   ["code":"AppBundle\Entity\Side":private]=> string(5) "light"
            #   ["name":"AppBundle\Entity\Side":private]=> string(5) "Light"
            #   ["cards":"AppBundle\Entity\Side":private]=> object(Doctrine\ORM\PersistentCollection)#749 (9) {
        }
        print("done.\n");
    }
}
