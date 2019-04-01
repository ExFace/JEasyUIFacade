<?php
namespace exface\JEasyUIFacade;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\FacadeFactory;
use exface\Core\Facades\AbstractPWAFacade\ServiceWorkerInstaller;

class JEasyUIFacadeApp extends App
{

    /**
     * {@inheritdoc}
     * 
     * An additional installer is included to condigure the routing for the HTTP facades.
     * 
     * @see App::getInstaller($injected_installer)
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // Routing installer
        $tplInstaller = new HttpFacadeInstaller($this->getSelector());
        $tplInstaller->setFacade(FacadeFactory::createFromString('exface.JEasyUIFacade.JEasyUIFacade', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        // ServiceWorker installer
        $installer->addInstaller(ServiceWorkerInstaller::fromConfig($this->getSelector(), $this->getConfig(), $this->getWorkbench()->getCMS()));
        
        return $installer;
    }
}
?>