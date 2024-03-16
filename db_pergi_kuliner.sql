-- MySQL Script generated by MySQL Workbench
-- Sat Mar 16 20:00:39 2024
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema db_pergi_kuliner
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `db_pergi_kuliner` ;

-- -----------------------------------------------------
-- Schema db_pergi_kuliner
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `db_pergi_kuliner` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `db_pergi_kuliner` ;

-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`tabled`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`tabled` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`tabled` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `du` LONGBLOB NULL,
  `u` LONGTEXT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`tablee`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`tablee` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`tablee` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `u` LONGTEXT NULL,
  `v` LONGTEXT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`restaurant`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`restaurant` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`restaurant` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(512) NOT NULL,
  `name` LONGTEXT NOT NULL,
  `category` LONGTEXT NULL,
  `price_min` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `price_max` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `address` LONGTEXT NULL,
  `phone` LONGTEXT NULL,
  `rating_overall` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `rating_flavor` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `rating_atmosphere` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `rating_relevant` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `rating_service` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `rating_cleanliness` DECIMAL(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `url_UNIQUE` (`url` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`payment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`payment` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`payment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `method` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_payment_restaurant_id_idx` (`restaurant_id` ASC) VISIBLE,
  CONSTRAINT `fk_payment_restaurant_id_idx`
    FOREIGN KEY (`restaurant_id`)
    REFERENCES `db_pergi_kuliner`.`restaurant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`facility`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`facility` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`facility` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `facility` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_facility_restaurant_id_idx` (`restaurant_id` ASC) VISIBLE,
  CONSTRAINT `fk_facility_restaurant_id_idx`
    FOREIGN KEY (`restaurant_id`)
    REFERENCES `db_pergi_kuliner`.`restaurant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `db_pergi_kuliner`.`review`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `db_pergi_kuliner`.`review` ;

CREATE TABLE IF NOT EXISTS `db_pergi_kuliner`.`review` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` LONGTEXT NULL,
  `username` LONGTEXT NOT NULL,
  `total_review` INT NOT NULL DEFAULT 0,
  `level` INT NOT NULL DEFAULT 0,
  `subject` LONGTEXT NULL,
  `rating` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `visit_date` DATE NULL,
  `price_from` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `price_to` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `review` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_review_restaurant_id_idx` (`restaurant_id` ASC) VISIBLE,
  CONSTRAINT `fk_review_restaurant_id_idx`
    FOREIGN KEY (`restaurant_id`)
    REFERENCES `db_pergi_kuliner`.`restaurant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
