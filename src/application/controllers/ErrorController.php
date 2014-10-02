<?php

use \Tranquility\Utility                   as Utility;
use Tranquility\Enum\System\HttpStatusCode as EnumHttpStatusCode;

class ErrorController extends BaseController {
    
    protected $_view;
    protected $_displayDetailedErrors = false;
    
    public function __construct($request, $config, $db, $log) {
        parent::__construct($request, $config, $db, $log);
        $this->_view = $this->getView();
        $this->_view->setFilename('error');
        $this->_view->request = $request;
        $this->_view->displayDetailedErrors = Utility::extractValue($config, 'displayDetailedErrors', false);
    }
    
    public function displayException(\Exception $ex) {
        $code = $ex->getCode();
        switch ($code) {
            case EnumHttpStatusCode::NotFound:
                return $this->_display404Error($ex);
            case EnumHttpStatusCode::InternalServerError:
            default:
                return $this->_display500Error($ex);
        }
    }
    
    private function _display404Error($ex) {
        $this->_view->setHttpStatusCode(EnumHttpStatusCode::NotFound);
        $this->_view->exception = $ex;
        $this->_view->heading = 'Uh oh!';
        $this->_view->subHeading = 'We couldn\'t find that page...';
        $this->_view->message = 'It\'s looking like you may have taken a wrong turn. Don\'t worry... it happens to the best of us. In the meantime, you could try going back and trying the link again. (Make sure to double check for typos!)';
        return $this->_view->render();
    }
    
    private function _display500Error($ex) {
        $this->_view->setHttpStatusCode(EnumHttpStatusCode::InternalServerError);
        $this->_view->exception = $ex;
        $this->_view->heading = 'Uh oh!';
        $this->_view->subHeading = 'An unexpected error occurred...';
        $this->_view->message = 'It\'s not you, it\'s us. Seriously... Possibly one of the hamsters running our server has run out of puff. Whatever caused this issue, rest assured we\'ll get right onto to tracking it down and fixing it.';
        return $this->_view->render();
    }
}