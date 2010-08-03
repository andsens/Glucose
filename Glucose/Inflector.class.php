<?php
namespace Glucose;
use Glucose\Exceptions\User as E;
class Inflector {
	
	private static $classNameMapping;
	public static function setClassNameMapping(array $classNameMapping) {
		self::$classNameMapping = $classNameMapping;
	}
	
	private $modelName;
	private $tableName;
	public function __construct($modelName, array $columns, array $constraints) {
		$this->modelName = $modelName;
		$this->tableName = Inflector::getTableName($this->modelName);
		foreach($constraints as $constraint) {
			if(count($constraint->columns) == 1)
				continue;
			$fields = array();
			foreach($constraint->columns as $column)
				$fields[] =  self::camelize($column->name);
			$this->constraints[implode('And', $fields)] = $constraint;
		}
		foreach($columns as $column)
			$this->columnNames[self::variable($column->name)] = $column;
	}
	
	private $constraints;
	public function getConstraint($fieldName, array $compoundConstraintMapping) {
		if(array_key_exists($fieldName, $this->constraints))
			return $this->constraints[$fieldName];
		if(array_key_exists($fieldName, $compoundConstraintMapping)) {
			foreach($this->constraints as $concatenation => $contraint) {
				if($constraint->name == $compoundConstraintMapping[$fieldName]) {
					if(count($constraint->columns) < 2)
						throw new E\InvalidMappingException('You can only map constraints containing two or more columns.');
					unset($this->constraints[$concatenation]);
					return $this->constraints[$fieldName] = $constraint;
				}
			}
			throw new E\UndefinedConstraintException("The constraint '$compoundConstraintMapping[$fieldName]' does not exist.");
		}
	}
	
	private $columnNames;
	public function getColumn($fieldName) {
		if(!array_key_exists($fieldName, $this->columnNames))
			throw new E\UndefinedFieldException("The field $this->modelName->$fieldName does not exist.");
		return $this->columnNames[$fieldName];
	}
	
	public static function getFieldName($className, $columnName) {
		return self::variable($name);
	}
	
	public function __get($name) {
		switch($name) {
			case 'tableName':
				return $this->tableName;
			case 'modelName':
				return $this->modelName;
		}
	}
	
	public static function getTableName($className) {
		if(isset(self::$classNameMapping) && array_key_exists($className, self::$classNameMapping))
			return self::$classNameMapping[$className];
		return self::tableize($className);
	}
	
	public static function getClassName($tableName) {
		if(isset(self::$classNameMapping) && false !== $className = array_search($tableName, self::$classNameMapping))
				return $className;
		return self::classify($tableName);
	}
	
	/**
	 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
	 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 */
	/**
	 * This is a rewritten and more compact version of CakePHPs Inflector.
	 */
	
	public function classify($tableName) {
		return self::camelize(self::singularize($tableName));
	}
	
	public static function tableize($className) {
		return self::pluralize(self::underscore($className));
	}
	
	private static function variable($string) {
		$string = self::camelize(self::underscore($string));
		$replace = strtolower(substr($string, 0, 1));
		return preg_replace('/\w/', $replace, $string, 1);
	}
	
	private static function camelize($lowerCaseAndUnderscoredWord) {
		return str_replace(" ", "", ucwords(str_replace("_", " ", $lowerCaseAndUnderscoredWord)));
	}
	
	private static function underscore($camelCaseWord) {
		return strtolower(preg_replace('/(?<=\w)([A-Z])/', '_$1', $fieldName));
	}
	
	private static $singularized;
	private static function singularize($word) {
		if(!isset(self::$pluralRules))
			self::initializeSingularRules();
		
		if (isset(self::$singularized[$word]))
			return self::$singularized[$word];
		
		if(preg_match('/(.*)\b(' . self::$singularRules['regexIrregular'] . ')$/i', $word, $regs))
			return self::$singularized[$word] = $regs[1] . substr($word, 0, 1) . substr($irregular[strtolower($regs[2])], 1);
		
		if(preg_match('/^(' . self::$singularRules['regexUninflected'] . ')$/i', $word, $regs))
			return self::$singularized[$word] = $word;
		
		foreach (self::$singularRules['singularRules'] as $rule => $replacement)
			if (preg_match($rule, $word))
				return $_this->singularized[$word] = preg_replace($rule, $replacement, $word);
	}
	
	private static $pluralized;
	private static function pluralize($word) {
		if(!isset(self::$pluralRules))
			self::initializePluralRules();
		
		if(isset(self::$pluralizedWords[$word]))
			return self::$pluralized[$word];
		
		if(preg_match('/(.*)\b(' . self::$pluralRules['regexIrregular'] . ')$/i', $word, $regs))
			return self::$pluralized[$word] = $regs[1] . substr($word, 0, 1) . substr($irregular[strtolower($regs[2])], 1);
		
		if(preg_match('/^(' . self::$pluralRules['regexUninflected'] . ')$/i', $word, $regs))
			return self::$pluralized[$word] = $word;
		
		foreach(self::$pluralRules['pluralRules'] as $rule => $replacement)
			if (preg_match($rule, $word))
				return self::$pluralized[$word] = preg_replace($rule, $replacement, $word);
	}
	
	private static $singularRules;
	function initializeSingularRules() {
		self::$singularRules = array();
		self::$singularRules['singularRules'] = array(
			'/(s)tatuses$/i' => '\1\2tatus',
			'/^(.*)(menu)s$/i' => '\1\2',
			'/(quiz)zes$/i' => '\\1',
			'/(matr)ices$/i' => '\1ix',
			'/(vert|ind)ices$/i' => '\1ex',
			'/^(ox)en/i' => '\1',
			'/(alias)(es)*$/i' => '\1',
			'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
			'/([ftw]ax)es/' => '\1',
			'/(cris|ax|test)es$/i' => '\1is',
			'/(shoe)s$/i' => '\1',
			'/(o)es$/i' => '\1',
			'/ouses$/' => 'ouse',
			'/uses$/' => 'us',
			'/([m|l])ice$/i' => '\1ouse',
			'/(x|ch|ss|sh)es$/i' => '\1',
			'/(m)ovies$/i' => '\1\2ovie',
			'/(s)eries$/i' => '\1\2eries',
			'/([^aeiouy]|qu)ies$/i' => '\1y',
			'/([lr])ves$/i' => '\1f',
			'/(tive)s$/i' => '\1',
			'/(hive)s$/i' => '\1',
			'/(drive)s$/i' => '\1',
			'/([^fo])ves$/i' => '\1fe',
			'/(^analy)ses$/i' => '\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
			'/([ti])a$/i' => '\1um',
			'/(p)eople$/i' => '\1\2erson',
			'/(m)en$/i' => '\1an',
			'/(c)hildren$/i' => '\1\2hild',
			'/(n)ews$/i' => '\1\2ews',
			'/^(.*us)$/' => '\\1',
			'/s$/i' => '');
		
		$uninflectedSingular = array(
			'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', '.*ss', 'Amoyese',
			'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus', 'carp', 'chassis', 'clippers',
			'cod', 'coitus', 'Congoese', 'contretemps', 'corps', 'debris', 'diabetes', 'djinn', 'eland', 'elk',
			'equipment', 'Faroese', 'flounder', 'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
			'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings', 'jackanapes', 'Kiplingese',
			'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'media', 'mews', 'moose', 'mumps', 'Nankingese', 'news',
			'nexus', 'Niasese', 'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese', 'proceedings',
			'rabies', 'rice', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors', 'sea[- ]bass', 'series', 'Shavese', 'shears',
			'siemens', 'species', 'swine', 'testes', 'trousers', 'trout', 'tuna', 'Vermontese', 'Wenchowese',
			'whiting', 'wildebeest', 'Yengeese');
		self::$singularRules['regexUninflected'] = '(?:'.(implode('|', $uninflectedSingular)).')';
		
		$irregularSingular = array(
			'atlases' => 'atlas',
			'beefs' => 'beef',
			'brothers' => 'brother',
			'children' => 'child',
			'corpuses' => 'corpus',
			'cows' => 'cow',
			'ganglions' => 'ganglion',
			'genies' => 'genie',
			'genera' => 'genus',
			'graffiti' => 'graffito',
			'hoofs' => 'hoof',
			'loaves' => 'loaf',
			'men' => 'man',
			'monies' => 'money',
			'mongooses' => 'mongoose',
			'moves' => 'move',
			'mythoi' => 'mythos',
			'numina' => 'numen',
			'occiputs' => 'occiput',
			'octopuses' => 'octopus',
			'opuses' => 'opus',
			'oxen' => 'ox',
			'penises' => 'penis',
			'people' => 'person',
			'sexes' => 'sex',
			'soliloquies' => 'soliloquy',
			'testes' => 'testis',
			'trilbys' => 'trilby',
			'turfs' => 'turf');
		self::$singularRules['regexIrregular'] = '(?:'.(implode('|', $irregularSingular)).')';
	}
	
	private static $pluralRules;
	private static function initializePluralRules() {
		self::$pluralRules = array();
		self::$pluralRules['pluralRules'] = array(
			'/(s)tatus$/i' => '\1\2tatuses',
			'/(quiz)$/i' => '\1zes',
			'/^(ox)$/i' => '\1\2en',
			'/([m|l])ouse$/i' => '\1ice',
			'/(matr|vert|ind)(ix|ex)$/i'  => '\1ices',
			'/(x|ch|ss|sh)$/i' => '\1es',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(hive)$/i' => '\1s',
			'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '\1a',
			'/(p)erson$/i' => '\1eople',
			'/(m)an$/i' => '\1en',
			'/(c)hild$/i' => '\1hildren',
			'/(buffal|tomat)o$/i' => '\1\2oes',
			'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i' => '\1i',
			'/us$/' => 'uses',
			'/(alias)$/i' => '\1es',
			'/(ax|cris|test)is$/i' => '\1es',
			'/s$/' => 's',
			'/^$/' => '',
			'/$/' => 's');
		
		$uninflectedPlural = array(
			'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', 'Amoyese',
			'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus', 'carp', 'chassis', 'clippers',
			'cod', 'coitus', 'Congoese', 'contretemps', 'corps', 'debris', 'diabetes', 'djinn', 'eland', 'elk',
			'equipment', 'Faroese', 'flounder', 'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
			'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings', 'jackanapes', 'Kiplingese',
			'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'media', 'mews', 'moose', 'mumps', 'Nankingese', 'news',
			'nexus', 'Niasese', 'Pekingese', 'People', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese', 'proceedings',
			'rabies', 'rice', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors', 'sea[- ]bass', 'series', 'Shavese', 'shears',
			'siemens', 'species', 'swine', 'testes', 'trousers', 'trout', 'tuna', 'Vermontese', 'Wenchowese',
			'whiting', 'wildebeest', 'Yengeese');
		self::$pluralRules['regexUninflected'] = '(?:'.(implode('|', $uninflectedPlural)).')';
		
		$irregularPlural = array(
			'atlas' => 'atlases',
			'beef' => 'beefs',
			'brother' => 'brothers',
			'child' => 'children',
			'corpus' => 'corpuses',
			'cow' => 'cows',
			'ganglion' => 'ganglions',
			'genie' => 'genies',
			'genus' => 'genera',
			'graffito' => 'graffiti',
			'hoof' => 'hoofs',
			'loaf' => 'loaves',
			'man' => 'men',
			'money' => 'monies',
			'mongoose' => 'mongooses',
			'move' => 'moves',
			'mythos' => 'mythoi',
			'numen' => 'numina',
			'occiput' => 'occiputs',
			'octopus' => 'octopuses',
			'opus' => 'opuses',
			'ox' => 'oxen',
			'penis' => 'penises',
			'person' => 'people',
			'sex' => 'sexes',
			'soliloquy' => 'soliloquies',
			'testis' => 'testes',
			'trilby' => 'trilbys',
			'turf' => 'turfs');
		self::$pluralRules['regexIrregular'] = '(?:'.(implode('|', $irregularPlural)).')';
	}
}