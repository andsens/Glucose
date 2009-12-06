<?php
/**
 * Root exception class, which is thrown by the framework.
 * Only exceptions inhereting from this class should be thrown from the model.
 * @author andsens
 * @package glucose
 * @subpackage glucose.exceptions.user
 */
namespace Glucose\Exceptions\User;
interface UserException extends \Glucose\Exceptions\GlucoseException {
	
}
?>