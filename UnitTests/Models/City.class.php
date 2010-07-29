<?php
/**
 *
 * @author Anders
 * @property int $id MySQL auto increment id
 * @property Country $country The country this city is in
 * @property string $name The name of the city
 * @property int $postalCode The postal code of the city
 *
 */
class City extends Glucose\Model {
	protected static $className = 'City';
}