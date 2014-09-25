<?php

// Construct response array
$response = array(
    
    // Metadata
    'meta' => array(
        'code' => $this->exception->getCode(),
        'errorMessage' => $this->message
    ),
    
    // Empty response section
    'response' => array(),
    
    // Empty messages section
    'messages' => array()
);

// If we are showing debug information, add it here
if ($this->displayDetailedErrors) {
    $response['_debug'] = array(
        'request' => array(
            'get' => $this->request->query->all(),
            'post' => $this->request->request->all(),
            'attributes' => $this->request->attributes->all()
        ),
        'exception' => array(
            'message' => $this->exception->getMessage(),
            'stackTrace' => $this->exception->getTrace()
        )
    );
}

echo json_encode($response);