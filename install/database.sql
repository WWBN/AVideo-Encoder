-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `formats`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `formats` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `code` VARCHAR(400) NOT NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  `extension` VARCHAR(5) NULL,
  `extension_from` VARCHAR(5) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `streamers`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `streamers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `siteURL` VARCHAR(255) NOT NULL,
  `user` VARCHAR(45) NOT NULL,
  `pass` VARCHAR(45) NOT NULL,
  `priority` INT NOT NULL DEFAULT 3,
  `isAdmin` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `encoder_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `encoder_queue` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fileURI` VARCHAR(255) NOT NULL,
  `filename` VARCHAR(400) NOT NULL,
  `status` ENUM('queue', 'encoding', 'error', 'done', 'downloading') NULL,
  `status_obs` VARCHAR(255) NULL,
  `return_vars` VARCHAR(45) NULL,
  `priority` INT(1) NULL,
  `title` VARCHAR(255) NULL,
  `videoDownloadedLink` VARCHAR(255) NULL,
  `downloadedFileName` VARCHAR(255) NULL,
  `streamers_id` INT NOT NULL,
  `formats_id` INT NOT NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_encoder_queue_formats_idx` (`formats_id` ASC),
  INDEX `fk_encoder_queue_streamers1_idx` (`streamers_id` ASC),
  CONSTRAINT `fk_encoder_queue_formats`
    FOREIGN KEY (`formats_id`)
    REFERENCES `formats` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_encoder_queue_streamers1`
    FOREIGN KEY (`streamers_id`)
    REFERENCES `streamers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `configurations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT NOT NULL,
  `allowedStreamersURL` TEXT NULL,
  `defaultPriority` INT(1) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
