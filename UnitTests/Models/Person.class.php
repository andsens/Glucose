<?php
/**
 *
 * @author Anders
 * @property int $id MySQL auto increment
 * @property string $firstName First name of the person
 * @property string $lastName Last name of the person
 * @property int $address The address at which the person lives
 * @property City $city The city the person lives in
 *
 */
class Person extends Glucose\Model {
	public static $className = 'Person';
}
?>