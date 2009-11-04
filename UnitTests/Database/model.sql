SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `model_unit_tests` ;
USE `model_unit_tests`;

-- -----------------------------------------------------
-- Table `model_unit_tests`.`countries`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_unit_tests`.`countries` ;

CREATE  TABLE IF NOT EXISTS `model_unit_tests`.`countries` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `model_unit_tests`.`cities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_unit_tests`.`cities` ;

CREATE  TABLE IF NOT EXISTS `model_unit_tests`.`cities` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `country` INT NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `postal_code` SMALLINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_cities__country` (`country` ASC) ,
  UNIQUE INDEX `UNIQUE_cities__country__postal_code` (`country` ASC, `postal_code` ASC) ,
  CONSTRAINT `FK_cities__country`
    FOREIGN KEY (`country` )
    REFERENCES `model_unit_tests`.`countries` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `model_unit_tests`.`person`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_unit_tests`.`person` ;

CREATE  TABLE IF NOT EXISTS `model_unit_tests`.`person` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(255) NOT NULL ,
  `last_name` VARCHAR(255) NOT NULL ,
  `email` VARCHAR(255) NULL ,
  `address` VARCHAR(255) NOT NULL ,
  `city` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_customers__city` (`city` ASC) ,
  UNIQUE INDEX `UNIQUE_customers__email` (`email` ASC) ,
  CONSTRAINT `FK_customers__city`
    FOREIGN KEY (`city` )
    REFERENCES `model_unit_tests`.`cities` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `model_unit_tests`.`countries`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
USE `model_unit_tests`;
INSERT INTO `countries` (`id`, `name`) VALUES (1, 'Germany');
INSERT INTO `countries` (`id`, `name`) VALUES (2, 'Denmark');
INSERT INTO `countries` (`id`, `name`) VALUES (3, 'Norway');
INSERT INTO `countries` (`id`, `name`) VALUES (4, 'Sweden');
INSERT INTO `countries` (`id`, `name`) VALUES (5, 'Finland');
INSERT INTO `countries` (`id`, `name`) VALUES (6, 'Netherlands');
INSERT INTO `countries` (`id`, `name`) VALUES (7, 'Belgium');

COMMIT;

-- -----------------------------------------------------
-- Data for table `model_unit_tests`.`cities`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
USE `model_unit_tests`;
INSERT INTO `cities` (`id`, `country`, `name`, `postal_code`) VALUES (1, 2, 'Århus', 8000);
INSERT INTO `cities` (`id`, `country`, `name`, `postal_code`) VALUES (2, 1, 'Hamburg', 20095);
INSERT INTO `cities` (`id`, `country`, `name`, `postal_code`) VALUES (3, 5, 'Helsinki', 00);

COMMIT;

-- -----------------------------------------------------
-- Data for table `model_unit_tests`.`person`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
USE `model_unit_tests`;
INSERT INTO `person` (`id`, `first_name`, `last_name`, `email`, `address`, `city`) VALUES (1, 'Anders', 'Ingemann', 'anders@ingemann.de', 'Vej 13', 1);

COMMIT;
