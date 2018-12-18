-- MySQL dump 10.13  Distrib 5.7.18, for Linux (x86_64)
--
-- Host: localhost    Database: YouPHPTube-Encoder
-- ------------------------------------------------------
-- Server version	5.7.18-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `formats`
--

DROP TABLE IF EXISTS `formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `code` varchar(400) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `extension` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formats`
--

LOCK TABLES `formats` WRITE;
/*!40000 ALTER TABLE `formats` DISABLE KEYS */;
INSERT INTO `formats` VALUES (1,'MP4','ffmpeg -i {$pathFileName} -vf scale=1280:720 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}','2017-01-01 00:00:00',NULL,'mp4'),(2,'Webm','ffmpeg -i {$pathFileName} -vf scale=640:360 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}','2017-07-11 12:56:26','2017-07-11 12:56:26','webm'),(3,'MP3','ffmpeg -i {$pathFileName} -acodec libmp3lame -y {$destinationFile}','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3'),(4,'OGG','ffmpeg -i {$pathFileName} -acodec libvorbis -y {$destinationFile}','2017-01-01 00:00:00','2017-01-01 00:00:00','ogg'),(5,'MP3 to Spectrum.MP4','ffmpeg -i {$pathFileName} -filter_complex \\\'[0:a]showwaves=s=858x480:mode=line,format=yuv420p[v]\\\' -map \\\'[v]\\\' -map 0:a -c:v libx264 -c:a copy {$destinationFile}','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4'),(6,'Video.MP4 to Audio.MP3','ffmpeg -i {$pathFileName} -q:a 0 -map a {$destinationFile}','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3'),(7,'Video to Spectrum','6-5-2','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4'),(8,'Video to Audio','6-4','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3'),(9,'Both Video','1-2','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4'),(10,'Both Audio','3-4','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3');
/*!40000 ALTER TABLE `formats` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-07-14 11:53:11
