<?php

namespace beaba\install;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class Composer extends LibraryInstaller
{
    
    const FRAMEWORK     = 'beaba-core';
    const APPLICATION   = 'beaba-application';
    const PLUGIN        = 'beaba-plugin';
    
    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package) 
    {
        parent::install($repo, $package);
        if ( 
            $package->getType() === self::APPLICATION 
            && !file_exists('./index.php')
        ) 
        {
            $name = explode('/', $package->getName(), 2);
            $template = '<?php'.<<<EOT
// DEFINES APPLICATION PATH
defined('BEABA_PATH') OR define(
    'BEABA_PATH',
    !empty(\$_SERVER['BEABA_PATH']) ?
    \$_SERVER['BEABA_PATH'] :
    '{$this->getFrameworkPath()}'
);
defined('BEABA_APP') OR define(
    'BEABA_APP',
    !empty(\$_SERVER['BEABA_APP']) ?
    \$_SERVER['BEABA_APP'] :
    '{$this->getApplicationPath()}'
);
defined('APP_NAME') OR define(
    'APP_NAME',
    !empty(\$_SERVER['APP_NAME']) ?
    \$_SERVER['APP_NAME'] :
    '{$name[1]}'
);

// LOADS SYSTEM
require_once BEABA_PATH . '/bootstrap.php';

// LOADS AN APPLICATION INSTANCE
\$app = new beaba\core\WebApp();
\$app->dispatch();
EOT;
            // create the website bootstrap
            file_put_contents('./index.php', $template);
        }
    }
    
    /**
     * Gets the framework path
     * @return string 
     */
    public function getFrameworkPath() 
    {
        return !empty($_SERVER['BEABA_PATH']) ? 
            $_SERVER['BEABA_PATH'] : realpath('../beaba/framework')
        ;
    }
    
    /**
     * Gets the applications path
     * @return string 
     */
    public function getApplicationPath() {
        return !empty($_SERVER['BEABA_APP']) ? 
            $_SERVER['BEABA_APP'] : 
            realpath($this->getFrameworkPath() . '/../applications')
        ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        // gets the package name parts
        $name = explode('/', $package->getName(), 2);
        switch( $package->getType() ) {
            case self::FRAMEWORK:
                return $this->getFrameworkPath();
                break;
            case self::APPLICATION:
                $target =  $this->getApplicationPath() . '/' . $name[1];
                if ( !is_dir($target) ) {
                    mkdir( $target, 0777, true );
                }
                return $target;
                break;
            case self::PLUGIN:
                return $this->getFrameworkPath() . '/plugins/' . $name[1];
                break;
            default:
                throw new \InvalidArgumentException(
                    'Undefined package type : ' 
                    . $package->getType()
                );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return (
            $packageType == self::FRAMEWORK ||
            $packageType == self::APPLICATION ||
            $packageType == self::PLUGIN
        );
    }
}
