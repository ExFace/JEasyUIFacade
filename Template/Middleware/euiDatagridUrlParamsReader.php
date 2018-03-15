<?php
namespace exface\JEasyUiTemplate\Template\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Interfaces\Templates\HttpTemplateInterface;
use exface\Core\Templates\AbstractHttpTemplate\Middleware\TaskRequestTrait;
use exface\Core\Templates\AbstractHttpTemplate\Middleware\DataEnricherTrait;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

/**
 * This PSR-15 middleware reads inline-filters from the URL and passes them to the task
 * in the attributes of the request.
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDatagridUrlParamsReader implements MiddlewareInterface
{
    use TaskRequestTrait;
    use DataEnricherTrait;
    
    private $template = null;
    
    private $taskAttributeName = null;
    
    private $getterMethodName = null;
    
    private $setterMethodName = null;
    
    /**
     * 
     * @param WorkbenchInterface $workbench
     */
    public function __construct(HttpTemplateInterface $template, string $dataGetterMethod, string $dataSetterMethod, $taskAttributeName = 'task')
    {
        $this->template = $template;
        $this->taskAttributeName = $taskAttributeName;
        $this->getterMethodName = $dataGetterMethod;
        $this->setterMethodName = $dataSetterMethod;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Psr\Http\Server\MiddlewareInterface::process()
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $task = $this->getTask($request, $this->taskAttributeName, $this->template);
        
        $requestParams = $request->getQueryParams();
        if (is_array($request->getParsedBody()) || $request->getParsedBody()) {
            $requestParams = array_merge($requestParams, $request->getParsedBody());
        }
        
        $result = $this->readSortParams($task, $requestParams);
        $result = $this->readPaginationParams($task, $requestParams, $result);
        
        if (! $result === false) {
            $task = $this->updateTask($task, $this->setterMethodName, $result);
            return $handler->handle($request->withAttribute($this->taskAttributeName, $task));
        } else {
            return $handler->handle($request);
        }
    }
    
    /**
     * 
     * @param TaskInterface $task
     * @param array $params
     * @param DataSheetInterface $dataSheet
     * @return \exface\Core\Interfaces\DataSheets\DataSheetInterface|NULL
     */
    protected function readSortParams (TaskInterface $task, array $params, DataSheetInterface $dataSheet = null) 
    {
        $order = isset($params['order']) ? strval($params['order']) : null;
        $sort_by = isset($params['sort']) ? strval($params['sort']) : null;
        if (! is_null($sort_by) && ! is_null($order)) {
            $dataSheet = $dataSheet ? $dataSheet : $this->getDataSheet($task, $this->getterMethodName);
            $sort_by = explode(',', $sort_by);
            $order = explode(',', $order);
            foreach ($sort_by as $nr => $sort) {
                $dataSheet->getSorters()->addFromString($sort, $order[$nr]);
            }
            return $dataSheet;
        }
        
        return null;
    }
    
    /**
     * 
     * @param TaskInterface $task
     * @param array $params
     * @param DataSheetInterface $dataSheet
     * @return \exface\Core\Interfaces\DataSheets\DataSheetInterface
     */
    protected function readPaginationParams (TaskInterface $task, array $params, DataSheetInterface $dataSheet = null) 
    {
        $dataSheet = $dataSheet ? $dataSheet : $this->getDataSheet($task, $this->getterMethodName);
        $page_length = isset($params['rows']) ? intval($params['rows']) : 0;
        $page_nr = isset($params['page']) ? intval($params['page']) : 1;
        $dataSheet->setRowOffset(($page_nr - 1) * $page_length);
        $dataSheet->setRowsOnPage($page_length);
        return $dataSheet;
    }
}