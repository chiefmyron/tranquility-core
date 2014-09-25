<?php

/**
 * Creates an XML render of the API response
 *
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */
class XmlView extends BaseView {
    
    public function render($content, $statusCode) {
        // Set headers
        
        $html  = $this->startLayout();
        $html .= $this->printArray($content);
        
        $response = Response::make($html, $statusCode);
        $response->header('Content-Type', 'application/xml; charset=utf8');
        return $response;
    }
    
    protected function startLayout() {
        $xml = "<?xml version='1.0' encoding='utf-8'?> \n";
        return $xml;
    }
    
    protected function printArray($content, $indent = 0) {
        // Generate indent in space characters
        $spaces = str_repeat(" ", $indent);
        $xml = '';
        
        foreach ($content as $field => $value) {
            $xml .= $spaces."<".$field.">";
            if (is_array($value)) {
                $xml .= "\n".$this->printArray($value, ($indent + 4));
                $xml .= $spaces."</".$field."> \n";
            } else {
                $xml .= htmlentities($value)."</".$field."> \n";
            }
        }
        
        return $xml;
    }
}
