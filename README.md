<img src="https://tv.qtune.io/videos/userPhoto/logo.png?1641610363"/>

# Encoder
### This is the Encoder for <a href="https://tv.qtune.io/" target="_blank">LiveTV</a>.
LiveTV is a video-sharing Platform software, When you use LiveTV Platform, you can create your own video sharing studio, LiveTV will help you import and encode videos from other sites like Youtube, Vimeo, etc. and you can share directly on your website. In addition, you can use Facebook or Google login to register users on your site. 

### If you are not sure what is LiveTV is Platform, go to our <a href="https://tv.qtune.io/" target="_blank">LiveTV</a> page


* <a href="https://tv.qtune.io/" target="_blank">Build your own Netflix</a>
  - We provide you a Flix like platform. On this site you can subscribe (with real money on PayPal). this subscription will allow you to watch our private videos. There is an user that you can use to see how it works. user: user and pass: Qtune@101.


## Important Information

> Streamer can be installed on any Server, including Windows, but the encoder and Livestream should work fine on any Linux distribution. However we recommend Ubuntu 16 or 17 without any kind of control panel.
> The problem with cPanel, Plesk, Webmin, VestaCP, etc. It's because we need full root access to install some libs, and maybe compile them. Another important point is that to make Livestream work, we need to compile Nginx and the control panels often prevent us from running the commands forcing the installation available only on your panel.




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

# Server Requirements

In order for you to be able to run LiveTV, there are certain tools that need to be installed on your server.
- Linux (Kernel 2.6.32+)
- PHP 5.6+
- MySQL 5.0+
- Apache web server 2.x (with mod_rewrite enabled)
- FFMPEG
- PHP Shell Exec Access, *Note: This function is disabled when PHP is running in safe mode.*

# What is new on this version?
Since version 4.x+ we separate the streamer website from the encoder website, so that we can distribute the application on different servers.
- The Streamer site, is the main front end and has as main function to attend the visitors of the site, through a layout based on the youtube experience, you can host the streamer site in any common internet host can host it (Windows or Linux).
- The Encoder site, will be better than the original encoder, the new encoder will be in charge of managing a media encoding queue. You can Donwload the encoder 
