SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `model_test_schema` DEFAULT CHARACTER SET utf8 ;
USE `model_test_schema`;

-- -----------------------------------------------------
-- Table `model_test_schema`.`countries`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_test_schema`.`countries` ;

CREATE  TABLE IF NOT EXISTS `model_test_schema`.`countries` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `model_test_schema`.`cities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_test_schema`.`cities` ;

CREATE  TABLE IF NOT EXISTS `model_test_schema`.`cities` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `country` INT NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `postal_code` SMALLINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_cities__country` (`country` ASC) ,
  UNIQUE INDEX `UNIQUE_cities__country__postal_code` (`country` ASC, `postal_code` ASC) ,
  CONSTRAINT `FK_cities__country`
    FOREIGN KEY (`country` )
    REFERENCES `model_test_schema`.`countries` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `model_test_schema`.`customers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `model_test_schema`.`customers` ;

CREATE  TABLE IF NOT EXISTS `model_test_schema`.`customers` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(255) NOT NULL ,
  `last_name` VARCHAR(255) NOT NULL ,
  `email` VARCHAR(255) NOT NULL ,
  `address` VARCHAR(255) NOT NULL ,
  `city` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_customers__city` (`city` ASC) ,
  UNIQUE INDEX `UNIQUE_customers__email` (`email` ASC) ,
  CONSTRAINT `FK_customers__city`
    FOREIGN KEY (`city` )
    REFERENCES `model_test_schema`.`cities` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
