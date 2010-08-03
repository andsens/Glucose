<?php
namespace Glucose\Fields;
interface Field {
	
	public function equalsCurrentValue($value);
	
	public function getTentativeValues(array $currentValues, $tentativeValue);
}