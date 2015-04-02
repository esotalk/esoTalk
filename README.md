## esoTalk – Fat-free forum software

**Help develop *Flarum*, esoTalk's successor. [Flarum on GitHub &raquo;](https://github.com/flarum/core)**

esoTalk is a free, open-source forum software package built with PHP and MySQL. It is designed to be:

 - **Fast.** esoTalk's code was architectured to have little overhead and to be as efficient as possible.
 - **Simple.** All of esoTalk's interfaces are designed around simplicity, ease-of-use, and speed.
 - **Powerful.** Despite its simplicity, a large array of [plugins](http://esotalk.org/plugins) and [skins](http://esotalk.org/skins) are available to extend the functionality of esoTalk.

esoTalk is developed by Toby Zerner in memory of his brother, Simon. 

### Donate

I've put many hundreds of hours and a lot of love into developing and maintaining esoTalk. If you have benefitted from it, why not consider [donating some schrapnel](http://esotalk.org/donate)? #feedtoby

### System Requirements

esoTalk requires **PHP 5.3+** and a modern version of **MySQL**.

The PHP **gd extension** is required to support avatar uploading.

esoTalk has only been tested on **Apache** and **lighttpd**. If you encounter a problem specific to any other web server, please [create an issue](https://github.com/esotalk/esoTalk/issues).

### Installation

Installing esoTalk is super easy. In brief, simply:

1. [Download esoTalk.](http://esotalk.org/download)
2. Extract and upload the files to your PHP-enabled web server.
3. Visit the location in your web browser and follow the instructions in the installer.

### Upgrading

To upgrade esoTalk from an older version, simply:

1. [Download](http://esotalk.org/download) the latest version of esoTalk.
2. Extract and upload all of the files to your web-server, overwriting old ones. (Be careful that you don't lose custom plugins, skins, and languages you've uploaded to the addons directory, though!)
3. Visit **your-forum.com/?p=upgrade** in your web browser and watch esoTalk complete the upgrade.

### Troubleshooting

If you are having problems installing esoTalk, view the [Troubleshooting](http://esotalk.org/docs/debug) documentation.

### SMTP Troubleshooting

If you have problems with SMTP extension, you can consider the situations below:

1. Your PHP dosen't have OpenSSL Support, but you use SSL/TLS to connect the SMTP Server.
Solution: Use Normal port instead of SSL port(like 994, for more you can visit your mail provider's help page).
If you would like to connect via SSL, you can recompile PHP by adding this function:
--with-openssl

2. Your PHP dosen't have necessary mail extensions.
Solution: Use pear to install the missing extensions.

./pear install Mail

./pear install Net_Socket-1.0.10

./pear install Net_SMTP

./pear install Auth_SASL

./pear install Mail_Mime

3: sendmail_path isn't correct.
Solution: Correct it in php.ini.
