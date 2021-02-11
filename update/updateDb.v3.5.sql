SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


CREATE TABLE IF NOT EXISTS `upload_queue` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `encoders_id` INT NOT NULL,
  `resolution` VARCHAR(255) NOT NULL,
  `format` VARCHAR(255) NOT NULL,
  `videos_id` INT NOT NULL,
  `status` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_upload_queue_encoders_idx` (`encoders_id` ASC),
  CONSTRAINT `fk_upload_queue_encoders`
    FOREIGN KEY (`encoders_id`)
    REFERENCES `encoder_queue` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

ALTER TABLE `encoder_queue` ADD COLUMN `override_status` VARCHAR(45) NULL;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
-- support for the chunked transfer between servers
UPDATE configurations SET  version = '3.5', modified = now() WHERE id = 1;
