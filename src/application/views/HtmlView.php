<?php

/**
 * Creates a HTML5 render of the API response
 *
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */
class HtmlView extends BaseView {

    public function __construct($config = array()) {
        $this->_templateFileSuffix = 'html';
        $this->setContentType('text/html; charset=utf8');
        parent::__construct($config);
    }
    
    public function formatArray($content, $indent = 4) {
        // Generate indent in space characters
        $spaces = str_repeat(" ", $indent);
        
        $html = $spaces."<ul> \n";
        foreach ($content as $field => $value) {
            $html .= $spaces."  <li> \n";
            $html .= $spaces."    <strong>".$field.":</strong> \n";
            if (is_array($value)) {
                $html .= "\n".$this->formatArray($value, ($indent + 4));
            } else {
                // Encode HTML entities
                $value = htmlentities($value, ENT_COMPAT, 'UTF-8');
                
                // If value is a hyperlink, make the link active
                if ((strpos($value, 'http://') === 0) || (strpos($value, 'https://') === 0)) {
                    $html .= $spaces."    <a href='".$value."'>".$value."</a> \n";
                } else {
                    $html .= $spaces."    ".$value." \n";
                }
            }
            $html .= $spaces."  </li> \n";
        }
        $html .= $spaces."</ul> \n";
        
        return $html;
    }
}
