# WPiuma

Wordpress plugin for Piuma, the simple and fast image optimizer server you can host on your machine

## Installation

You jus need to download the files from https://github.com/piumaio/wpiuma/releases and place the content on yor ```/wp-content/plugins/```.

![Control Panel image](../assets/cp.jpg?raw=true)

## Structure

```
/classes
    /class_piuma_image_optimizer.php
define.php
index.php
LICENSE
piuma-image-optimizer.php
readme.md
reset.php
scripts.js
style.css
update.php
```

## Filters

Right now WPiuma supports only one filter which is `piuma_image_src`.
This can be used whenever you need have an full absolute URL of an image, and you want to pass it to Piuma.


Here you can find an example:

```php
<?php
    $my_image_url = apply_filters('piuma_image_src', get_template_directory_uri() . '/static/img/my_fancy_image.jpg');
?>

<img src="<?php echo $my_image_url ?>">
```
In this case the image we want to serve is located in our custom template, so we get the URL to it and then pass it through our filter, and the job is done. In case plugin is deactivated this filter will simply return the same string received in input, like any other filter in WordPress.
