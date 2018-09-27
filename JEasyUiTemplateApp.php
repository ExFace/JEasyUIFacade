<?php
namespace exface\JEasyUiTemplate;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Templates\AbstractHttpTemplate\HttpTemplateInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\TemplateFactory;
use exface\Core\Templates\AbstractPWATemplate\ServiceWorkerBuilder;
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
        $serviceWorkerBuilder = new ServiceWorkerBuilder();
        foreach ($this->getConfig()->getOption('INSTALLER.SERVICEWORKER.ROUTES') as $id => $uxon) {
            $serviceWorkerBuilder->addRouteToCache(
                $id,
                $uxon->getProperty('matcher'),
                $uxon->getProperty('strategy'),
                $uxon->getProperty('method'),
                $uxon->getProperty('description'),
                $uxon->getProperty('cacheName'),
                $uxon->getProperty('maxEntries'),
                $uxon->getProperty('maxAgeSeconds')
                );
        }
        foreach ($this->getConfig()->getOption('INSTALLER.SERVICEWORKER.IMPORTS') as $path) {
            $serviceWorkerBuilder->addImport($this->getWorkbench()->getCMS()->buildUrlToInclude($path));
        }
        $serviceWorkerInstaller = new ServiceWorkerInstaller($this->getSelector(), $serviceWorkerBuilder);
        $installer->addInstaller($serviceWorkerInstaller);
        
        return $installer;
    }
}
?>