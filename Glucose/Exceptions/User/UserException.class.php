<?php
/**
 * Root exception class, which is thrown by the framework.
 * Only exceptions inhereting from this class should be thrown from the model.
 * @author andsens
 * @package model
 * @subpackage model.exceptions
 */
namespace Glucose\Exceptions\User;
abstract class UserException extends \Glucose\Exceptions\GlucoseException {
	
}
?>