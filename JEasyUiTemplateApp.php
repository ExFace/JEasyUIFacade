<?php
namespace exface\JEasyUiTemplate;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Templates\AbstractHttpTemplate\HttpTemplateInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\TemplateFactory;
use exface\Core\Templates\AbstractPWATemplate\ServiceWorkerInstaller;

class JEasyUiTemplateApp extends App
{

    /**
     * {@inheritdoc}
     * 
     * An additional installer is included to condigure the routing for the HTTP templates.
     * 
     * @see App::getInstaller($injected_installer)
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // Routing installer
        $tplInstaller = new HttpTemplateInstaller($this->getSelector());
        $tplInstaller->setTemplate(TemplateFactory::createFromString('exface.JEasyUiTemplate.JEasyUiTemplate', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        // ServiceWorker installer
        $installer->addInstaller(ServiceWorkerInstaller::fromConfig($this->getSelector(), $this->getConfig(), $this->getWorkbench()->getCMS()));
        
        return $installer;
    }
}
?>