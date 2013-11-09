<?php


/**
 * Description of Resource
 *
 * @author Andriy
 */
class Resource
{
    private $_name;
    private $_location;
    
    public function __construct($real_name, $storage_file)
    {
        $this->_name = $real_name;
        $this->_location = $storage_file;
    }
    
    public function getLocation()
    {
        return $this->_location;
    }
    public function getName()
    {
        return $this->_name;
    }
}

?>
