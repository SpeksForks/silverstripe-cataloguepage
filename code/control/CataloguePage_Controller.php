<?php

class CataloguePage_Controller extends Page_Controller
{
    
    private static $allowed_actions = array(
        "product"
    );
    
    /**
	 * Set up the "restful" URLs
	 *
	 * @config
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action/$ID' => 'handleAction',
	);

    public function init()
    {
        parent::init();
    }
    
    public function PaginatedChildren($length = 12)
    {
        return new PaginatedList($this->Children(), $this->request);
    }
    
    public function product($request)
    {
        // Shift the current url up one and get the URL segment
		$request->shiftAllParams();
        $urlsegment = $request->param('URLSegment');
        $product_class = CataloguePage::config()->product_class;
        
        // Setup our filter and get a product
        $filter = array(
            'URLSegment' => $urlsegment,
            'Disabled' => 0
        );
        
        $object = $product_class::get()->filter($filter)->first();
        
        if(!$object)
            return $this->httpError(404);
        
        $controller = $this->controller_for($object);
        $result = $controller->handleRequest($request, $this->model);

        return $result;
    }
    
    /**
	 * Get the appropriate {@link CatalogueProductController} or
     * {@link CatalogueProductController} for handling the relevent
     * object.
	 *
	 * @param $object Either Product or Category object
	 * @param string $action
	 * @return CatalogueController
	 */
	protected static function controller_for($object, $action = null)
    {
		if ($object->class == 'CatalogueProduct') {
			$controller = "CatalogueProductController";
		} else {
			$ancestry = ClassInfo::ancestry($object->class);
            
			while ($class = array_pop($ancestry)) {
				if (class_exists($class . "_Controller")) break;
			}
            
            // Find the controller we need, or revert to a default
			if($class !== null)
                $controller = "{$class}_Controller";
            elseif(ClassInfo::baseDataClass($object->class) == "CatalogueProduct")
                $controller = "CatalogueProductController";
		}

		if($action && class_exists($controller . '_' . ucfirst($action))) {
			$controller = $controller . '_' . ucfirst($action);
		}
		
		return class_exists($controller) ? Injector::inst()->create($controller, $object) : $object;
	}
    
    
    /**
	 * @param SS_HTTPRequest $request
	 * @param $model
	 *
	 * @return HTMLText|SS_HTTPResponse
	 */
	protected function handleAction($request, $model)
    {
		//we return nested controllers, so the parsed URL params need to be discarded for the subsequent controllers
		// to work
		$request->shiftAllParams();
        
		return parent::handleAction($request, $model);
	}
}
