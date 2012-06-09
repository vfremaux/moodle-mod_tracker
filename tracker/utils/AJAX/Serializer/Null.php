<?php
// $Id: Null.php,v 1.2 2010/07/23 16:56:42 vf Exp $
/**
 * Null Serializer
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.6
 * @link       http://pear.php.net/package/PackageName
 */
class HTML_AJAX_Serializer_Null 
{
    
    function serialize($input) 
    {
        return $input;
    }

    function unserialize($input) 
    {
        return $input;
    }
}
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
