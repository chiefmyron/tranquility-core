<?php

//use OAuth2;
use Tranquility\Enum\System\HttpStatusCode as EnumHttpStatusCode;

class AuthController extends BaseController {
    
    protected $oauth;
    
    public function __construct($request, $config, $db, $log, $oauth) {
        $this->_oauth = $oauth;
        parent::__construct($request, $config, $db, $log);
    }
    
    public function generateToken() {
        $server = $this->_oauth;
        $token = $server->handleTokenRequest(OAuth2\Request::createFromGlobals());
        $token->send();
    }
    
    public function displayAuthorisationPrompt() {
        $server = $this->_oauth;
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();
        
        // Validate authorisation request
        if (!$server->validateAuthorizeRequest($request, $response)) {
            $response->send();
            exit();
        }
        
        // TODO: Actual user login needs to happen before asking for permission
        // Suspect this means that frontend / backend applications may actually
        // need to perform this part.
        
        // Display authorisation prompt
        $this->_view = $this->getView();
        $this->_view->setFilename('auth');
        $this->_view->heading = 'Authorisation request';
        $this->_view->subHeading = 'Application access';
        $this->_view->message = 'Do you provide authorisation?';
        $this->_view->scope = array();
        return $this->_view->render();
    }
    
    public function processAuthorisationRequest() {
        $server = $this->_oauth;
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();
        
        // Validate authorisation request
        if (!$server->validateAuthorizeRequest($request, $response)) {
            $response->send();
            exit();
        }
        
        // print the authorization code if the user has authorized your client
        $is_authorized = ($_POST['authorized'] === 'yes');
        $server->handleAuthorizeRequest($request, $response, $is_authorized);
        if ($is_authorized) {
          // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
          $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
          exit("SUCCESS! Authorization Code: $code");
        }
        $response->send();
    }
}