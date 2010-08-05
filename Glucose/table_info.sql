SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `tables`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tables` ;

CREATE  TABLE IF NOT EXISTS `tables` (
  `name` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`name`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `columns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `columns` ;

CREATE  TABLE IF NOT EXISTS `columns` (
  `name` VARCHAR(64) NOT NULL ,
  `table` VARCHAR(64) NOT NULL ,
  `position` INT UNSIGNED NOT NULL ,
  `primary` TINYINT(1) NOT NULL ,
  `default` LONGTEXT NULL DEFAULT NULL ,
  `is_nullable` TINYINT(1) NOT NULL ,
  `column_type` VARCHAR(64) NOT NULL ,
  `maximum_length` BIGINT(21) UNSIGNED NULL ,
  `on_update_current_timestamp` ENUM('yes') NULL ,
  `auto_increment` ENUM('yes') NULL DEFAULT NULL ,
  PRIMARY KEY (`name`, `table`) ,
  INDEX `FK_columns__table` (`table` ASC) ,
  UNIQUE INDEX `UQ_columns__position` (`table` ASC, `position` ASC) ,
  UNIQUE INDEX `UQ_columns__table__on_update_current_timestamp` (`table` ASC, `on_update_current_timestamp` ASC) ,
  UNIQUE INDEX `UQ_columns__table__autoincrement` (`table` ASC, `auto_increment` ASC) ,
  CONSTRAINT `FK_columns__table`
    FOREIGN KEY (`table` )
    REFERENCES `tables` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `constraints`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `constraints` ;

CREATE  TABLE IF NOT EXISTS `constraints` (
  `name` VARCHAR(64) NOT NULL ,
  `table` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`name`) ,
  INDEX `FK_constraints__table` (`table` ASC) ,
  CONSTRAINT `FK_constraints__table`
    FOREIGN KEY (`table` )
    REFERENCES `tables` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `foreign_key_constraints`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `foreign_key_constraints` ;

CREATE  TABLE IF NOT EXISTS `foreign_key_constraints` (
  `name` VARCHAR(64) NOT NULL ,
  `on_update` ENUM('CASCADE', 'RESTRICT', 'SET NULL') NOT NULL ,
  `on_delete` ENUM('CASCADE', 'RESTRICT', 'SET NULL') NOT NULL ,
  PRIMARY KEY (`name`) ,
  INDEX `FK_foreign_key_constraints__name` (`name` ASC) ,
  CONSTRAINT `FK_foreign_key_constraints__name`
    FOREIGN KEY (`name` )
    REFERENCES `constraints` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `foreign_key_columns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `foreign_key_columns` ;

CREATE  TABLE IF NOT EXISTS `foreign_key_columns` (
  `source_column` VARCHAR(64) NOT NULL ,
  `source_table` VARCHAR(64) NOT NULL ,
  `destination_column` VARCHAR(64) NOT NULL ,
  `destination_table` VARCHAR(64) NOT NULL ,
  `constraint` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`source_column`, `source_table`) ,
  INDEX `FK_foreign_key_columns__source_column__source_table` (`source_column` ASC, `source_table` ASC) ,
  INDEX `FK_foreign_key_columns__destination_column__destination_table` (`destination_column` ASC, `destination_table` ASC) ,
  INDEX `FK_foreign_key_columns__constraint` (`constraint` ASC) ,
  CONSTRAINT `FK_foreign_key_columns__source_column__source_table`
    FOREIGN KEY (`source_column` , `source_table` )
    REFERENCES `columns` (`name` , `table` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_foreign_key_columns__destination_column__destination_table`
    FOREIGN KEY (`destination_column` , `destination_table` )
    REFERENCES `columns` (`name` , `table` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_foreign_key_columns__constraint`
    FOREIGN KEY (`constraint` )
    REFERENCES `foreign_key_constraints` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `unique_key_constraints`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `unique_key_constraints` ;

CREATE  TABLE IF NOT EXISTS `unique_key_constraints` (
  `name` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`name`) ,
  INDEX `FK_unique_key_constraints__name` (`name` ASC) ,
  CONSTRAINT `FK_unique_key_constraints__name`
    FOREIGN KEY (`name` )
    REFERENCES `constraints` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `unique_columns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `unique_columns` ;

CREATE  TABLE IF NOT EXISTS `unique_columns` (
  `column` VARCHAR(64) NOT NULL ,
  `table` VARCHAR(64) NOT NULL ,
  `constraint` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`column`, `table`, `constraint`) ,
  INDEX `FK_unique_columns__column__table` (`column` ASC, `table` ASC) ,
  INDEX `FK_unique_columns__constraint` (`constraint` ASC) ,
  CONSTRAINT `FK_unique_columns__column__table`
    FOREIGN KEY (`column` , `table` )
    REFERENCES `columns` (`name` , `table` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_unique_columns__constraint`
    FOREIGN KEY (`constraint` )
    REFERENCES `unique_key_constraints` (`name` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
