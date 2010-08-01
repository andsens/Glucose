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
  UNIQUE INDEX `UQ_countries__name` (`name` ASC) )
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
  UNIQUE INDEX `UQ_cities__contry__postal_code` (`country` ASC, `postal_code` ASC) ,
  CONSTRAINT `FK_cities__country`
    FOREIGN KEY (`country` )
    REFERENCES `countries` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
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
  `city` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_people__city` (`city` ASC) ,
  UNIQUE INDEX `UQ_people__email` (`email` ASC) ,
  CONSTRAINT `FK_people__city`
    FOREIGN KEY (`city` )
    REFERENCES `cities` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
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
  PRIMARY KEY (`person`) ,
  INDEX `FK_users__person` (`person` ASC) ,
  CONSTRAINT `FK_users__person`
    FOREIGN KEY (`person` )
    REFERENCES `people` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `overlapping_unique_keys`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `overlapping_unique_keys` ;

CREATE  TABLE IF NOT EXISTS `overlapping_unique_keys` (
  `column1` INT NOT NULL ,
  `column2` INT NOT NULL ,
  `column3` INT NOT NULL ,
  PRIMARY KEY (`column1`, `column2`) ,
  UNIQUE INDEX `UQ_overlapping_unique_keys__column2__column3` (`column2` ASC, `column3` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `compund_foreign_key`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `compund_foreign_key` ;

CREATE  TABLE IF NOT EXISTS `compund_foreign_key` (
  `column1` INT NOT NULL ,
  `column2` INT NOT NULL ,
  PRIMARY KEY (`column1`, `column2`) ,
  INDEX `fk_compund_foreign_key__column11` (`column1` ASC, `column2` ASC) ,
  CONSTRAINT `fk_compund_foreign_key__column11`
    FOREIGN KEY (`column1` , `column2` )
    REFERENCES `overlapping_unique_keys` (`column1` , `column2` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `countries`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

insert into `countries` (`id`, `name`) values (1, 'Germany');
insert into `countries` (`id`, `name`) values (2, 'Denmark');
insert into `countries` (`id`, `name`) values (3, 'Norway');
insert into `countries` (`id`, `name`) values (4, 'Sweden');
insert into `countries` (`id`, `name`) values (5, 'Finland');
insert into `countries` (`id`, `name`) values (6, 'Netherlands');
insert into `countries` (`id`, `name`) values (7, 'Belgium');
insert into `countries` (`id`, `name`) values (8, 'United States of America');

COMMIT;

-- -----------------------------------------------------
-- Data for table `cities`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

insert into `cities` (`id`, `country`, `name`, `postal_code`) values (1, 2, 'Århus', '8000');
insert into `cities` (`id`, `country`, `name`, `postal_code`) values (2, 1, 'Hamburg', '20095');
insert into `cities` (`id`, `country`, `name`, `postal_code`) values (3, 5, 'Helsinki', '0');
insert into `cities` (`id`, `country`, `name`, `postal_code`) values (4, 8, 'Andeby', '0');

COMMIT;

-- -----------------------------------------------------
-- Data for table `people`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

insert into `people` (`id`, `first_name`, `last_name`, `email`, `address`, `city`) values (1, 'Anders', 'Ingemann', 'anders@ingemann.de', 'Vej 13', 1);
insert into `people` (`id`, `first_name`, `last_name`, `email`, `address`, `city`) values (2, 'Casper', 'Bach-Poulsen', 'casperbp@gmail.com', 'Somewhere', 1);

COMMIT;

-- -----------------------------------------------------
-- Data for table `users`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

insert into `users` (`person`, `nickname`, `password`, `registered`, `last_login`) values (1, 'andsens', '21298df8a3277357ee55b01df9530b535cf08ec1', NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `overlapping_unique_keys`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

insert into `overlapping_unique_keys` (`column1`, `column2`, `column3`) values (0, 1, 1);
insert into `overlapping_unique_keys` (`column1`, `column2`, `column3`) values (1, 2, 3);
insert into `overlapping_unique_keys` (`column1`, `column2`, `column3`) values (2, 4, 9);
insert into `overlapping_unique_keys` (`column1`, `column2`, `column3`) values (3, 8, 27);

COMMIT;
