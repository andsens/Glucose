SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `countries`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `countries` ;

CREATE  TABLE IF NOT EXISTS `countries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `UNIQUE_countries__name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cities` ;

CREATE  TABLE IF NOT EXISTS `cities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `country` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `postal_code` SMALLINT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_cities__country` (`country` ASC) ,
  UNIQUE INDEX `UNIQUE_cities__country__postal_code` (`country` ASC, `postal_code` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `people`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `people` ;

CREATE  TABLE IF NOT EXISTS `people` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(255) NOT NULL ,
  `last_name` VARCHAR(255) NOT NULL ,
  `email` VARCHAR(255) NULL ,
  `address` VARCHAR(255) NOT NULL ,
  `city` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_people__city` (`city` ASC) ,
  UNIQUE INDEX `UNIQUE_people__email` (`email` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE  TABLE IF NOT EXISTS `users` (
  `person` INT UNSIGNED NOT NULL ,
  `nickname` VARCHAR(16) NOT NULL ,
  `password` CHAR(40) NOT NULL ,
  `registered` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  `last_login` TIMESTAMP NULL ,
  PRIMARY KEY (`person`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `countries`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO countries (`id`, `name`) VALUES ('1', 'Germany');
INSERT INTO countries (`id`, `name`) VALUES ('2', 'Denmark');
INSERT INTO countries (`id`, `name`) VALUES ('3', 'Norway');
INSERT INTO countries (`id`, `name`) VALUES ('4', 'Sweden');
INSERT INTO countries (`id`, `name`) VALUES ('5', 'Finland');
INSERT INTO countries (`id`, `name`) VALUES ('6', 'Netherlands');
INSERT INTO countries (`id`, `name`) VALUES ('7', 'Belgium');
INSERT INTO countries (`id`, `name`) VALUES ('8', 'United States of America');

COMMIT;

-- -----------------------------------------------------
-- Data for table `cities`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO cities (`id`, `country`, `name`, `postal_code`) VALUES ('1', '2', 'Århus', '8000');
INSERT INTO cities (`id`, `country`, `name`, `postal_code`) VALUES ('2', '1', 'Hamburg', '20095');
INSERT INTO cities (`id`, `country`, `name`, `postal_code`) VALUES ('3', '5', 'Helsinki', '0');
INSERT INTO cities (`id`, `country`, `name`, `postal_code`) VALUES ('4', '8', 'Andeby', '0');

COMMIT;

-- -----------------------------------------------------
-- Data for table `people`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO people (`id`, `first_name`, `last_name`, `email`, `address`, `city`) VALUES ('1', 'Anders', 'Ingemann', 'anders@ingemann.de', 'Vej 13', '1');
INSERT INTO people (`id`, `first_name`, `last_name`, `email`, `address`, `city`) VALUES ('2', 'Casper', 'Bach-Poulsen', 'casperbp@gmail.com', 'Somewhere', '1');

COMMIT;

-- -----------------------------------------------------
-- Data for table `users`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO users (`person`, `nickname`, `password`, `registered`, `last_login`) VALUES ('1', 'andsens', '21298df8a3277357ee55b01df9530b535cf08ec1', NULL, NULL);

COMMIT;
