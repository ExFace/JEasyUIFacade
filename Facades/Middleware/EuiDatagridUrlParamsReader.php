<?php
namespace exface\JEasyUIFacade\Facades\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Interfaces\Facades\HttpFacadeInterface;
use exface\Core\Facades\AbstractHttpFacade\Middleware\Traits\TaskRequestTrait;
use exface\Core\Facades\AbstractHttpFacade\Middleware\Traits\DataEnricherTrait;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

/**
 * This PSR-15 middleware reads inline-filters from the URL and passes them to the task
 * in the attributes of the request.
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDatagridUrlParamsReader implements MiddlewareInterface
{
    use TaskRequestTrait;
    use DataEnricherTrait;
    
    private $facade = null;
    
    private $taskAttributeName = null;
    
    private $getterMethodName = null;
    
    private $setterMethodName = null;
    
    /**
     * 
     * @param WorkbenchInterface $workbench
     */
    public function __construct(HttpFacadeInterface $facade, string $dataGetterMethod, string $dataSetterMethod, $taskAttributeName = 'task')
    {
        $this->facade = $facade;
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
        $task = $this->getTask($request, $this->taskAttributeName, $this->facade);
        
        $requestParams = $request->getQueryParams();
        if (is_array($request->getParsedBody()) || $request->getParsedBody()) {
            $requestParams = array_merge($requestParams, $request->getParsedBody());
        }
        
        if (isset($requestParams['exfrid'])) {
            $task->getWorkbench()->getContext()->getScopeRequest()->setSubrequestId($requestParams['exfrid']);
        }
        
        $result = $this->readSortParams($task, $requestParams);
        $result = $this->readPaginationParams($task, $requestParams, $result);
        
        if ($result !== null) {
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
        $order = isset($params['order']) ? urldecode($params['order']) : null;
        $sort_cols = isset($params['sort']) ? urldecode($params['sort']) : null;
        $sort_attrs = isset($params['sortAttr']) ? urldecode($params['sortAttr']) : null;
        if (is_null($sort_attrs)) {
            $sort_attrs = $sort_cols;
        }
        if (! is_null($sort_attrs) && ! is_null($order)) {
            $dataSheet = $dataSheet ? $dataSheet : $this->getDataSheet($task, $this->getterMethodName);
            $sort_attrs = explode(',', $sort_attrs);
            $order = explode(',', $order);
            if (! empty($sort_attrs)) {
                $dataSheet->getSorters()->removeAll();
            }
            foreach ($sort_attrs as $nr => $sort) {
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
        if (array_key_exists('rows', $params) || array_key_exists('page', $params)) {
            $dataSheet = $dataSheet ? $dataSheet : $this->getDataSheet($task, $this->getterMethodName);
            $page_length = isset($params['rows']) ? intval($params['rows']) : 0;
            $page_nr = isset($params['page']) ? intval($params['page']) : 1;
            $dataSheet->setRowsOffset(($page_nr - 1) * $page_length);
            $dataSheet->setRowsLimit($page_length);
        }
        return $dataSheet;
    }
}