### If you are not sure what is YouPHPTube, go to our <a href="https://demo.youphptube.com/" target="_blank">demo</a> page or visit our <a href="https://www.youphptube.com/" target="_blank">official site</a>


* <a href="https://netflix.youphptube.com/" target="_blank">Netflix demo page</a>
  - We provide you a Netflix site sample. On this site you can subscribe (with real money on PayPal). this subscription will allow you to watch our private videos. There is an user that you can use to see how it works. user: test and pass: test.
* <a href="https://tutorials.youphptube.com/" target="_blank">Gallery demo page</a>
  - We've provided a sample Video Gallery site, which is also our tutorials site. On this sample you can login, subscribe, like, dislike and comment. but you can not upload videos. 
* <a href="http://demo.youphptube.com/" target="_blank">Full access demo site</a>
  - We provide you a Demo site sample with full access to the admin account. You will need an admin password to upload and manage videos, it is by default. user: admin and pass: 123. Also there is a non admin user and password (Only for comments). user: test and pass: test.

# First thing...
I would humbly like to thank God for giving me the necessary knowledge, motivation, resources and idea to be able to execute this project. Without God's permission this would never be possible.

**For of Him, and through Him, and to Him, are all things: to whom be glory for ever. Amen.**
`Apostle Paul in Romans 11:36`
# This Software must be used for Good, never Evil. It is expressly forbidden to use YouPHPTube to build porn sites, violence, racism or anything else that affects human integrity or denigrates the image of anyone.

# Now you can read the rest...

## Important Information

> Streamer can be installed on any Server, including Windows, but the encoder and Livestream should work fine on any Linux distribution. However we recommend Ubuntu 16 or 17 without any kind of control panel.
> The problem with cPanel, Plesk, Webmin, VestaCP, etc. It's because we need full root access to install some libs, and maybe compile them. Another important point is that to make Livestream work, we need to compile Nginx and the control panels often prevent us from running the commands forcing the installation available only on your panel.

I don´t want to read I just want you to show me how to install!!

Ok, check this out! https://tutorials.youphptube.com/video/streamer-and-encoder

### Are you having a hard time to configure or install YouPHPTube or any of its resources? fell free to ask us for help:

https://www.youphptube.com/services

# YouPHPTube - Encoder
This is the Encoder for <a href="https://www.youphptube.com/" target="_blank">YouPHPTube</a>
YouPHPTube! is an video-sharing website, It is an open source solution that is freely available to everyone. With YouPHPTube you can create your own video sharing site, YouPHPTube will help you import and encode videos from other sites like Youtube, Vimeo, etc. and you can share directly on your website. In addition, you can use Facebook or Google login to register users on your site. The service was created in march 2017.

<div align="center">
<img src="https://youphptube.com/img/prints/encoder.png">
<a href="https://encoder.youphptube.com/" target="_blank">View Public Encoder</a>
</div>

# Why do I need the Encoder?
You may want to install the encoder for a few reasons:
If you have a faster server than the public encoder server (which is likely to happen) or If you'd like a private way of encoding your videos

But the mandatory installation if you are using a private network. The public encoder will not have access to send the videos to your streamer site

If your server does not have a public IP or uses an IP on some of these bands:
- 10.0.0.0/8
- 127.0.0.0/8 (Localhost)
- 172.16.0.0/12
- 192.168.0.0/16

Surely you need to install an encoder

# YouPHPTube - Streamer
Go get it <a href="https://github.com/DanielnetoDotCom/YouPHPTube" target="_blank">here</a>

<div align="center">
<img src="https://www.youphptube.com/img/prints/prints7.png">
<a href="https://demo.youphptube.com/" target="_blank">View Demo</a>
</div>

# Server Requirements

In order for you to be able to run YouPHPTube, there are certain tools that need to be installed on your server. Don't worry, they are all FREE. To have a look at complete list of required tools, click the link below.

- Linux (Kernel 2.6.32+)
- PHP 5.6+
- MySQL 5.0+
- Apache web server 2.x (with mod_rewrite enabled)
- FFMPEG
- PHP Shell Exec Access, *Note: This function is disabled when PHP is running in safe mode.*

# What is new on this version?
Since version 4.x+ we separate the streamer website from the encoder website, so that we can distribute the application on different servers.
- The Streamer site, is the main front end and has as main function to attend the visitors of the site, through a layout based on the youtube experience, you can host the streamer site in any common internet host can host it (Windows or Linux).
- The Encoder site, will be better than the original encoder, the new encoder will be in charge of managing a media encoding queue. You can Donwload the encoder here: https://github.com/DanielnetoDotCom/YouPHPTube-Encoder. but to install it you will need ssh access to your server, usually only VPS servers give you that kind of access, that code uses commands that use the Linux shell and consume more CPU.
- I will have to install the encoder and the streamer?
No. We will be providing a public encoder, we will build the encoder in such a way that several streamers can use the same encoder. We are also providing source code for this, so you can install it internally and manage your own encoding priority.

<div align="center">
<img src="https://www.youphptube.com/img/architecture/SchemeV4.0.jpg">
<a href="https://github.com/DanielnetoDotCom/YouPHPTube-Encoder" target="_blank">Download Encoder</a>
</div>

# Older version
If you want the old version with Streamer and Encoder together (Version 3.4.1) download it <a href="https://github.com/DanielnetoDotCom/YouPHPTube/releases/tag/3.4.1">here</a>
